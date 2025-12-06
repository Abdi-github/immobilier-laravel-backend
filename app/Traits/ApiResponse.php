<?php

declare(strict_types=1);

namespace App\Traits;

use Illuminate\Http\JsonResponse;
use Illuminate\Pagination\CursorPaginator;
use Illuminate\Pagination\LengthAwarePaginator;

trait ApiResponse
{
    protected function successResponse(
        mixed $data = null,
        string $message = 'Success',
        int $statusCode = 200,
    ): JsonResponse {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $data,
        ], $statusCode);
    }

    protected function paginatedResponse(
        LengthAwarePaginator $paginator,
        string $message = 'Success',
        int $statusCode = 200,
    ): JsonResponse {
        $page = $paginator->currentPage();
        $totalPages = $paginator->lastPage();

        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $paginator->items(),
            'meta' => [
                'page' => $page,
                'limit' => $paginator->perPage(),
                'total' => $paginator->total(),
                'totalPages' => $totalPages,
                'hasNextPage' => $page < $totalPages,
                'hasPrevPage' => $page > 1,
            ],
        ], $statusCode);
    }

    protected function cursorPaginatedResponse(
        CursorPaginator $paginator,
        string $message = 'Success',
        int $statusCode = 200,
    ): JsonResponse {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $paginator->items(),
            'meta' => [
                'hasNextPage' => $paginator->hasMorePages(),
                'hasPrevPage' => $paginator->previousCursor() !== null,
                'nextCursor' => $paginator->nextCursor()?->encode(),
                'prevCursor' => $paginator->previousCursor()?->encode(),
            ],
        ], $statusCode);
    }

    protected function errorResponse(
        string $message = 'Error',
        int $statusCode = 400,
        array $errors = [],
        ?string $code = null,
    ): JsonResponse {
        $response = [
            'success' => false,
            'message' => $message,
        ];

        if (! empty($errors)) {
            $response['errors'] = $errors;
        }

        if ($code !== null) {
            $response['code'] = $code;
        }

        return response()->json($response, $statusCode);
    }

    protected function notFoundResponse(string $message = 'Resource not found'): JsonResponse
    {
        return $this->errorResponse($message, 404);
    }

    protected function unauthorizedResponse(string $message = 'Unauthorized'): JsonResponse
    {
        return $this->errorResponse($message, 401);
    }

    protected function forbiddenResponse(string $message = 'Forbidden'): JsonResponse
    {
        return $this->errorResponse($message, 403);
    }

    protected function validationErrorResponse(array $errors, string $message = 'Validation Error'): JsonResponse
    {
        return $this->errorResponse($message, 422, $errors);
    }

    protected function createdResponse(mixed $data = null, string $message = 'Created successfully'): JsonResponse
    {
        return $this->successResponse($data, $message, 201);
    }

    protected function noContentResponse(): JsonResponse
    {
        return response()->json(null, 204);
    }
}
