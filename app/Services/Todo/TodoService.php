<?php

namespace App\Services\Todo;

use App\Models\Entity\Todo;
use App\Services\AppService;
use App\Services\AppServiceInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class TodoService extends AppService implements AppServiceInterface
{
    public function __construct(Todo $model)
    {
        parent::__construct($model);
    }

    public function dataTable($filter)
    {
        return Todo::datatable($filter)->paginate($filter->entries ?? 15);
    }

    public function getById($id)
    {
        return Todo::findOrFail($id);
    }

    public function create($data)
    {
        try {
            return Todo::create([
                'title' => $data->title,
                'assignee' => $data->assignee,
                'due_date' => $data->due_date,
                'time_tracked' => $data->time_tracked,
                'status' => $data->status,
                'priority' => $data->priority,
            ]);
        } catch (\Exception $e) {
            throw $e;
        }
    }

    public function update($id, $data)
    {
        try {
            $todo = Todo::findOrFail($id);
            $todo->update([
                'title' => $data->title,
                'assignee' => $data->assignee,
                'due_date' => $data->due_date,
                'time_tracked' => $data->time_tracked,
                'status' => $data->status,
                'priority' => $data->priority,
            ]);
            return $todo;
        } catch (\Exception $e) {
            throw $e;
        }
    }

    public function delete($id)
    {
        try {
            $todo = Todo::findOrFail($id);
            $todo->delete();
            return $todo;
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * Export data todos ke file CSV/Excel
     */
    public function exportData(Request $request)
    {
        try {
            $timestamp = date('Y-m-d-His');
            $csvFilename = 'todos-report-' . $timestamp . '.csv';
            $xlsxFilename = 'todos-report-' . $timestamp . '.xlsx';

            $csvPath = storage_path('app/public/exports/' . $csvFilename);
            $csvExportPath = 'exports/' . $csvFilename;
            $xlsxPath = storage_path('app/public/exports/' . $xlsxFilename);
            $xlsxExportPath = 'exports/' . $xlsxFilename;

            // File temporary untuk proses
            $tempCsvPath = storage_path('app/public/exports/temp-' . $timestamp . '.csv');

            // Pastikan folder ada
            $directory = storage_path('app/public/exports');
            if (!file_exists($directory)) {
                mkdir($directory, 0755, true);
            }

            // Cek dulu jika ada proses yang sedang berjalan dengan file yang sama
            if (file_exists($tempCsvPath)) {
                // Jika file CSV temp sudah ada, return sukses
                return [
                    'filename' => $csvFilename,
                    'file_url' => asset('storage/' . $csvExportPath),
                    'message' => 'Export sedang diproses'
                ];
            }

            // Cek statistik data
            $stats = $this->getExportStatistics($request);
            $totalRecords = $stats['totalRows'];
            $totalTimeTracked = $stats['totalTimeTracked'];

            if ($totalRecords == 0) {
                throw new \Exception('Tidak ada data yang ditemukan');
            }

            // Untuk data besar (>1000), gunakan CSV saja tanpa konversi ke XLSX
            $isLargeData = $totalRecords > 1000;
            $finalPath = $isLargeData ? $csvPath : $xlsxPath;
            $finalExportPath = $isLargeData ? $csvExportPath : $xlsxExportPath;
            $finalFilename = $isLargeData ? $csvFilename : $xlsxFilename;

            // Buat file kosong terlebih dahulu
            file_put_contents($finalPath, '');

            // Mulai proses asinkron untuk data besar
            if ($totalRecords > 100) {
                // Buat file indikator proses sedang berjalan
                file_put_contents($tempCsvPath, '');

                // Jalankan proses export di background dengan ignoring user abort
                ignore_user_abort(true);
                set_time_limit(0);

                // Return hasil sebelum proses selesai
                $result = [
                    'filename' => $finalFilename,
                    'file_url' => asset('storage/' . $finalExportPath),
                    'total_records' => $totalRecords,
                    'total_time_tracked' => $totalTimeTracked,
                    'file_type' => $isLargeData ? 'csv' : 'xlsx',
                    'message' => 'Export dimulai di background'
                ];

                // Jalankan operasi di background
                register_shutdown_function([$this, 'processExportInBackground'], $request, $tempCsvPath, $finalPath, $totalTimeTracked, $isLargeData);

                return $result;
            }

            // Untuk data kecil (<=100), proses langsung
            $handle = fopen($tempCsvPath, 'w');
            fputcsv($handle, ['Title', 'Assignee', 'Due Date', 'Time Tracked', 'Status', 'Priority']);

            $processedRecords = 0;
            $actualTotalTimeTracked = 0; // Track real total
            $query = $this->buildFilteredQuery($request);

            $query->orderBy('due_date', 'asc')->orderBy('id', 'asc')
                ->chunk(100, function($todos) use ($handle, &$processedRecords, &$actualTotalTimeTracked) {
                    foreach ($todos as $todo) {
                        fputcsv($handle, [
                            $todo->title,
                            $todo->assignee,
                            $todo->due_date ? date('Y-m-d', strtotime($todo->due_date)) : '',
                            $todo->time_tracked,
                            $todo->status,
                            $todo->priority
                        ]);
                        $processedRecords++;
                        $actualTotalTimeTracked += (float)$todo->time_tracked; // Tambahkan nilai sebenarnya
                    }
                });

            // Gunakan total yang dihitung dari data aktual untuk akurasi lebih baik
            $formattedTotal = number_format($actualTotalTimeTracked, 2, '.', '');
            fputcsv($handle, ['Total: ' . $processedRecords . ' todos', '', '', $formattedTotal, '', '']);
            fclose($handle);

            // Untuk data kecil, konversi ke Excel jika perlu
            if (!$isLargeData) {
                $this->quickConvertCsvToExcel($tempCsvPath, $finalPath);
                @unlink($tempCsvPath);
            } else {
                // Jika data besar, salin CSV saja
                copy($tempCsvPath, $finalPath);
                @unlink($tempCsvPath);
            }

            return [
                'filename' => $finalFilename,
                'file_url' => asset('storage/' . $finalExportPath),
                'total_records' => $processedRecords,
                'total_time_tracked' => $totalTimeTracked,
                'file_type' => $isLargeData ? 'csv' : 'xlsx',
                'message' => 'Export selesai'
            ];

        } catch (\Exception $e) {
            Log::error('Export error: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Proses export di background (dipanggil oleh register_shutdown_function)
     */
    public function processExportInBackground($request, $tempCsvPath, $finalPath, $totalTimeTracked, $isLargeData = false)
    {
        try {
            // Memastikan proses berjalan meskipun user menutup browser
            ignore_user_abort(true);
            set_time_limit(0);

            $handle = fopen($tempCsvPath, 'w');
            fputcsv($handle, ['Title', 'Assignee', 'Due Date', 'Time Tracked', 'Status', 'Priority']);

            $processedRecords = 0;
            $actualTotalTimeTracked = 0; // Track real total time
            $query = $this->buildFilteredQuery($request);

            $query->orderBy('due_date', 'asc')->orderBy('id', 'asc')
                ->chunk(1000, function($todos) use ($handle, &$processedRecords, &$actualTotalTimeTracked) {
                    foreach ($todos as $todo) {
                        fputcsv($handle, [
                            $todo->title,
                            $todo->assignee,
                            $todo->due_date ? date('Y-m-d', strtotime($todo->due_date)) : '',
                            $todo->time_tracked,
                            $todo->status,
                            $todo->priority
                        ]);
                        $processedRecords++;
                        $actualTotalTimeTracked += (float)$todo->time_tracked; // Tambahkan nilai sebenarnya
                    }
                });

            // Gunakan total yang dihitung dari data aktual, bukan dari statistik
            $formattedTotal = number_format($actualTotalTimeTracked, 2, '.', '');
            fputcsv($handle, ['Total: ' . $processedRecords . ' todos', '', '', $formattedTotal, '', '']);
            fclose($handle);

            // Untuk data besar, gunakan CSV saja tanpa konversi ke Excel
            if (!$isLargeData) {
                // Konversi ke Excel jika tidak besar
                $this->quickConvertCsvToExcel($tempCsvPath, $finalPath);
            } else {
                // Jika data besar, salin CSV saja
                copy($tempCsvPath, $finalPath);
            }

            // Hapus file temporary
            @unlink($tempCsvPath);

        } catch (\Exception $e) {
            Log::error('Background export error: ' . $e->getMessage());
            @unlink($tempCsvPath);
        }
    }

    /**
     * Mendapatkan statistik untuk export
     */
    public function getExportStatistics($filter)
    {
        try {
            // Clone filter untuk menghindari perubahan pada filter asli
            $newFilter = clone $filter;

            // Buat query yang sama dengan iterator
            $query = Todo::query()->whereNull('deleted_at');

            // Filter title
            if (!empty($newFilter->title)) {
                $query->where('title', 'ILIKE', '%' . $newFilter->title . '%');
            }

            // Filter assignee
            if (!empty($newFilter->assignee)) {
                $assignees = explode(',', $newFilter->assignee);
                $query->where(function($q) use ($assignees) {
                    foreach ($assignees as $assignee) {
                        $q->orWhere('assignee', 'ILIKE', '%' . trim($assignee) . '%');
                    }
                });
            }

            // Filter tanggal
            if (!empty($newFilter->due_date_start)) {
                $query->whereDate('due_date', '>=', date('Y-m-d', strtotime($newFilter->due_date_start)));
            }
            if (!empty($newFilter->due_date_end)) {
                $query->whereDate('due_date', '<=', date('Y-m-d', strtotime($newFilter->due_date_end)));
            }

            // Filter time tracked
            if (!empty($newFilter->time_tracked_min)) {
                $query->where('time_tracked', '>=', (float)$newFilter->time_tracked_min);
            }
            if (!empty($newFilter->time_tracked_max)) {
                $query->where('time_tracked', '<=', (float)$newFilter->time_tracked_max);
            }

            // Filter status
            if (!empty($newFilter->status)) {
                $statuses = explode(',', $newFilter->status);
                $query->whereIn('status', $statuses);
            }

            // Filter priority
            if (!empty($newFilter->priority)) {
                $priorities = explode(',', $newFilter->priority);
                $query->whereIn('priority', $priorities);
            }

            // Gunakan raw SQL untuk agregasi yang lebih cepat
            $connection = $query->getConnection();
            $bindings = $query->getBindings();
            $queryString = $query->toSql();

            $countQuery = "SELECT COUNT(*) as total_count, COALESCE(SUM(time_tracked), 0) as total_time FROM ({$queryString}) as sub";
            $result = $connection->selectOne($countQuery, $bindings);

            return [
                'totalRows' => $result->total_count ?? 0,
                'totalTimeTracked' => $result->total_time ?? 0
            ];
        } catch (\Exception $e) {
            Log::error('Export statistics error: ' . $e->getMessage());
            return ['totalRows' => 0, 'totalTimeTracked' => 0];
        }
    }

    /**
     * Build query dengan filter untuk export
     * Method ini sengaja dibuat public agar bisa diakses oleh TodosExport
     */
    public function buildFilteredQuery($request)
    {
        // Gunakan query builder untuk performa lebih baik
        $query = DB::table('todos')
            ->select('id', 'title', 'assignee', 'due_date', 'time_tracked', 'status', 'priority')
            ->whereNull('deleted_at');

        // Terapkan filter
        if (!empty($request->title)) {
            $query->where('title', 'ILIKE', '%' . $request->title . '%');
        }

        if (!empty($request->assignee)) {
            $assignees = explode(',', $request->assignee);
            $query->where(function($q) use ($assignees) {
                foreach($assignees as $assignee) {
                    $q->orWhere('assignee', 'ILIKE', '%' . trim($assignee) . '%');
                }
            });
        }

        // Ambil tanggal mulai dan akhir dari berbagai parameter yang mungkin
        $startDate = null;
        $endDate = null;

        if (!empty($request->due_date_start)) {
            $startDate = date('Y-m-d', strtotime($request->due_date_start));
        }

        if (!empty($request->due_date_end)) {
            $endDate = date('Y-m-d', strtotime($request->due_date_end));
        }

        // Terapkan filter tanggal jika valid
        if ($startDate) {
            $query->whereDate('due_date', '>=', $startDate);
        }

        if ($endDate) {
            $query->whereDate('due_date', '<=', $endDate);
        }

        if (!empty($request->time_tracked_min)) {
            $query->where('time_tracked', '>=', (float)$request->time_tracked_min);
        }

        if (!empty($request->time_tracked_max)) {
            $query->where('time_tracked', '<=', (float)$request->time_tracked_max);
        }

        if (!empty($request->status)) {
            $statuses = explode(',', $request->status);
            $query->whereIn('status', $statuses);
        }

        if (!empty($request->priority)) {
            $priorities = explode(',', $request->priority);
            $query->whereIn('priority', $priorities);
        }

        return $query->orderBy('due_date', 'asc')->orderBy('id', 'asc');
    }

    /**
     * Konversi CSV ke Excel dengan cepat dan minimal styling
     */
    protected function quickConvertCsvToExcel($csvPath, $excelPath)
    {
        // Buat spreadsheet baru
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Baca CSV (lebih cepat)
        $reader = new \PhpOffice\PhpSpreadsheet\Reader\Csv();
        $reader->setDelimiter(',');
        $reader->setEnclosure('"');
        $reader->setSheetIndex(0);

        $csvSpreadsheet = $reader->load($csvPath);
        $csvSheet = $csvSpreadsheet->getActiveSheet();

        // Salin data tanpa formatting kompleks
        $sheet->fromArray($csvSheet->toArray(), null, 'A1');

        // Style header minimal
        $sheet->getStyle('A1:F1')->getFont()->setBold(true);

        // Terakhir beri style summary (baris terakhir)
        $lastRow = $sheet->getHighestRow();
        $sheet->getStyle('A' . $lastRow . ':F' . $lastRow)->getFont()->setBold(true);

        // Simpan ke Excel dengan pengaturan minimal
        $writer = new Xlsx($spreadsheet);
        $writer->setPreCalculateFormulas(false); // Matikan precalculate
        $writer->save($excelPath);

        // Bebaskan memori
        $spreadsheet->disconnectWorksheets();
        unset($spreadsheet);
        unset($csvSpreadsheet);
        gc_collect_cycles();

        return true;
    }

    /**
     * Mendapatkan data untuk chart berdasarkan tipe
     *
     * @param string $type (status, priority, assignee)
     * @return array
     */
    public function getChartData($type)
    {
        try {
            $result = [];

            switch ($type) {
                case 'status':
                    $statuses = DB::table('todos')
                        ->select('status', DB::raw('count(*) as total'))
                        ->whereNull('deleted_at')
                        ->groupBy('status')
                        ->get();

                    $statusSummary = [];
                    foreach ($statuses as $status) {
                        $statusSummary[$status->status] = $status->total;
                    }

                    $result['status_summary'] = $statusSummary;
                    break;

                case 'priority':
                    $priorities = DB::table('todos')
                        ->select('priority', DB::raw('count(*) as total'))
                        ->whereNull('deleted_at')
                        ->groupBy('priority')
                        ->get();

                    $prioritySummary = [];
                    foreach ($priorities as $priority) {
                        $prioritySummary[$priority->priority] = $priority->total;
                    }

                    $result['priority_summary'] = $prioritySummary;
                    break;

                case 'assignee':
                    $assigneeTotals = DB::table('todos')
                        ->select(
                            'assignee',
                            DB::raw('COUNT(*) as total_todos'),
                            DB::raw('SUM(CASE WHEN status = \'pending\' THEN 1 ELSE 0 END) as total_pending_todos')
                        )
                        ->whereNull('deleted_at')
                        ->whereNotNull('assignee')
                        ->where('assignee', '!=', '')
                        ->groupBy('assignee')
                        ->get();

                    // Query terpisah untuk time tracked pada completed todos
                    $assigneeTimeTracked = DB::table('todos')
                        ->select(
                            'assignee',
                            DB::raw('SUM(time_tracked) as total_timetracked_completed_todos')
                        )
                        ->whereNull('deleted_at')
                        ->whereNotNull('assignee')
                        ->where('assignee', '!=', '')
                        ->where('status', 'completed')
                        ->groupBy('assignee')
                        ->get()
                        ->keyBy('assignee');

                    // Memproses hasil
                    $assigneeSummary = [];
                    foreach ($assigneeTotals as $item) {
                        $assignee = $item->assignee;
                        $timeTracked = 0;

                        // Ambil time tracked jika ada
                        if (isset($assigneeTimeTracked[$assignee])) {
                            $timeTracked = (float)$assigneeTimeTracked[$assignee]->total_timetracked_completed_todos;
                        }

                        $assigneeSummary[$assignee] = [
                            'total_todos' => $item->total_todos,
                            'total_pending_todos' => $item->total_pending_todos,
                            'total_timetracked_completed_todos' => $timeTracked
                        ];
                    }

                    $result['assignee_summary'] = $assigneeSummary;
                    break;

                default:
                    throw new \Exception('Tipe chart tidak valid. Gunakan: status, priority, atau assignee');
            }

            return $result;
        } catch (\Exception $e) {
            Log::error('Chart data error: ' . $e->getMessage());
            throw $e;
        }
    }
}
