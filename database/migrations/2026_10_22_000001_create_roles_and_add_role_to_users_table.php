<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('description')->nullable();
            $table->timestamps();
        });

        // Seed Admin (id: 1) and User (id: 2)
        DB::table('roles')->insert([
            [
                'id' => 1,
                'name' => 'Admin',
                'slug' => 'admin',
                'description' => 'Full CRUD permissions for folders and files, plus User Management',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 2,
                'name' => 'User',
                'slug' => 'user',
                'description' => 'Read-only permissions for viewing files',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('role_id')->default(2)->after('id')->constrained('roles')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['role_id']);
            $table->dropColumn('role_id');
        });

        Schema::dropIfExists('roles');
    }
};
