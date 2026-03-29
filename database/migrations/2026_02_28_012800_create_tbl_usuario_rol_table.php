<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tbl_usuario_rol', function (Blueprint $table) {

            $table->unsignedBigInteger('usuario_id');
            $table->unsignedBigInteger('rol_id');

            // Clave primaria compuesta
            $table->primary(['usuario_id', 'rol_id']);

            // Foreign keys
            $table->foreign('usuario_id')
                  ->references('id')
                  ->on('tbl_usuarios')
                  ->onDelete('cascade');

            $table->foreign('rol_id')
                  ->references('id')
                  ->on('tbl_roles')
                  ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tbl_usuario_rol');
    }
};