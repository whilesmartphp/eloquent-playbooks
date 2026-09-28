<?php

namespace Tests\Support;

use Illuminate\Http\JsonResponse;
use Whilesmart\Playbooks\Contracts\ResponseFormatter;

class BareFormatter implements ResponseFormatter
{
    public function success(mixed $data = null, int $status = 200): JsonResponse
    {
        return response()->json(['result' => $data], $status);
    }

    public function failure(string $message, int $status = 400, array $errors = []): JsonResponse
    {
        return response()->json(['problem' => $message], $status);
    }
}
