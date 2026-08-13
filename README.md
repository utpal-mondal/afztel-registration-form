# A Unique Tel — Customer / Supplier registration form

A seven-step registration wizard built with Laravel and plain CSS. **No Tailwind, no npm,
no build step.** On submit it stores the application, e-mails a confirmation to the
applicant, and e-mails the full record plus uploaded documents to the admin inbox.

## Files

```
public/css/registration.css                 ← all styling, hand-written CSS
routes/web.php
app/Http/Controllers/RegistrationController.php
app/Http/Requests/StoreRegistrationRequest.php
app/Models/Registration.php
app/Mail/RegistrationSubmitted.php          → applicant confirmation
app/Mail/RegistrationReceived.php           → admin copy (with attachments)
config/registration.php                     → admin address + company letterhead
database/migrations/..._create_registrations_table.php
resources/views/layouts/app.blade.php
resources/views/components/field.blade.php  → <x-field> input partial
resources/views/registration/create.blade.php
resources/views/registration/success.blade.php
resources/views/emails/registration-applicant.blade.php
resources/views/emails/registration-admin.blade.php
```

## Install

1. Copy the files into a Laravel 10 / 11 / 12 app, keeping the same paths.

2. Add to `.env`:

```dotenv
REGISTRATION_ADMIN_EMAIL=info@auniquetel.pt
# comma separated for several recipients:
# REGISTRATION_ADMIN_EMAIL=info@auniquetel.pt,compliance@auniquetel.pt

MAIL_MAILER=smtp
MAIL_HOST=smtp.yourprovider.com
MAIL_PORT=587
MAIL_USERNAME=
MAIL_PASSWORD=
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS="no-reply@auniquetel.pt"
MAIL_FROM_NAME="A Unique Tel"
```

3. Run:

```bash
php artisan migrate
php artisan serve
```

4. Open `http://localhost:8000/register`.

That's it — no `npm install`, no `npm run build`. The stylesheet is loaded with
`asset('css/registration.css')` straight from `public/`.

## Styling

Everything lives in one file, `public/css/registration.css`, driven by CSS variables at
the top:

```css
:root {
    --navy: #12294A;   /* headings, buttons, active step */
    --gold: #C8A24A;   /* accents, completed steps, submit button */
    --page: #F4F7FA;   /* page background */
    --line: #E3E8EF;   /* borders */
}
```

Change those and the whole page re-skins.

Layout: sticky navbar, centred hero, a horizontal seven-dot stepper, one card holding the
form, and a navy footer. Fields sit on a 12-column grid (`col-4`, `col-6`, `col-8`,
`col-12`) that stacks full width under 640px. The stepper scrolls sideways on narrow
screens rather than wrapping.

If you edit the CSS and the browser serves a stale copy, bump the `?v=2` query string on
the `<link>` tag in `layouts/app.blade.php`.

## Error messages

Every field's wording lives in one place — `config/registration.php`, under
`field_messages`:

```php
'iban' => [
    'label'    => 'IBAN',
    'required' => 'Enter the IBAN for your settlement account.',
    'invalid'  => 'An IBAN is 15 to 34 characters: two country letters, then digits…',
],
```

`StoreRegistrationRequest` builds its `messages()` and `attributes()` from that array,
and the `<x-field>` component prints the same strings as `data-required` /
`data-invalid` attributes for the inline JS. So the browser and the server always say
the same thing, and you change copy in exactly one file.

Behaviour:

- A field is checked when you leave it, not while you type — mistakes are pointed out
  once, then cleared as soon as you correct them.
- Valid, filled fields get a small green tick so you can see what's already done.
- Radio sets and the declaration checkbox render their message in a dedicated slot below
  the choices, not squeezed inside a card.
- A failed server-side submit lists every problem at the top of the card and reopens the
  step containing the first one.

## Notes

- **Uploads** go to the private `local` disk under `storage/app/registrations/YYYY/MM`,
  never `public`. They are attached to the admin mail only.
- **Mail failures are logged, not fatal** — the application is saved first, so an SMTP
  outage never loses a submission. Check `storage/logs/laravel.log`.
- **Queueing**: both mailables use `Queueable`. Swap `->send(...)` for `->queue(...)` in
  `RegistrationController::sendMails()` and run `php artisan queue:work` if you want the
  redirect to be instant.
- **Spam control**: a hidden honeypot field plus `throttle:6,1` on the POST route.
- **Validation** runs twice — in the browser per step (vanilla JS, no framework), and
  again server-side in `StoreRegistrationRequest`. If the server rejects anything, the
  wizard reopens on the step that failed.
- The IBAN is masked in the applicant's copy and shown in full only to the admin.
