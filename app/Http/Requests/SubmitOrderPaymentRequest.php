<?php

namespace App\Http\Requests;

use App\Services\SecureUploadService;
use Illuminate\Foundation\Http\FormRequest;

class SubmitOrderPaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'phone_num' => ['required', 'string', 'max:20'],
            'address' => ['required', 'string', 'max:500'],
            'payment_proof' => [
                'required',
                'file',
                'max:'.SecureUploadService::MAX_PAYMENT_PROOF_KB,
                'mimetypes:'.implode(',', SecureUploadService::paymentProofMimes()),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'phone_num.required' => 'Please enter your phone number for delivery contact.',
            'address.required' => 'Please enter your shipping address.',
            'payment_proof.required' => 'Please upload a screenshot or photo of your payment receipt.',
        ];
    }
}
