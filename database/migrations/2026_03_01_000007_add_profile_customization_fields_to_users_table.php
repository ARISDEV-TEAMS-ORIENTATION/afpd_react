<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('avatar_path')->nullable()->after('telephone');
            $table->string('theme')->default('system')->after('avatar_path');
            $table->string('language', 10)->default('fr')->after('theme');
            $table->string('timezone')->default('Africa/Douala')->after('language');
            $table->boolean('email_notifications')->default(true)->after('timezone');
            $table->boolean('push_notifications')->default(false)->after('email_notifications');
            $table->boolean('show_phone')->default(false)->after('push_notifications');
            $table->boolean('show_email')->default(false)->after('show_phone');
            $table->string('profile_visibility')->default('members')->after('show_email');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'avatar_path',
                'theme',
                'language',
                'timezone',
                'email_notifications',
                'push_notifications',
                'show_phone',
                'show_email',
                'profile_visibility',
            ]);
        });
    }
};
