<?php 

namespace App\Helpers;

use Illuminate\Http\Resources\Json\JsonResource;
use Symfony\Component\HttpFoundation\Response;

class ApiResponse
{
    public static function success(
        JSONResource|array|null $data = null,
        string $message = 'Success',
        int $status = Response::HTTP_OK
    ){
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $data
        ], $status);
    }

    public static function error(
        string $message,
        int $status = Response::HTTP_BAD_REQUEST,
        array $errors = []
    ){
        return response()->json([
            'success' => true,
            'message' => $message,
            'error' => $errors,
        ], $status);
    }
}