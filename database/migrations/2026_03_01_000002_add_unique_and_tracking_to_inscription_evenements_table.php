<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inscription_evenements', function (Blueprint $table) {
            $table->timestamp('date_presence')->nullable()->after('presence');
            $table->string('statut_inscription')->default('inscrite')->index()->after('date_presence');
            $table->unique(['user_id', 'evenement_id'], 'inscription_evenements_user_evenement_unique');
        });
    }

    public function down(): void
    {
        Schema::table('inscription_evenements', function (Blueprint $table) {
            $table->dropUnique('inscription_evenements_user_evenement_unique');
            $table->dropColumn(['date_presence', 'statut_inscription']);
        });
    }
};
