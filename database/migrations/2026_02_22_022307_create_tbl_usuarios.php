<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tbl_usuarios', function (Blueprint $table) {
            $table->id();
            $table->string('run', 15)->unique();
            $table->string('password_hash');
            $table->unsignedBigInteger('rol_id');
            $table->unsignedBigInteger('sucursal_id');
            $table->string('device_id')->nullable();
            $table->boolean('activo')->default(true);
            $table->timestamps();

            $table->foreign('rol_id')->references('id')->on('tbl_roles');
            $table->foreign('sucursal_id')->references('id')->on('tbl_sucursales');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tbl_usuarios');
    }
};