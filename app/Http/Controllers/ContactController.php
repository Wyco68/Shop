<?php

namespace App\Http\Controllers;

use App\Services\SupportContactService;

class ContactController extends Controller
{
    public function __construct(
        private readonly SupportContactService $contacts,
    ) {}

    public function index()
    {
        return view('contact', [
            'supportContacts' => $this->contacts->enabled(),
        ]);
    }
}
