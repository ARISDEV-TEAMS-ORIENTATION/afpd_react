<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rapports', function (Blueprint $table) {
            $table->string('format')->nullable()->after('chemin_fichier');
            $table->string('statut_generation')->default('termine')->index()->after('format');
            $table->timestamp('generated_at')->nullable()->after('statut_generation');
        });
    }

    public function down(): void
    {
        Schema::table('rapports', function (Blueprint $table) {
            $table->dropIndex(['statut_generation']);
            $table->dropColumn(['format', 'statut_generation', 'generated_at']);
        });
    }
};
