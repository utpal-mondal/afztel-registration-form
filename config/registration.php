<?php

return [
    /*
     * Every submitted form is copied here. Mail::to() accepts an array,
     * so REGISTRATION_ADMIN_EMAIL can hold a comma separated list.
     */
    'admin_email' => array_map(
        'trim',
        explode(',', env('REGISTRATION_ADMIN_EMAIL', 'info@auniquetel.pt'))
    ),

    'company' => [
        'name'    => env('COMPANY_NAME', 'A UNIQUE TEL, UNIPESSOAL LDA'),
        'nipc'    => env('COMPANY_NIPC', '510296211'),
        'legal'   => env('COMPANY_LEGAL', 'Sociedade por Quotas'),
        'address' => env('COMPANY_ADDRESS', 'Rua Angola, nº 4, Centro Comercial Satélite, loja 30, Cacém'),
        'region'  => env('COMPANY_REGION', 'Cacém e São Marcos, 2735-229 Cacém — Sintra, Lisboa'),
    ],

    /*
     |--------------------------------------------------------------------------
     | Field messages — single source of truth
     |--------------------------------------------------------------------------
     | 'label'    → used in fallback validation messages
     | 'required' → shown when the field is left empty (browser AND server)
     | 'invalid'  → shown when the value is the wrong shape (email, date, …)
     |
     | The <x-field> component prints these as data-attributes so the inline
     | JS shows the same wording the server would, and StoreRegistrationRequest
     | reads the same array. Edit the copy here and both sides update.
     */
    'field_messages' => [
        'company_type' => [
            'label'    => 'company type',
            'required' => 'Choose whether you are registering as a supplier or a customer.',
        ],
        'company_name' => [
            'label'    => 'company name',
            'required' => 'Enter your registered company name.',
        ],
        'address_1' => [
            'label'    => 'address',
            'required' => 'Enter the first line of your registered address.',
        ],
        'address_2' => ['label' => 'second address line'],
        'post_code' => [
            'label'    => 'post code',
            'required' => 'Enter your post code.',
        ],
        'country' => [
            'label'    => 'country',
            'required' => 'Enter the country your company is registered in.',
        ],
        'phone_number' => [
            'label'    => 'phone number',
            'required' => 'Enter a landline or mobile number we can reach you on.',
            'invalid'  => 'Use digits, spaces and an optional + prefix, e.g. +351 21 000 0000.',
        ],
        'email' => [
            'label'    => 'mail address',
            'required' => 'Enter the e-mail address your confirmation should go to.',
            'invalid'  => 'That does not look like a valid e-mail address, e.g. name@company.com.',
        ],
        'web_address' => [
            'label'   => 'web address',
            'invalid' => 'Include the full address starting with http:// or https://.',
        ],

        'registration_number' => [
            'label'    => 'registration number',
            'required' => 'Enter the company registration number from your incorporation record.',
        ],
        'vat_number' => [
            'label'    => 'VAT number',
            'required' => 'Enter your VAT number, including the country prefix.',
            'invalid'  => 'Use 8 to 15 characters, letters and digits only, e.g. PT510296211.',
        ],
        'incorporation_date' => [
            'label'    => 'incorporation date',
            'required' => 'Enter the date your company was incorporated.',
            'invalid'  => 'The incorporation date cannot be in the future.',
        ],
        'main_business_activity' => [
            'label'    => 'main business activity',
            'required' => 'Describe what your company trades in.',
        ],
        'hear_about_us' => ['label' => 'referral source'],

        'director_name' => [
            'label'    => 'director name',
            'required' => 'Enter the full name of a director or partner.',
        ],
        'director_address' => [
            'label'    => 'director address',
            'required' => 'Enter the address held on record for this director.',
        ],
        'director_position' => [
            'label'    => 'director position',
            'required' => 'Enter this person’s position, e.g. Managing Director.',
        ],

        'trader_name' => [
            'label'    => 'trader name',
            'required' => 'Enter the name of the person we should speak to about orders.',
        ],
        'trader_mobile' => [
            'label'    => 'trader mobile number',
            'required' => 'Enter a mobile number for the trading contact.',
            'invalid'  => 'Use digits, spaces and an optional + prefix, e.g. +351 910 000 000.',
        ],
        'trader_position' => [
            'label'    => 'trader position',
            'required' => 'Enter the trading contact’s position.',
        ],

        'delivery_company_name' => [
            'label'    => 'delivery company name',
            'required' => 'Enter the company name goods should be delivered to.',
        ],
        'delivery_address' => [
            'label'    => 'delivery address',
            'required' => 'Enter the street address for delivery.',
        ],
        'delivery_city_country' => [
            'label'    => 'delivery city and country',
            'required' => 'Enter the city and country for delivery.',
        ],
        'delivery_postal_code' => [
            'label'    => 'delivery postal code',
            'required' => 'Enter the postal code for the delivery address.',
        ],
        'delivery_contact_phone' => [
            'label'    => 'delivery contact',
            'required' => 'Enter a name and phone number for the person receiving goods.',
        ],

        'delivery2_company_name'  => ['label' => 'second delivery company name'],
        'delivery2_address'       => ['label' => 'second delivery address'],
        'delivery2_city_country'  => ['label' => 'second delivery city and country'],
        'delivery2_postal_code'   => ['label' => 'second delivery postal code'],
        'delivery2_contact_phone' => ['label' => 'second delivery contact'],

        'bank_name' => [
            'label'    => 'bank name',
            'required' => 'Enter the name of your bank.',
        ],
        'account_name' => [
            'label'    => 'account name',
            'required' => 'Enter the name the account is held under.',
        ],
        'iban' => [
            'label'    => 'IBAN',
            'required' => 'Enter the IBAN for your settlement account.',
            'invalid'  => 'An IBAN is 15 to 34 characters: two country letters, then digits, e.g. PT50 0002 0123 1234 5678 9015 4.',
        ],
        'sort_code' => [
            'label'   => 'sort code',
            'invalid' => 'A sort code is six digits, e.g. 20-00-00.',
        ],
        'bic_swift' => [
            'label'    => 'BIC / SWIFT',
            'required' => 'Enter the BIC or SWIFT code for your bank.',
            'invalid'  => 'A BIC is 8 or 11 characters, e.g. BCOMPTPL.',
        ],

        'registration_doc' => ['label' => 'incorporation certificate'],
        'vat_certificate'  => ['label' => 'VAT certificate'],
        'signature'        => ['label' => 'signed and stamped page'],

        'signatory_name' => [
            'label'    => 'signatory name',
            'required' => 'Enter the full name of the person signing.',
        ],
        'signatory_designation' => [
            'label'    => 'designation',
            'required' => 'Enter the signatory’s designation, e.g. Director.',
        ],
        'signed_date' => [
            'label'    => 'date',
            'required' => 'Enter the date you are signing this declaration.',
        ],
        'signed_place' => [
            'label'    => 'place',
            'required' => 'Enter the city where the declaration is signed.',
        ],
        'terms_accepted' => [
            'label'    => 'declaration',
            'required' => 'Tick the box to confirm the declaration before submitting.',
        ],
    ],
];
