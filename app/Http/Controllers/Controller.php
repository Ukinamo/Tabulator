<?php

namespace App\Http\Controllers;

abstract class Controller
{
    /**
     * Standard JSON envelope response helper.
     *
     * @param  mixed  $data
     * @param  string|null  $message
     * @param  int  $status
     */
    protected function respond(mixed $data = null, ?string $message = null, int $status = 200)
    {
        return response()->json([
            'success' => $status >= 200 && $status < 300,
            'data' => $data,
            'message' => $message ?? '',
        ], $status);
    }

    /**
     * Standard JSON error response helper with optional validation errors.
     *
     * @param  array<string, mixed>|null  $errors
     */
    protected function error(string $message, int $status = 400, ?array $errors = null)
    {
        $payload = [
            'success' => false,
            'data' => null,
            'message' => $message,
        ];

        if ($errors !== null) {
            $payload['errors'] = $errors;
        }

        return response()->json($payload, $status);
    }
}
