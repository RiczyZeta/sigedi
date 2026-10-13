<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/*
 * Sprint 4 - Bitácora digital (HU13, HU14).
 * Agrega la fecha de la actividad (diccionario de datos, Tabla 18). En los registros automáticos
 * coincide con el momento de la acción; en los mantenimientos manuales es la fecha en que se
 * realizó el trabajo, que puede ser anterior a la fecha de registro (created_at).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bitacoras', function (Blueprint $table) {
            $table->timestamp('fecha')->nullable()->after('descripcion')->index();
            $table->index('accion');
        });

        // Registros previos: la fecha de la actividad es la fecha en que se registraron.
        DB::table('bitacoras')->whereNull('fecha')->update(['fecha' => DB::raw('created_at')]);
    }

    public function down(): void
    {
        Schema::table('bitacoras', function (Blueprint $table) {
            $table->dropIndex(['accion']);
            $table->dropIndex(['fecha']);
            $table->dropColumn('fecha');
        });
    }
};
