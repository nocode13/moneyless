<?php

namespace App\Exceptions;

use Illuminate\Auth\AuthenticationException;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Throwable;

class ApiExceptionHandler
{
    public function handle(Throwable $e): JsonResponse
    {
        if ($e instanceof ApiException || $e instanceof HttpException) {
            return $this->respond($e->getMessage(), $e->getStatusCode());
        }

        if ($e instanceof AuthenticationException) {
            return $this->respond($e->getMessage(), 401);
        }

        if ($e instanceof ValidationException) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'code' => 422,
                'errors' => $e->errors(),
            ], 422);
        }

        report($e);
        return $this->respond(
            config('app.debug') ? $e->getMessage() : 'Internal error',
            500
        );
    }

    private function respond(string $message, int $status): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $message,
            'code' => $status,
        ], $status);
    }
}
