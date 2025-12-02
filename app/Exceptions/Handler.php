<?php

namespace App\Exceptions;

use App\Helpers\ApiResponse;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Throwable;

class Handler extends ExceptionHandler
{
    public function render($request, Throwable $e)
    {
        // Force JSON responses for all API-like requests
        if ($request->expectsJson() || str_starts_with($request->path(), 'api/') || $request->is('api/*')) {

            if ($e instanceof NotFoundHttpException) {
                return ApiResponse::error('Route not found', [], 404);
            }

            if ($e instanceof MethodNotAllowedHttpException) {
                return ApiResponse::error('Method not allowed', [],405);
            }

            // Optional: generic fallback for all other exceptions in API
            // return ApiResponse::error('Server error', 500);
        }

        return parent::render($request, $e);
    }
}
