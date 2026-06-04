<?php

namespace App\Http\Controllers\Admin;

use App\Enums\SupportContactType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreSupportContactRequest;
use App\Http\Requests\Admin\UpdateSupportContactRequest;
use App\Models\SupportContact;
use App\Services\SecureUploadService;
use App\Services\SupportContactService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class SupportSettingsController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        private readonly SupportContactService $contacts,
        private readonly SecureUploadService $uploads,
    ) {}

    public function index(): View
    {
        $this->authorize('viewAny', SupportContact::class);

        $supportContacts = SupportContact::query()->orderBy('sort_order')->orderBy('id')->get();
        $types = SupportContactType::cases();
        $editContact = request()->integer('edit')
            ? $supportContacts->firstWhere('id', request()->integer('edit'))
            : null;

        return view('admin.settings.support', compact('supportContacts', 'types', 'editContact'));
    }

    public function store(StoreSupportContactRequest $request): RedirectResponse
    {
        $this->authorize('create', SupportContact::class);

        $data = $request->contactAttributes();

        try {
            if ($request->hasFile('qr_image')) {
                $data['qr_path'] = $this->uploads->storeImage(
                    $request->file('qr_image'),
                    'support',
                    SecureUploadService::categoryIconMimes(),
                    SecureUploadService::MAX_QR_KB,
                );
            }
        } catch (\InvalidArgumentException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        SupportContact::create($data);
        $this->contacts->forgetCache();

        return redirect()->route('admin.settings.support.index')->with('success', 'Support contact added.');
    }

    public function update(UpdateSupportContactRequest $request, SupportContact $supportContact): RedirectResponse
    {
        $this->authorize('update', $supportContact);

        $data = $request->contactAttributes();

        try {
            if ($request->hasFile('qr_image')) {
                $this->uploads->deleteIfExists($supportContact->qr_path);
                $data['qr_path'] = $this->uploads->storeImage(
                    $request->file('qr_image'),
                    'support',
                    SecureUploadService::categoryIconMimes(),
                    SecureUploadService::MAX_QR_KB,
                );
            }
        } catch (\InvalidArgumentException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        $supportContact->update($data);
        $this->contacts->forgetCache();

        return redirect()->route('admin.settings.support.index')->with('success', 'Support contact updated.');
    }

    public function destroy(SupportContact $supportContact): RedirectResponse
    {
        $this->authorize('delete', $supportContact);

        $this->uploads->deleteIfExists($supportContact->qr_path);
        $supportContact->delete();
        $this->contacts->forgetCache();

        return redirect()->route('admin.settings.support.index')->with('success', 'Support contact removed.');
    }
}
