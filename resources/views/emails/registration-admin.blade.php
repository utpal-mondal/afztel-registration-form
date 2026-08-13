<!DOCTYPE html>
<html>
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width"></head>
<body style="margin:0;padding:0;background:#F4F7FA;font-family:Helvetica,Arial,sans-serif;color:#12294A;">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#F4F7FA;padding:28px 12px;">
<tr><td align="center">
    <table role="presentation" width="640" cellpadding="0" cellspacing="0" style="max-width:640px;background:#ffffff;border:1px solid #E3E8EF;border-radius:12px;overflow:hidden;">

        <tr><td style="background:#12294A;padding:24px 30px;">
            <p style="margin:0;font-size:11px;letter-spacing:3px;text-transform:uppercase;color:#E4CB92;">New {{ $registration->company_type }} application</p>
            <h1 style="margin:8px 0 0;font-size:22px;font-weight:normal;color:#ffffff;">{{ $registration->company_name }}</h1>
            <p style="margin:8px 0 0;font-family:monospace;font-size:13px;color:#9FA9B8;">
                {{ $registration->reference }} · {{ $registration->created_at->format('d/m/Y H:i') }} · IP {{ $registration->submitted_ip }}
            </p>
        </td></tr>

        <tr><td style="padding:26px 30px;">
            @foreach ($registration->summarySections() as $section => $rows)
                @php $rows = array_filter($rows, fn ($v) => filled($v)); @endphp
                @if ($rows)
                    <p style="margin:{{ $loop->first ? '0' : '26px' }} 0 8px;font-size:11px;letter-spacing:2px;text-transform:uppercase;color:#C8A24A;">{{ $section }}</p>
                    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="font-size:14px;border-collapse:collapse;">
                        @foreach ($rows as $label => $value)
                            <tr style="border-bottom:1px solid #EFEBE0;">
                                <td style="padding:8px 12px 8px 0;color:#6B7A8D;width:40%;vertical-align:top;">{{ $label }}</td>
                                <td style="padding:8px 0;font-weight:600;">{{ $value }}</td>
                            </tr>
                        @endforeach
                    </table>
                @endif
            @endforeach

            @php
                $docs = array_filter([
                    'Incorporation certificate' => $registration->registration_doc_path,
                    'VAT certificate'           => $registration->vat_certificate_path,
                    'Signed & stamped page'     => $registration->signature_path,
                ]);
            @endphp

            <p style="margin:26px 0 8px;font-size:11px;letter-spacing:2px;text-transform:uppercase;color:#C8A24A;">Attachments</p>
            @if ($docs)
                <ul style="margin:0;padding-left:18px;font-size:14px;line-height:1.8;">
                    @foreach ($docs as $label => $path)
                        <li>{{ $label }} — <span style="font-family:monospace;font-size:12px;color:#6B7A8D;">{{ basename($path) }}</span></li>
                    @endforeach
                </ul>
            @else
                <p style="margin:0;font-size:14px;color:#6B7A8D;">No documents uploaded. Request them before opening the account.</p>
            @endif

            <p style="margin:26px 0 0;font-size:14px;">
                Reply directly to this message to reach {{ $registration->trader_name }} at
                <a href="mailto:{{ $registration->email }}" style="color:#12294A;">{{ $registration->email }}</a>.
            </p>
        </td></tr>

        <tr><td style="background:#0C1D36;padding:18px 30px;color:#9FA9B8;font-size:12px;">
            Sent automatically by the A Unique Tel registration form.
        </td></tr>
    </table>
</td></tr>
</table>
</body>
</html>
