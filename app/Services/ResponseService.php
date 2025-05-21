<?php

namespace App\Services;

use Illuminate\Pagination\LengthAwarePaginator;

class ResponseService
{
    private $data;

    private $message;

    private $success;

    // private $responseCode;

    public function __construct($data = null)
    {
        $this->data = $data;
    }

    public function success($message = null, $responseCode = null)
    {
        $message = (empty($message)) ? 'success' : $message;

        // set the message
        $this->setMessage($message);

        // set response code
        $this->setResponseCode($responseCode);

        $this->success = true;

        return (object) $this->responseWrapper();
    }

    public function error($message = null, $responseCode = null)
    {
        $message = (empty($message)) ? 'error' : $message;

        // set the message
        $this->setMessage($message);

        // set response code
        $this->setResponseCode($responseCode);

        $this->success = false;

        return (object) $this->responseWrapper();
    }

    private function responseWrapper()
    {
        // handle empty data
        $data = (empty($this->data)) ? null : $this->data;

        $response = [
            'code'      => http_response_code(),
            'success'   => $this->success,
            'message'   => $this->message,
        ];
        if ($data instanceof LengthAwarePaginator) {
            $data = $data->toArray();
            $response['data'] = $data['data'];
            $response['meta']['current_page'] = $data['current_page'];
            $response['meta']['from'] = $data['from'];
            $response['meta']['last_page'] = $data['last_page'];
            $response['meta']['next_page_url'] = $data['next_page_url'];
            $response['meta']['path'] = $data['path'];
            $response['meta']['per_page'] = $data['per_page'];
            $response['meta']['prev_page_url'] = $data['prev_page_url'];
            $response['meta']['to'] = $data['to'];
            $response['meta']['total'] = $data['total'];
        } else {
            $response['data'] = $data;
        }

        return $response;
    }

    private function setMessage($message)
    {
        // check if message constructed in array format (multiple message)
        if (is_array($message)) {
            $extract = array_values($message);
            $this->message = $extract[0];
        } else {
            $this->message = $message;
        }
    }

    private function setResponseCode($responseCode)
    {
        if (!empty($responseCode) && is_numeric($responseCode))
            http_response_code($responseCode);
    }
}