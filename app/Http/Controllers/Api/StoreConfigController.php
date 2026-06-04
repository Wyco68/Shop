<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\SupportContactResource;
use App\Services\SupportContactService;
use Illuminate\Http\JsonResponse;

class StoreConfigController extends Controller
{
    public function __construct(
        private readonly SupportContactService $contacts,
    ) {}

    public function supportContacts(): JsonResponse
    {
        $contacts = $this->contacts->enabled();

        return response()->json([
            'contacts' => SupportContactResource::collection($contacts)->resolve(),
        ]);
    }
}
