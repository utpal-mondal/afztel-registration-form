<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Registration extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'incorporation_date' => 'date',
        'signed_date'        => 'date',
        'terms_accepted'     => 'boolean',
    ];

    /**
     * Human readable label for the chosen company type.
     */
    public function getTypeLabelAttribute(): string
    {
        return ucfirst($this->company_type);
    }

    /**
     * Groups of fields used to render the summary in both e-mails.
     * Keeping it here means the applicant mail, the admin mail and any
     * future PDF export all stay in sync automatically.
     */
    public function summarySections(): array
    {
        return [
            'Company details' => [
                'Company type'          => $this->type_label,
                'Company name'          => $this->company_name,
                'Address 1'             => $this->address_1,
                'Address 2'             => $this->address_2,
                'Post code'             => $this->post_code,
                'Country'               => $this->country,
                'Phone number'          => $this->phone_number,
                'Mail address'          => $this->email,
                'Web address'           => $this->web_address,
            ],
            'Registration & business' => [
                'Registration number'   => $this->registration_number,
                'VAT number'            => $this->vat_number,
                'Incorporation date'    => optional($this->incorporation_date)->format('d/m/Y'),
                'Main business activity' => $this->main_business_activity,
                'How they heard about us' => $this->hear_about_us,
            ],
            'Directors / partners' => [
                'Name'     => $this->director_name,
                'Address'  => $this->director_address,
                'Position' => $this->director_position,
            ],
            'Trader contact' => [
                'Name'          => $this->trader_name,
                'Mobile number' => $this->trader_mobile,
                'Position'      => $this->trader_position,
            ],
            'Delivery address' => [
                'Company name'   => $this->delivery_company_name,
                'Address'        => $this->delivery_address,
                'City / country' => $this->delivery_city_country,
                'Postal code'    => $this->delivery_postal_code,
                'Contact / phone' => $this->delivery_contact_phone,
            ],
            'Delivery address 2' => array_filter([
                'Company name'   => $this->delivery2_company_name,
                'Address'        => $this->delivery2_address,
                'City / country' => $this->delivery2_city_country,
                'Postal code'    => $this->delivery2_postal_code,
                'Contact / phone' => $this->delivery2_contact_phone,
            ]),
            'Bank details' => [
                'Bank name'    => $this->bank_name,
                'Account name' => $this->account_name,
                'IBAN'         => $this->iban,
                'Sort code'    => $this->sort_code,
                'BIC / SWIFT'  => $this->bic_swift,
            ],
            'Declaration' => [
                'Authorised signatory' => $this->signatory_name,
                'Designation'          => $this->signatory_designation,
                'Date'                 => optional($this->signed_date)->format('d/m/Y'),
                'Place'                => $this->signed_place,
                'Terms accepted'       => $this->terms_accepted ? 'Yes' : 'No',
            ],
        ];
    }
}
