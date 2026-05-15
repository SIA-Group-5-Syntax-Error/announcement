<?php

// Builds consistent JSON error responses: { "error": { "code", "message" } }.

namespace App\Support\Gateway;

use Symfony\Component\HttpFoundation\Response;

final class ApiError
{
    public static function json(string $code, string $message, int $status): Response
    {
        return response()->json([
            'error' => [
                'code' => $code,
                'message' => $message,
            ],
        ], $status);
    }
}
