<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tbl_roles_modulos', function (Blueprint $table) {

            $table->unsignedBigInteger('rol_id');
            $table->unsignedBigInteger('modulo_id');

            // Clave primaria compuesta
            $table->primary(['rol_id', 'modulo_id']);

            // Foreign keys
            $table->foreign('rol_id')
                  ->references('id')
                  ->on('tbl_roles')
                  ->onDelete('cascade');

            $table->foreign('modulo_id')
                  ->references('id')
                  ->on('tbl_modulos')
                  ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tbl_roles_modulos');
    }
};