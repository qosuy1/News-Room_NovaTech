<?php

namespace App\Helper\V1;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\ResourceCollection;

class ApiResponse
{
    /**
     * Send a success response
     *
     * @param  mixed  $data
     * @return JsonResponse
     */
    public static function success($data = null, string $message = 'Success', int $code = 200)
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $data,
        ], $code);
    }

    /**
     * Send a paginated success response
     *
     * @param  ResourceCollection  $resource
     * @return JsonResponse
     */
    public static function paginated($resource, string $message = 'Success', int $code = 200)
    {
        $response = $resource->response()->getData(true);

        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $response['data'] ?? [],
            'links' => $response['links'] ?? [],
            'meta' => $response['meta'] ?? [],
        ], $code);
    }

    /**
     * Send an error response
     *
     * @param  mixed  $errors
     * @return JsonResponse
     */
    public static function error(string $message = 'Error', int $code = 400, $errors = null)
    {
        $response = [
            'success' => false,
            'message' => $message,
        ];

        if ($errors !== null) {
            $response['errors'] = $errors;
        }

        return response()->json($response, $code);
    }

    /**
     * Send a not found response
     *
     * @return JsonResponse
     */
    public static function notFound(string $message = 'Resource not found')
    {
        return self::error($message, 404);
    }

    /**
     * Send a validation error response
     *
     * @return JsonResponse
     */
    public static function validationError(array $errors, string $message = 'Validation failed')
    {
        return self::error($message, 422, $errors);
    }

    /**
     * Send an unauthorized response
     *
     * @return JsonResponse
     */
    public static function unauthorized(string $message = 'Unauthorized')
    {
        return self::error($message, 401);
    }

    /**
     * Send a forbidden response
     *
     * @return JsonResponse
     */
    public static function forbidden(string $message = 'Forbidden')
    {
        return self::error($message, 403);
    }

    /**
     * Send a created response
     *
     * @param  mixed  $data
     * @return JsonResponse
     */
    public static function created($data = null, string $message = 'Created successfully')
    {
        return self::success($data, $message, 201);
    }

    /**
     * Send a server error response
     *
     * @return JsonResponse
     */
    public static function serverError(string $message = 'Internal server error')
    {
        return self::error($message, 500);
    }
}
