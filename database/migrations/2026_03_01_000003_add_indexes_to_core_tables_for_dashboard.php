<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->index(['statut', 'role_id'], 'users_statut_role_id_index');
        });

        Schema::table('evenements', function (Blueprint $table) {
            $table->index(['statut', 'date_debut'], 'evenements_statut_date_debut_index');
        });

        Schema::table('annonces', function (Blueprint $table) {
            $table->index(['statut', 'created_at'], 'annonces_statut_created_at_index');
        });

        Schema::table('notifications', function (Blueprint $table) {
            $table->index(['user_id', 'lu', 'created_at'], 'notifications_user_lu_created_at_index');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex('users_statut_role_id_index');
        });

        Schema::table('evenements', function (Blueprint $table) {
            $table->dropIndex('evenements_statut_date_debut_index');
        });

        Schema::table('annonces', function (Blueprint $table) {
            $table->dropIndex('annonces_statut_created_at_index');
        });

        Schema::table('notifications', function (Blueprint $table) {
            $table->dropIndex('notifications_user_lu_created_at_index');
        });
    }
};
