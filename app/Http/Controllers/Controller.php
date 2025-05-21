<?php

namespace App\Http\Controllers;

use App\Services\ResponseService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;

class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    public function responseWrapper($data = null)
    {
        return new ResponseService($data);
    }
    /**
     * Send Response Success
     *
     * @param  array|object $data
     * @param  string|array $message
     * @param  int $statusCode
     * @return \Illuminate\Http\JsonResponse
     */
    protected function sendSuccess($data = null, $message = null, $statusCode = null)
    {
        $data = $this->responseWrapper($data)->success($message, $statusCode);

        return response()->json($data, $data->code);
    }

    /**
     * Send Response Error
     *
     * @param  array|object $data
     * @param  string|array $message
     * @param  int $statusCode
     * @return \Illuminate\Http\JsonResponse
     */
    protected function sendError($data = null, $message = null, $statusCode = null)
    {
        $data = $this->responseWrapper($data)->error($message, $statusCode);

        return response()->json($data, $data->code);
    }
}