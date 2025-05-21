<?php

namespace App\Http\Controllers\Todo;

use App\Http\Controllers\ApiController;
use App\Http\Requests\Todo\StoreTodoRequest;
use App\Http\Requests\Todo\UpdateTodoRequest;
use App\Services\Todo\TodoService;
use Illuminate\Http\Request;

class TodoController extends ApiController
{
    protected TodoService $service;

    /**
     * @param TodoService $service
     * @param Request $request
     */
    public function __construct(TodoService $service, Request $request)
    {
        $this->service = $service;
        parent::__construct($request);
    }

    /**
     * Display a listing of the resource.
     *
     * @return JsonResponse
     */
    public function index(Request $request)
    {
        $todos = $this->service->dataTable($request);
        return $this->sendSuccess($todos, null, 200);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param StoreTodoRequest $request
     * @return JsonResponse
     */
    public function store(StoreTodoRequest $request)
    {
        $todo = $this->service->create($request);
        return $this->sendSuccess($todo, null, 201);
    }

    /**
     * Display the specified resource.
     *
     * @param String $id
     * @return JsonResponse
     */
    public function show(string $id)
    {
        $todo = $this->service->getById($id);
        return $this->sendSuccess($todo, null, 200);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param UpdateTodoRequest $request
     * @param String $id
     * @return JsonResponse
     */
    public function update(UpdateTodoRequest $request, string $id)
    {
        $todo = $this->service->update($id, $request);
        return $this->sendSuccess($todo, null, 200);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param String $id
     * @return JsonResponse
     */
    public function destroy(string $id)
    {
        $todo = $this->service->delete($id);
        return $this->sendSuccess($todo, null, 200);
    }

    /**
     * Export todos
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function export(Request $request)
    {
        try {
            $result = $this->service->exportData($request);

            return $this->sendSuccess([
                'file_url' => $result['file_url'],
                'filename' => $result['filename'],
                'file_type' => $result['file_type'] ?? 'xlsx'
            ], 'File ' . ($result['file_type'] ?? 'Excel') . ' berhasil dibuat', 200);

        } catch (\Exception $e) {
            return $this->sendError(null, $e->getMessage(), 500);
        }
    }

    /**
     * Get chart data based on type
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function chart(Request $request)
    {
        try {
            $type = $request->get('type', 'status');

            // Validasi tipe chart
            if (!in_array($type, ['status', 'priority', 'assignee'])) {
                return $this->sendError(null, 'Tipe chart tidak valid. Gunakan: status, priority, atau assignee', 400);
            }

            $chartData = $this->service->getChartData($type);

            return $this->sendSuccess($chartData, 'Data chart berhasil dimuat', 200);

        } catch (\Exception $e) {
            return $this->sendError(null, $e->getMessage(), 500);
        }
    }
}
