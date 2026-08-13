<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreRegistrationRequest;
use App\Mail\RegistrationReceived;
use App\Mail\RegistrationSubmitted;
use App\Models\Registration;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class RegistrationController extends Controller
{
    public function create()
    {
        return view('registration.create');
    }

    public function store(StoreRegistrationRequest $request)
    {
        $data = $request->safe()->except(['website_url', 'registration_doc', 'vat_certificate', 'signature']);

        // Store uploads on the private disk — bank details and IDs should not be public.
        foreach ([
            'registration_doc' => 'registration_doc_path',
            'vat_certificate'  => 'vat_certificate_path',
            'signature'        => 'signature_path',
        ] as $input => $column) {
            if ($request->hasFile($input)) {
                $data[$column] = $request->file($input)->store('registrations/' . date('Y/m'), 'local');
            }
        }

        $data['terms_accepted'] = true;
        $data['submitted_ip']   = $request->ip();
        $data['reference']      = 'AUT-' . date('Y') . '-' . strtoupper(bin2hex(random_bytes(3)));

        $registration = Registration::create($data);

        $this->sendMails($registration);

        return redirect()
            ->route('register.success')
            ->with('reference', $registration->reference)
            ->with('email', $registration->email);
    }

    public function success(Request $request)
    {
        if (! session('reference')) {
            return redirect()->route('register.create');
        }

        return view('registration.success', [
            'reference' => session('reference'),
            'email'     => session('email'),
        ]);
    }

    /**
     * Confirmation to the applicant + full copy to the admin inbox.
     * Wrapped so a mail outage never loses a saved application.
     */
    protected function sendMails(Registration $registration): void
    {
        try {
            Mail::to($registration->email)->send(new RegistrationSubmitted($registration));
        } catch (\Throwable $e) {
            Log::error('Applicant mail failed for ' . $registration->reference . ': ' . $e->getMessage());
        }

        try {
            Mail::to(config('registration.admin_email'))
                ->send(new RegistrationReceived($registration));
        } catch (\Throwable $e) {
            Log::error('Admin mail failed for ' . $registration->reference . ': ' . $e->getMessage());
        }
    }
}
