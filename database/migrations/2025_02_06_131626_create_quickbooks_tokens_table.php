<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('quickbooks_tokens', function (Blueprint $table) {
            $table->id();
            $table->longText('realm_id'); // Store long encrypted values
            $table->longText('access_token');
            $table->longText('refresh_token');
            $table->timestamp('expires_at'); // Token expiration time
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quickbooks_tokens');
    }
};
