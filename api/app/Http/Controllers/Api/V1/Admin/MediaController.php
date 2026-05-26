<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Api\ApiController;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MediaController extends ApiController
{
    public function presign(Request $request): JsonResponse
    {
        $request->validate([
            'filename' => ['required', 'string'],
            'mime_type' => ['required', 'string'],
        ]);

        return $this->success([
            'upload_url' => 'https://storage.stub/upload',
            'path' => 'uploads/'.$request->string('filename'),
            ...$this->stub(),
        ]);
    }
}
