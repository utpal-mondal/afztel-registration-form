@extends('layouts.app')

@section('title', 'Register')

@php
    $steps = [
        ['title' => 'Company',     'head' => 'Company details',            'sub' => 'Enter the legal entity exactly as it appears on your incorporation record.'],
        ['title' => 'Registry',    'head' => 'Registration & business',    'sub' => 'These identifiers are checked against the VIES register before an account opens.'],
        ['title' => 'People',      'head' => 'Directors & trading contact','sub' => 'The trading contact is the person we call to confirm an order.'],
        ['title' => 'Delivery',    'head' => 'Delivery address',           'sub' => 'Goods and paperwork are shipped to this address.'],
        ['title' => 'Bank',        'head' => 'Bank details',               'sub' => 'Settlement account only. We never ask for card details or banking credentials.'],
        ['title' => 'Documents',   'head' => 'Supporting documents',       'sub' => 'PDF, JPG or PNG up to 5 MB each. You can send them later by e-mail.'],
        ['title' => 'Sign',        'head' => 'Declaration',                'sub' => 'Read the statement, then sign off on behalf of your company.'],
    ];
@endphp

@section('content')
<main class="wrap">

    <div class="hero">
        <span class="kicker">Customer / Supplier registration</span>
        <h1>Join our network</h1>
        <p>Become a registered partner in seven short steps.</p>
    </div>

    {{-- ── Stepper ─────────────────────────────────────────────── --}}
    <div class="stepper-shell">
        <ol class="stepper" id="stepper">
            @foreach ($steps as $i => $step)
                @if ($i > 0)
                    <li><span class="step-line" data-line="{{ $i }}"></span></li>
                @endif
                <li>
                    <button type="button" class="step-btn" data-goto="{{ $i }}">
                        <span class="step-num">{{ $i + 1 }}</span>
                        <span class="step-name">{{ $step['title'] }}</span>
                    </button>
                </li>
            @endforeach
        </ol>
    </div>

    {{-- ── Card ────────────────────────────────────────────────── --}}
    <form method="POST" action="{{ route('register.store') }}" enctype="multipart/form-data"
          id="regForm" class="card" novalidate>
        @csrf

        <div class="is-hidden" aria-hidden="true">
            <label>Leave this empty <input type="text" name="website_url" tabindex="-1" autocomplete="off"></label>
        </div>

        <div class="card-head">
            <span class="count"><span id="dStep">1</span> of 7</span>
            <h2 id="cardTitle">{{ $steps[0]['head'] }}</h2>
            <p id="cardSub">{{ $steps[0]['sub'] }}</p>
        </div>

        <div class="card-body">

            @if ($errors->any())
                <div class="alert" role="alert">
                    <span class="icon" aria-hidden="true">!</span>
                    <div>
                        <strong>{{ $errors->count() }} {{ Str::plural('field', $errors->count()) }} need{{ $errors->count() === 1 ? 's' : '' }} attention</strong>
                        <ul>
                            @foreach ($errors->all() as $message)
                                <li>{{ $message }}</li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            @endif

            {{-- 1 ── Company --}}
            <section class="step-panel is-active" data-step="0">
                <fieldset>
                    <legend class="field-label">Company type <span class="req">*</span></legend>
                    <div class="type-cards">
                        @foreach (['supplier' => 'We supply goods to A Unique Tel', 'customer' => 'We purchase goods from A Unique Tel'] as $val => $desc)
                            <label class="type-card {{ old('company_type') === $val ? 'is-checked' : '' }}">
                                <input type="radio" name="company_type" value="{{ $val }}" required
                                       data-required="{{ config('registration.field_messages.company_type.required') }}"
                                       @checked(old('company_type') === $val)>
                                <span class="radio"></span>
                                <span class="type-name">{{ $val }}</span>
                                <span class="type-desc">{{ $desc }}</span>
                            </label>
                        @endforeach
                    </div>
                    <div data-group-error="company_type">
                        @error('company_type')
                            <p class="group-error" role="alert"><span class="icon" aria-hidden="true">!</span>{{ $message }}</p>
                        @enderror
                    </div>
                </fieldset>

                <div class="grid">
                    <x-field name="company_name" label="Company name" required placeholder="Registered trading name" autocomplete="organization" />
                    <x-field name="address_1" label="Address 1" required placeholder="Street and number" autocomplete="address-line1" />
                    <x-field name="address_2" label="Address 2" placeholder="Unit, floor or building" autocomplete="address-line2" />
                    <x-field name="post_code" label="Post code" required span="col-4" placeholder="2735-229" autocomplete="postal-code" />
                    <x-field name="country" label="Country" required span="col-8" placeholder="Portugal" autocomplete="country-name" />
                    <x-field name="phone_number" label="Phone number" type="tel" required span="col-6" placeholder="+351 21 000 0000" inputmode="tel" autocomplete="tel" pattern="[0-9+()\-.\s]{6,25}" />
                    <x-field name="email" label="Mail address" type="email" required span="col-6"
                             placeholder="name@company.com" inputmode="email" autocomplete="email" help="Your confirmation copy is sent here." />
                    <x-field name="web_address" label="Web address" type="url" placeholder="https://www.company.com" inputmode="url" />
                </div>
            </section>

            {{-- 2 ── Registry --}}
            <section class="step-panel" data-step="1">
                <div class="grid">
                    <x-field name="registration_number" label="Registration number" required span="col-6" placeholder="510296211" />
                    <x-field name="vat_number" label="VAT number" required span="col-6" placeholder="PT510296211" pattern="[A-Za-z0-9\s\-]{8,20}" />
                    <x-field name="incorporation_date" label="Incorporation date" type="date" required span="col-6" />
                    <x-field name="main_business_activity" label="Main business activity" required span="col-6"
                             placeholder="Wholesale of consumer electronics" />
                    <x-field name="hear_about_us" label="How did you hear about A Unique Tel?" type="select"
                             :options="['Trade fair', 'Referral from a partner', 'Search engine', 'Social media', 'Existing relationship', 'Other']" />
                </div>
            </section>

            {{-- 3 ── People --}}
            <section class="step-panel" data-step="2">
                <p class="section-label">Directors / partners</p>
                <div class="grid">
                    <x-field name="director_name" label="Name" required span="col-6" placeholder="Full legal name" />
                    <x-field name="director_position" label="Position" required span="col-6" placeholder="Managing Director" />
                    <x-field name="director_address" label="Address" required />
                </div>

                <p class="section-label">Trader contact</p>
                <div class="grid">
                    <x-field name="trader_name" label="Name" required span="col-6" />
                    <x-field name="trader_mobile" label="Mobile number" type="tel" required span="col-6" placeholder="+351 910 000 000" inputmode="tel" pattern="[0-9+()\-.\s]{6,25}" />
                    <x-field name="trader_position" label="Position" required span="col-6" placeholder="Sales Manager" />
                </div>
            </section>

            {{-- 4 ── Delivery --}}
            <section class="step-panel" data-step="3">
                <div class="head-row" style="margin-bottom:1.25rem">
                    <p class="section-label" style="margin:0">Primary address</p>
                    <button type="button" id="copyCompany" class="btn-outline">Copy company address</button>
                </div>

                <div class="grid">
                    <x-field name="delivery_company_name" label="Company name" required />
                    <x-field name="delivery_address" label="Address" required />
                    <x-field name="delivery_city_country" label="City / country" required span="col-6" />
                    <x-field name="delivery_postal_code" label="Postal code" required span="col-6" />
                    <x-field name="delivery_contact_phone" label="Contact / phone" required placeholder="Name and phone number of the receiver" />
                </div>

                <label class="check-row dashed">
                    <input type="checkbox" id="toggleDelivery2" @checked(old('delivery2_company_name'))>
                    <span>Add a second delivery address</span>
                </label>

                <div class="grid {{ old('delivery2_company_name') ? '' : 'is-hidden' }}" id="delivery2" style="margin-top:1.25rem">
                    <x-field name="delivery2_company_name" label="Company name" />
                    <x-field name="delivery2_address" label="Address" />
                    <x-field name="delivery2_city_country" label="City / country" span="col-6" />
                    <x-field name="delivery2_postal_code" label="Postal code" span="col-6" />
                    <x-field name="delivery2_contact_phone" label="Contact / phone" />
                </div>
            </section>

            {{-- 5 ── Bank --}}
            <section class="step-panel" data-step="4">
                <div class="grid">
                    <x-field name="bank_name" label="Bank name" required span="col-6" placeholder="Banco Comercial Português" />
                    <x-field name="account_name" label="Account name" required span="col-6" placeholder="Name the account is held under" />
                    <x-field name="iban" label="IBAN" required placeholder="PT50 0002 0123 1234 5678 9015 4" pattern="[A-Za-z]{2}[0-9A-Za-z\s]{13,32}" />
                    <x-field name="sort_code" label="Sort code" span="col-6" placeholder="20-00-00" pattern="[0-9\-\s]{6,10}" help="UK accounts only — leave empty otherwise." />
                    <x-field name="bic_swift" label="BIC / SWIFT" required span="col-6" placeholder="BCOMPTPL" pattern="[A-Za-z0-9]{8}([A-Za-z0-9]{3})?" />
                </div>
            </section>

            {{-- 6 ── Documents --}}
            <section class="step-panel" data-step="5">
                <div class="grid">
                    <x-field name="registration_doc" label="Company incorporation certificate" type="file" />
                    <x-field name="vat_certificate" label="VAT certificate" type="file" />
                    <x-field name="signature" label="Signed and stamped page" type="file"
                             help="A scan or photo of your company stamp alongside the signature." />
                </div>
            </section>

            {{-- 7 ── Declaration --}}
            <section class="step-panel" data-step="6">
                <div class="note-box">
                    <p>
                        As authorised signatory for
                        <span class="fill" id="echoCompany">—</span>
                        with VAT number
                        <span class="fill" id="echoVat">—</span>,
                        referred to as ‘the company’:
                    </p>
                    <ul>
                        <li>‘The company’ will comply with all VAT regulations in both the country of registration and the country of delivery, where different, concerning goods purchased from or delivered to A Unique Tel.</li>
                        <li>‘The company’ will make all VAT declarations and applications concerning those goods as required by EU law and national law in both countries.</li>
                        <li>The information provided is true, updated and correct, and the company is registered and holds a valid VAT number.</li>
                    </ul>
                </div>

                <div class="grid">
                    <x-field name="signatory_name" label="Name" required span="col-6" placeholder="Full legal name" />
                    <x-field name="signatory_designation" label="Designation" required span="col-6" placeholder="Director" />
                    <x-field name="signed_date" label="Date" type="date" required span="col-6" :value="now()->toDateString()" />
                    <x-field name="signed_place" label="Place" required span="col-6" placeholder="Lisboa" />
                </div>

                <label class="check-row">
                    <input type="checkbox" name="terms_accepted" value="1" required
                           data-required="{{ config('registration.field_messages.terms_accepted.required') }}"
                           @checked(old('terms_accepted'))>
                    <span>I confirm the declaration above and accept A Unique Tel’s terms for the supply of goods.</span>
                </label>
                <div data-group-error="terms_accepted">
                    @error('terms_accepted')
                        <p class="group-error" role="alert"><span class="icon" aria-hidden="true">!</span>{{ $message }}</p>
                    @enderror
                </div>

                <p class="section-label">Final review</p>
                <div class="review" id="review"></div>
            </section>

        </div>

        <div class="card-foot">
            <button type="button" id="prevBtn" class="btn btn-ghost" disabled>Previous</button>
            <span class="progress-text" id="progressText">Step 1 of 7 · Company details</span>
            <span>
                <button type="button" id="nextBtn" class="btn btn-primary">Next</button>
                <button type="submit" id="submitBtn" class="btn btn-gold is-hidden">Submit application</button>
            </span>
        </div>
    </form>
</main>
@endsection

@push('scripts')
<script>
(function () {
    var form     = document.getElementById('regForm');
    var panels   = [].slice.call(form.querySelectorAll('.step-panel'));
    var stepBtns = [].slice.call(document.querySelectorAll('.step-btn'));
    var lines    = [].slice.call(document.querySelectorAll('.step-line'));
    var prevBtn  = document.getElementById('prevBtn');
    var nextBtn  = document.getElementById('nextBtn');
    var submitBtn = document.getElementById('submitBtn');
    var steps    = @json($steps);

    var current = 0, furthest = 0;

    function show(i, skipScroll) {
        current = Math.max(0, Math.min(i, panels.length - 1));
        furthest = Math.max(furthest, current);

        panels.forEach(function (p, idx) { p.classList.toggle('is-active', idx === current); });

        stepBtns.forEach(function (b, idx) {
            b.classList.toggle('is-active', idx === current);
            b.classList.toggle('is-done', idx < current);
            b.querySelector('.step-num').textContent = idx < current ? '✓' : (idx + 1);
        });
        lines.forEach(function (l, idx) { l.classList.toggle('is-done', idx < current); });

        document.getElementById('cardTitle').textContent = steps[current].head;
        document.getElementById('cardSub').textContent = steps[current].sub;
        document.getElementById('dStep').textContent = current + 1;
        document.getElementById('progressText').textContent = 'Step ' + (current + 1) + ' of 7 · ' + steps[current].head;

        prevBtn.disabled = current === 0;
        var last = current === panels.length - 1;
        nextBtn.classList.toggle('is-hidden', last);
        submitBtn.classList.toggle('is-hidden', !last);

        if (last) buildReview();

        if (!skipScroll) {
            var top = form.getBoundingClientRect().top + window.pageYOffset - 90;
            window.scrollTo({ top: top, behavior: 'smooth' });
        }
    }

    /* ── Error messages ──────────────────────────────────────────
       Wording comes from data-required / data-invalid, which the Blade
       component prints from config/registration.php — the same file the
       server-side validator reads. */

    function messageFor(el) {
        var v = el.validity;
        if (v.valueMissing)   return el.dataset.required || 'This field is required.';
        if (v.typeMismatch)   return el.dataset.invalid || (el.type === 'email'
            ? 'Enter a valid e-mail address, e.g. name@company.com.'
            : 'Enter a valid ' + (el.type === 'url' ? 'web address, starting with https://' : 'value') + '.');
        if (v.patternMismatch) return el.dataset.invalid || 'That format is not accepted here.';
        if (v.rangeOverflow)   return el.dataset.invalid || 'Choose an earlier date.';
        if (v.tooShort)        return 'Use at least ' + el.minLength + ' characters.';
        if (v.tooLong)         return 'Use ' + el.maxLength + ' characters or fewer.';
        return el.validationMessage || 'Check this field.';
    }

    /* Where the message goes: normal fields sit under their own control,
       radio sets and checkboxes have a dedicated slot in the markup. */
    function slotFor(el) {
        if (el.type === 'radio' || el.type === 'checkbox') {
            return document.querySelector('[data-group-error="' + el.name + '"]');
        }
        return el.closest('.field');
    }

    function clearError(el) {
        el.setAttribute('aria-invalid', 'false');
        var slot = slotFor(el);
        if (!slot) return;

        if (el.type === 'radio' || el.type === 'checkbox') {
            slot.innerHTML = '';
            [].slice.call(document.querySelectorAll('input[name="' + el.name + '"]')).forEach(function (r) {
                var box = r.closest('.type-card') || r.closest('.check-row');
                if (box) box.classList.remove('is-invalid');
            });
            return;
        }
        var old = slot.querySelector('.field-error');
        if (old) old.parentElement.removeChild(old);
        var help = slot.querySelector('.field-help');
        if (help) help.style.display = '';
    }

    function markError(el) {
        el.setAttribute('aria-invalid', 'true');
        var slot = slotFor(el);
        if (!slot) return;

        var text = messageFor(el);

        if (el.type === 'radio' || el.type === 'checkbox') {
            slot.innerHTML = '<p class="group-error" role="alert"><span class="icon" aria-hidden="true">!</span>' + text + '</p>';
            [].slice.call(document.querySelectorAll('input[name="' + el.name + '"]')).forEach(function (r) {
                var box = r.closest('.type-card') || r.closest('.check-row');
                if (box) box.classList.add('is-invalid');
            });
            return;
        }

        var existing = slot.querySelector('.field-error');
        if (existing) { existing.lastChild.textContent = text; return; }

        var help = slot.querySelector('.field-help');
        if (help) help.style.display = 'none';

        var p = document.createElement('p');
        p.className = 'field-error';
        p.setAttribute('role', 'alert');
        p.innerHTML = '<span class="icon" aria-hidden="true">!</span>';
        p.appendChild(document.createTextNode(text));
        slot.appendChild(p);
    }

    function markValid(el) {
        if (el.type === 'radio' || el.type === 'checkbox' || el.type === 'file') return;
        el.classList.toggle('is-valid', el.value.trim() !== '' && el.checkValidity());
    }

    function check(el, silent) {
        if (el.type === 'hidden' || el.disabled) return true;
        if (el.checkValidity()) {
            clearError(el);
            markValid(el);
            return true;
        }
        el.classList.remove('is-valid');
        if (!silent) markError(el);
        return false;
    }

    function validate(panel) {
        var first = null;
        var seen = {};
        [].slice.call(panel.querySelectorAll('input, select, textarea')).forEach(function (el) {
            if (el.type === 'hidden' || el.disabled) return;
            if (el.type === 'radio') {
                if (seen[el.name]) return;
                seen[el.name] = true;
                var group = [].slice.call(panel.querySelectorAll('input[name="' + el.name + '"]'));
                var ok = group.some(function (r) { return r.checked; }) || !el.required;
                if (ok) { clearError(el); return; }
                markError(el);
                if (!first) first = el;
                return;
            }
            if (!check(el)) { if (!first) first = el; }
        });

        if (first) {
            var box = first.closest('.field') || first.closest('fieldset') || first;
            box.scrollIntoView({ behavior: 'smooth', block: 'center' });
            first.focus({ preventScroll: true });
            return false;
        }
        return true;
    }

    /* Validate on blur, forgive while typing */
    form.addEventListener('blur', function (e) {
        var el = e.target;
        if (!el.name || el.type === 'file') return;
        if (el.value.trim() === '' && !el.required) { clearError(el); return; }
        check(el);
    }, true);

    form.addEventListener('input', function (e) {
        var el = e.target;
        if (el.getAttribute('aria-invalid') === 'true') check(el, true);
        else markValid(el);
    });

    form.addEventListener('change', function (e) {
        if (e.target.type === 'radio' || e.target.type === 'checkbox') clearError(e.target);
    });

    function buildReview() {
        var box = document.getElementById('review');
        box.innerHTML = '';
        panels.slice(0, 6).forEach(function (panel, idx) {
            var rows = [];
            [].slice.call(panel.querySelectorAll('input, select, textarea')).forEach(function (el) {
                if (!el.name || el.type === 'file' || el.type === 'checkbox') return;
                if (el.type === 'radio' && !el.checked) return;
                if (!el.value) return;
                var label = panel.querySelector('label[for="' + el.id + '"]');
                var name = label ? label.textContent.replace('*', '').trim() : el.name;
                rows.push('<div class="review-row"><dt>' + name + '</dt><dd>' + el.value.replace(/[<>&]/g, '') + '</dd></div>');
            });
            if (!rows.length) return;
            box.insertAdjacentHTML('beforeend',
                '<div class="review-group">' +
                '<button type="button" class="review-head" data-goto="' + idx + '">' +
                '<span>' + steps[idx].head + '</span><span class="edit">Edit</span></button>' +
                '<dl>' + rows.join('') + '</dl></div>');
        });
        [].slice.call(box.querySelectorAll('[data-goto]')).forEach(function (b) {
            b.addEventListener('click', function () { show(+b.dataset.goto); });
        });
    }

    nextBtn.addEventListener('click', function () { if (validate(panels[current])) show(current + 1); });
    prevBtn.addEventListener('click', function () { show(current - 1); });

    stepBtns.forEach(function (b) {
        b.addEventListener('click', function () {
            var t = +b.dataset.goto;
            if (t <= furthest || validate(panels[current])) show(t);
        });
    });

    form.addEventListener('submit', function (e) {
        for (var i = 0; i < panels.length; i++) {
            if (!validate(panels[i])) { e.preventDefault(); show(i); validate(panels[i]); return; }
        }
        submitBtn.disabled = true;
        submitBtn.textContent = 'Sending…';
    });

    var typeCards = [].slice.call(document.querySelectorAll('.type-card'));
    typeCards.forEach(function (card) {
        card.querySelector('input').addEventListener('change', function () {
            typeCards.forEach(function (c) { c.classList.toggle('is-checked', c.querySelector('input').checked); });
        });
    });

    function echo(src, dest) {
        var input = document.getElementById(src), out = document.getElementById(dest);
        function sync() { out.textContent = input.value.trim() || '—'; }
        input.addEventListener('input', sync);
        sync();
    }
    echo('f_company_name', 'echoCompany');
    echo('f_vat_number', 'echoVat');

    var t2 = document.getElementById('toggleDelivery2'), d2 = document.getElementById('delivery2');
    t2.addEventListener('change', function () { d2.classList.toggle('is-hidden', !t2.checked); });

    document.getElementById('copyCompany').addEventListener('click', function () {
        function g(id) { return document.getElementById(id).value; }
        document.getElementById('f_delivery_company_name').value = g('f_company_name');
        document.getElementById('f_delivery_address').value = [g('f_address_1'), g('f_address_2')].filter(Boolean).join(', ');
        document.getElementById('f_delivery_city_country').value = g('f_country');
        document.getElementById('f_delivery_postal_code').value = g('f_post_code');
        document.getElementById('f_delivery_contact_phone').value = g('f_phone_number');
    });

    var failed = form.querySelector('[data-has-error]');
    if (failed) show(panels.indexOf(failed.closest('.step-panel')));
    else show(0, true);
})();
</script>
@endpush
