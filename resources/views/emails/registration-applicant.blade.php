<!DOCTYPE html>
<html>
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width"></head>
<body style="margin:0;padding:0;background:#F4F7FA;font-family:Helvetica,Arial,sans-serif;color:#12294A;">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#F4F7FA;padding:28px 12px;">
<tr><td align="center">
    <table role="presentation" width="600" cellpadding="0" cellspacing="0" style="max-width:600px;background:#ffffff;border:1px solid #E3E8EF;border-radius:12px;overflow:hidden;">

        <tr><td style="background:#12294A;padding:26px 32px;">
            <p style="margin:0;font-size:11px;letter-spacing:3px;text-transform:uppercase;color:#E4CB92;">A Unique Tel</p>
            <h1 style="margin:10px 0 0;font-size:24px;font-weight:normal;color:#ffffff;">Registration received</h1>
        </td></tr>

        <tr><td style="padding:30px 32px;">
            <p style="margin:0 0 16px;font-size:15px;line-height:1.6;">Dear {{ $registration->signatory_name }},</p>

            <p style="margin:0 0 16px;font-size:15px;line-height:1.6;">
                Thank you for registering <strong>{{ $registration->company_name }}</strong> as a
                {{ $registration->company_type }} with A Unique Tel. Your application is now with our
                compliance desk and we will come back to you within two business days.
            </p>

            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin:22px 0;background:#F4F7FA;border:1px solid #E3E8EF;border-radius:8px;">
                <tr><td style="padding:16px 20px;">
                    <p style="margin:0;font-size:11px;letter-spacing:2px;text-transform:uppercase;color:#6B7A8D;">Your reference</p>
                    <p style="margin:6px 0 0;font-size:18px;letter-spacing:2px;font-family:monospace;">{{ $registration->reference }}</p>
                </td></tr>
            </table>

            <p style="margin:0 0 10px;font-size:11px;letter-spacing:2px;text-transform:uppercase;color:#C8A24A;">Summary of your submission</p>

            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="font-size:14px;">
                @foreach (['Company details', 'Registration & business', 'Bank details'] as $section)
                    @php $rows = array_filter($registration->summarySections()[$section] ?? []); @endphp
                    @if ($rows)
                        <tr><td colspan="2" style="padding:14px 0 6px;font-size:11px;letter-spacing:2px;text-transform:uppercase;color:#6B7A8D;border-bottom:1px solid #E3E8EF;">{{ $section }}</td></tr>
                        @foreach ($rows as $label => $value)
                            <tr>
                                <td style="padding:7px 12px 7px 0;color:#6B7A8D;width:45%;">{{ $label }}</td>
                                <td style="padding:7px 0;font-weight:600;">{{ $label === 'IBAN' ? Str::mask($value, '•', 4, max(strlen($value) - 8, 0)) : $value }}</td>
                            </tr>
                        @endforeach
                    @endif
                @endforeach
            </table>

            <p style="margin:24px 0 0;font-size:14px;line-height:1.6;color:#6B7A8D;">
                If any detail is wrong, reply to this e-mail with the correction and your reference number.
            </p>
        </td></tr>

        <tr><td style="background:#0C1D36;padding:20px 32px;color:#9FA9B8;font-size:12px;line-height:1.6;">
            {{ config('registration.company.name') }} — NIPC {{ config('registration.company.nipc') }}<br>
            {{ config('registration.company.address') }}<br>
            {{ config('registration.company.region') }}
        </td></tr>
    </table>
</td></tr>
</table>
</body>
</html>
