<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
   public function up()
{
    Schema::create('users', function (Blueprint $table) {
        $table->id(); // id_utilisateur
        $table->string('nom');
        $table->string('prenom')->nullable();
        $table->string('email')->unique();
        $table->string('password'); // mot_de_passe
        $table->string('telephone')->nullable();
        $table->dateTime('date_inscription')->nullable();
        $table->string('statut')->default('actif');
        $table->rememberToken();
        $table->timestamps();
    });
}


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('sessions');
    }
};
