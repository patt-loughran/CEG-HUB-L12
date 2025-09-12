<?php

namespace App\Helpers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Exception;

class ErrorLogger
{
    /**
     * Log comprehensive error information and re-throw the exception
     *
     * @param Exception $exception
     * @param string $context Description of what was happening when error occurred
     * @param Request|null $request Optional request object for additional context
     * @param array $additionalData Any extra data to include in the log
     * @throws Exception
     */
    public static function logAndRethrow(
        Exception $exception, 
        string $context = 'An error occurred', 
        ?Request $request = null, 
        array $additionalData = []
    ): void {
        $logData = [
            'message' => $exception->getMessage(),
            'code' => $exception->getCode(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'trace' => $exception->getTraceAsString(),
            'exception_class' => get_class($exception),
        ];

        // Add request context if available
        if ($request) {
            $logData['request'] = [
                'url' => $request->fullUrl(),
                'method' => $request->method(),
                'data' => $request->all(),
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'headers' => $request->headers->all(),
            ];
        }

        // Add user context if authenticated
        if (Auth::check()) {
            $logData['user'] = [
                'id' => Auth::id(),
                'email' => Auth::user()->email ?? null,
            ];
        }

        // Add any additional context data
        if (!empty($additionalData)) {
            $logData['additional_data'] = $additionalData;
        }

        Log::error($context, $logData);

        throw $exception;
    }

    /**
     * Log error without re-throwing (for when you want to handle gracefully)
     *
     * @param Exception $exception
     * @param string $context
     * @param Request|null $request
     * @param array $additionalData
     */
    public static function logOnly(
        Exception $exception, 
        string $context = 'An error occurred', 
        ?Request $request = null, 
        array $additionalData = []
    ): void {
        try {
            static::logAndRethrow($exception, $context, $request, $additionalData);
        } catch (Exception $e) {
            // Swallow the re-thrown exception since we only want to log
        }
    }
}