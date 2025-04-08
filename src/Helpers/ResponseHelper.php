<?php

namespace App\Helpers;

class ResponseHelper
{
    /**
     * Generate a success response.
     *
     * @param mixed $data
     * @param string $message
     * @param int $statusCode
     * @return array
     */
    public static function success($data = null, $message = 'Operation successful', $statusCode = 200)
    {
        return [
            'status' => 'success',
            'message' => $message,
            'data' => $data,
            'statusCode' => $statusCode,
        ];
    }

    /**
     * Generate an error response.
     *
     * @param string $message
     * @param int $statusCode
     * @param mixed $errors
     * @return array
     */
    public static function error($message = 'An error occurred', $statusCode = 400, $errors = null)
    {
        return [
            'status' => 'error',
            'message' => $message,
            'errors' => $errors,
            'statusCode' => $statusCode,
        ];
    }
}