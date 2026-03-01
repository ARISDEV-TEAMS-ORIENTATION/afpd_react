<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cotisations', function (Blueprint $table) {
            $table->timestamp('date_paiement')->nullable()->index()->after('montant');
            $table->string('periode', 7)->nullable()->index()->after('date_paiement');
            $table->string('statut_paiement')->default('en_attente')->index()->after('periode');
            $table->string('mode_paiement')->nullable()->after('statut_paiement');
            $table->string('reference')->nullable()->unique()->after('mode_paiement');
            $table->string('recu_path')->nullable()->after('reference');
        });
    }

    public function down(): void
    {
        Schema::table('cotisations', function (Blueprint $table) {
            $table->dropUnique(['reference']);
            $table->dropColumn([
                'date_paiement',
                'periode',
                'statut_paiement',
                'mode_paiement',
                'reference',
                'recu_path',
            ]);
        });
    }
};
