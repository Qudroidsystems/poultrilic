<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

/** Every answer: {"success": true, "message": ..., "data": ..., "meta": {...}} (the mobile app's contract). */
abstract class ApiController extends Controller
{
    protected function ok(mixed $data = null, ?string $message = null, array $meta = [], int $status = 200): JsonResponse
    {
        return response()->json(['success' => true, 'message' => $message, 'data' => $data, 'meta' => (object) $meta], $status);
    }

    protected function fail(string $message, int $status = 422, ?string $code = null, array $errors = []): JsonResponse
    {
        return response()->json(array_filter([
            'success' => false, 'message' => $message, 'code' => $code, 'errors' => $errors ?: null,
        ], fn ($v) => $v !== null), $status);
    }

    protected function authorizeTo(string $permission): void
    {
        abort_unless(request()->user()?->can($permission), 403, 'You are not allowed to do that.');
    }
}
