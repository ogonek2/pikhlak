<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

abstract class ApiController extends Controller
{
    protected function success(mixed $data = null, array $meta = [], int $status = 200): JsonResponse
    {
        $payload = ['data' => $data];

        if ($meta !== []) {
            $payload['meta'] = $meta;
        }

        return response()->json($payload, $status);
    }

    protected function error(string $message, string $code = 'error', int $status = 400, ?string $field = null): JsonResponse
    {
        $error = ['code' => $code, 'message' => $message];

        if ($field !== null) {
            $error['field'] = $field;
        }

        return response()->json(['errors' => [$error]], $status);
    }

    protected function paginatedMeta(int $total, int $page = 1, int $perPage = 20): array
    {
        return [
            'current_page' => $page,
            'per_page' => $perPage,
            'total' => $total,
            'last_page' => (int) max(1, ceil($total / $perPage)),
        ];
    }

    protected function stub(string $message = 'Not implemented yet — stub response'): array
    {
        return [
            'stub' => true,
            'message' => $message,
        ];
    }
}
