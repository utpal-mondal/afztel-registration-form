<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('registrations', function (Blueprint $table) {
            $table->id();
            $table->string('reference')->unique();

            // Company type
            $table->enum('company_type', ['supplier', 'customer']);

            // Company details
            $table->string('company_name');
            $table->string('address_1');
            $table->string('address_2')->nullable();
            $table->string('post_code');
            $table->string('country');
            $table->string('phone_number');
            $table->string('email');
            $table->string('web_address')->nullable();

            // Registration / business
            $table->string('registration_number');
            $table->string('vat_number');
            $table->date('incorporation_date');
            $table->string('main_business_activity');
            $table->string('hear_about_us')->nullable();

            // Directors / partners
            $table->string('director_name');
            $table->string('director_address');
            $table->string('director_position');

            // Trader contact
            $table->string('trader_name');
            $table->string('trader_mobile');
            $table->string('trader_position');

            // Delivery address 1
            $table->string('delivery_company_name');
            $table->string('delivery_address');
            $table->string('delivery_city_country');
            $table->string('delivery_postal_code');
            $table->string('delivery_contact_phone');

            // Delivery address 2 (optional)
            $table->string('delivery2_company_name')->nullable();
            $table->string('delivery2_address')->nullable();
            $table->string('delivery2_city_country')->nullable();
            $table->string('delivery2_postal_code')->nullable();
            $table->string('delivery2_contact_phone')->nullable();

            // Bank details
            $table->string('bank_name');
            $table->string('account_name');
            $table->string('iban');
            $table->string('sort_code')->nullable();
            $table->string('bic_swift');

            // Uploaded documents
            $table->string('registration_doc_path')->nullable();
            $table->string('vat_certificate_path')->nullable();
            $table->string('signature_path')->nullable();

            // Declaration
            $table->string('signatory_name');
            $table->string('signatory_designation');
            $table->date('signed_date');
            $table->string('signed_place');
            $table->boolean('terms_accepted')->default(false);

            // Meta
            $table->string('status')->default('pending');
            $table->ipAddress('submitted_ip')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('registrations');
    }
};
