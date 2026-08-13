@props([
    'name',
    'label',
    'type' => 'text',
    'required' => false,
    'placeholder' => '',
    'options' => null,
    'help' => null,
    'value' => null,
    'span' => 'col-12',
    'pattern' => null,
    'inputmode' => null,
    'autocomplete' => null,
    'maxlength' => null,
])

@php
    $id      = 'f_' . $name;
    $current = old($name, $value);
    $invalid = $errors->has($name);
    $copy    = config('registration.field_messages.' . $name, []);

    $msgRequired = $copy['required'] ?? 'Fill in the ' . strtolower($label) . '.';
    $msgInvalid  = $copy['invalid']  ?? null;
@endphp

<div class="field {{ $span }}" @if($invalid) data-has-error="true" @endif>
    <label for="{{ $id }}" class="field-label">
        {{ $label }}
        @if($required)
            <span class="req" aria-hidden="true">*</span><span class="sr-only">required</span>
        @else
            <span class="optional">optional</span>
        @endif
    </label>

    <div class="field-control">
        @if ($type === 'textarea')
            <textarea id="{{ $id }}" name="{{ $name }}" rows="3"
                      placeholder="{{ $placeholder }}"
                      @if($required) required @endif
                      @if($maxlength) maxlength="{{ $maxlength }}" @endif
                      data-required="{{ $msgRequired }}"
                      @if($msgInvalid) data-invalid="{{ $msgInvalid }}" @endif
                      aria-invalid="{{ $invalid ? 'true' : 'false' }}"
                      @if($help || $invalid) aria-describedby="{{ $id }}_note" @endif
                      class="field-input">{{ $current }}</textarea>

        @elseif ($type === 'select')
            <select id="{{ $id }}" name="{{ $name }}"
                    @if($required) required @endif
                    data-required="{{ $msgRequired }}"
                    aria-invalid="{{ $invalid ? 'true' : 'false' }}"
                    class="field-input">
                <option value="">Select an option…</option>
                @foreach ($options as $key => $text)
                    <option value="{{ is_int($key) ? $text : $key }}"
                        @selected($current === (is_int($key) ? $text : $key))>{{ $text }}</option>
                @endforeach
            </select>

        @elseif ($type === 'file')
            <input id="{{ $id }}" name="{{ $name }}" type="file"
                   accept=".pdf,.jpg,.jpeg,.png,.webp"
                   aria-invalid="{{ $invalid ? 'true' : 'false' }}"
                   class="field-input field-file">

        @else
            <input id="{{ $id }}" name="{{ $name }}" type="{{ $type }}"
                   value="{{ $current }}"
                   placeholder="{{ $placeholder }}"
                   @if($required) required @endif
                   @if($pattern) pattern="{{ $pattern }}" @endif
                   @if($inputmode) inputmode="{{ $inputmode }}" @endif
                   @if($autocomplete) autocomplete="{{ $autocomplete }}" @endif
                   @if($maxlength) maxlength="{{ $maxlength }}" @endif
                   @if($type === 'date') max="{{ now()->toDateString() }}" @endif
                   data-required="{{ $msgRequired }}"
                   @if($msgInvalid) data-invalid="{{ $msgInvalid }}" @endif
                   aria-invalid="{{ $invalid ? 'true' : 'false' }}"
                   @if($help || $invalid) aria-describedby="{{ $id }}_note" @endif
                   class="field-input">
        @endif

        <span class="field-state" aria-hidden="true"></span>
    </div>

    @error($name)
        <p class="field-error" id="{{ $id }}_note" data-for="{{ $name }}" role="alert">
            <span class="icon" aria-hidden="true">!</span>{{ $message }}
        </p>
    @else
        @if ($help)
            <p class="field-help" id="{{ $id }}_note">{{ $help }}</p>
        @endif
    @enderror
</div>
