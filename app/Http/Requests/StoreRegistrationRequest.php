<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreRegistrationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $doc   = ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png,webp', 'max:5120'];
        $phone = ['regex:/^[0-9+()\-.\s]{6,25}$/'];

        return [
            // Honeypot — must stay empty.
            'website_url' => ['nullable', 'size:0'],

            'company_type' => ['required', Rule::in(['supplier', 'customer'])],

            'company_name' => ['required', 'string', 'max:255'],
            'address_1'    => ['required', 'string', 'max:255'],
            'address_2'    => ['nullable', 'string', 'max:255'],
            'post_code'    => ['required', 'string', 'max:32'],
            'country'      => ['required', 'string', 'max:120'],
            'phone_number' => array_merge(['required', 'string'], $phone),
            'email'        => ['required', 'email:rfc,dns', 'max:255'],
            'web_address'  => ['nullable', 'url', 'max:255'],

            'registration_number'    => ['required', 'string', 'max:80'],
            'vat_number'             => ['required', 'string', 'regex:/^[A-Za-z0-9\s\-]{8,20}$/'],
            'incorporation_date'     => ['required', 'date', 'before_or_equal:today'],
            'main_business_activity' => ['required', 'string', 'max:255'],
            'hear_about_us'          => ['nullable', 'string', 'max:255'],

            'director_name'     => ['required', 'string', 'max:255'],
            'director_address'  => ['required', 'string', 'max:255'],
            'director_position' => ['required', 'string', 'max:120'],

            'trader_name'     => ['required', 'string', 'max:255'],
            'trader_mobile'   => array_merge(['required', 'string'], $phone),
            'trader_position' => ['required', 'string', 'max:120'],

            'delivery_company_name'  => ['required', 'string', 'max:255'],
            'delivery_address'       => ['required', 'string', 'max:255'],
            'delivery_city_country'  => ['required', 'string', 'max:255'],
            'delivery_postal_code'   => ['required', 'string', 'max:32'],
            'delivery_contact_phone' => ['required', 'string', 'max:120'],

            'delivery2_company_name'  => ['nullable', 'string', 'max:255'],
            'delivery2_address'       => ['nullable', 'string', 'max:255'],
            'delivery2_city_country'  => ['nullable', 'string', 'max:255'],
            'delivery2_postal_code'   => ['nullable', 'string', 'max:32'],
            'delivery2_contact_phone' => ['nullable', 'string', 'max:120'],

            'bank_name'    => ['required', 'string', 'max:255'],
            'account_name' => ['required', 'string', 'max:255'],
            'iban'         => ['required', 'string', 'regex:/^[A-Za-z]{2}[0-9A-Za-z\s]{13,32}$/'],
            'sort_code'    => ['nullable', 'string', 'regex:/^[0-9\-\s]{6,10}$/'],
            'bic_swift'    => ['required', 'string', 'regex:/^[A-Za-z0-9]{8}([A-Za-z0-9]{3})?$/'],

            'registration_doc' => $doc,
            'vat_certificate'  => $doc,
            'signature'        => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:5120'],

            'signatory_name'        => ['required', 'string', 'max:255'],
            'signatory_designation' => ['required', 'string', 'max:120'],
            'signed_date'           => ['required', 'date'],
            'signed_place'          => ['required', 'string', 'max:255'],
            'terms_accepted'        => ['accepted'],
        ];
    }

    /**
     * Friendly names, used by any message that falls back to the default text.
     */
    public function attributes(): array
    {
        $names = [];

        foreach (config('registration.field_messages') as $field => $copy) {
            if (! empty($copy['label'])) {
                $names[$field] = $copy['label'];
            }
        }

        return $names + ['website_url' => 'this field'];
    }

    /**
     * Per-field wording comes from config/registration.php so the browser
     * and the server never disagree about what went wrong.
     */
    public function messages(): array
    {
        $messages = [];

        foreach (config('registration.field_messages') as $field => $copy) {
            if (! empty($copy['required'])) {
                $messages["{$field}.required"] = $copy['required'];
                $messages["{$field}.accepted"] = $copy['required'];
                $messages["{$field}.in"]       = $copy['required'];
            }

            if (! empty($copy['invalid'])) {
                foreach (['regex', 'email', 'url', 'date', 'before_or_equal'] as $rule) {
                    $messages["{$field}.{$rule}"] = $copy['invalid'];
                }
            }
        }

        return $messages + [
            'string'      => 'The :attribute must be text.',
            'max.string'  => 'The :attribute must be :max characters or fewer.',
            'max.file'    => 'The :attribute must be smaller than 5 MB.',
            'mimes'       => 'Upload the :attribute as a PDF, JPG, PNG or WEBP file.',
            'file'        => 'The :attribute must be an uploaded file.',
            'date'        => 'Enter the :attribute as a valid date.',
            'website_url.size' => 'This submission looks automated. Leave the hidden field empty.',
        ];
    }
}
