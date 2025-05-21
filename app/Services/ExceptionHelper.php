<?php

namespace App\Services;

class ExceptionHelper {

    public static function clientExceptionHandler($e){
        $response = $e->getResponse();

        if(!$response){
            throw new \Exception($e->getMessage(), 422);
        }

        $responseBodyAsString = $response->getBody()->getContents();

        if($responseBodyAsString){
            $responseBody = json_decode($responseBodyAsString);
            throw new \Exception($responseBody->message, 422);
        }

        throw new \Exception($e->getMessage(), 422);
    }
}