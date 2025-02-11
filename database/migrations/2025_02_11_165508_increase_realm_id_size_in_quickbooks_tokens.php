<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class IncreaseRealmIdSizeInQuickbooksTokens extends Migration
{
    public function up()
    {
        Schema::table('quickbooks_tokens', function (Blueprint $table) {
            $table->text('realm_id')->change(); // Change column type to text
        });
    }

    public function down()
    {
        Schema::table('quickbooks_tokens', function (Blueprint $table) {
            $table->string('realm_id', 50)->change(); // Revert to previous state
        });
    }
}

