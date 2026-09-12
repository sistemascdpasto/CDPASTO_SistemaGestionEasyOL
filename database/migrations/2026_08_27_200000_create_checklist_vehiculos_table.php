<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::getConnection()->getDriverName() !== 'mysql') {
            Schema::create('checklist_vehiculos', function (Blueprint $table) {
                $table->id();
                $table->string('id_form', 191)->unique();
                $table->string('estado', 50)->nullable();
                $table->dateTime('fecha')->nullable();
                $table->dateTime('fecha_fin')->nullable();
                $table->string('id_centro', 50)->nullable();
                $table->string('id_regional', 50)->nullable();
                $table->string('regional', 100)->nullable();
                $table->string('centro', 100)->nullable();
                $table->string('operacion', 100)->nullable();
                $table->string('cedula_conductor', 20)->nullable();
                $table->string('placa_vehiculo', 20)->nullable();
                $table->string('odometro', 30)->nullable();
                $table->string('salud_descanso', 50)->nullable();
                $table->string('libre_medicamentos', 50)->nullable();
                $table->string('fugas', 50)->nullable();
                $table->string('testigos_presion_aire', 50)->nullable();
                $table->string('freno_parqueo', 50)->nullable();
                $table->string('kit_reparto', 50)->nullable();
                $table->string('inventario', 50)->nullable();
                $table->string('capacidad_vehiculo', 50)->nullable();
                $table->string('condiciones_operar', 50)->nullable();
                $table->string('documentos_operar', 50)->nullable();
                $table->string('licencia_vigente', 50)->nullable();
                $table->string('licencia_original', 50)->nullable();
                $table->string('tecnomecanica', 50)->nullable();
                $table->string('soat_vigente', 50)->nullable();
                $table->string('kit_totalidad', 50)->nullable();
                $table->string('repuestos_buen_estado', 50)->nullable();
                $table->string('extintor', 50)->nullable();
                $table->string('extintor_vigente', 50)->nullable();
                $table->string('botiquin_condiciones', 50)->nullable();
                $table->string('linterna_condiciones', 50)->nullable();
                $table->string('kit_basico', 50)->nullable();
                $table->string('niveles_totalidad', 50)->nullable();
                $table->string('combustible_suficiente', 50)->nullable();
                $table->string('nivel_combustible', 50)->nullable();
                $table->string('liquido_embrague', 50)->nullable();
                $table->string('refrigerante_estado', 50)->nullable();
                $table->string('aceite_estado', 50)->nullable();
                $table->string('estado_hidraulico', 50)->nullable();
                $table->string('aceite_caja', 50)->nullable();
                $table->string('agua_limpiabrisas', 50)->nullable();
                $table->string('cumple_llantas', 50)->nullable();
                $table->string('bandas_rodamientos', 50)->nullable();
                $table->string('deformaciones_costados', 50)->nullable();
                $table->string('labrado_profundidad', 50)->nullable();
                $table->string('cumple_visibilidad', 50)->nullable();
                $table->string('estado_panoramico', 50)->nullable();
                $table->string('estado_retrovisores', 50)->nullable();
                $table->string('estado_limpiabrisas', 50)->nullable();
                $table->string('estado_cinturones', 50)->nullable();
                $table->string('estado_colapies', 50)->nullable();
                $table->string('cerrar_fuera', 50)->nullable();
                $table->string('estado_dashcam', 50)->nullable();
                $table->string('estado_vidrios', 50)->nullable();
                $table->string('cumple_luces', 50)->nullable();
                $table->string('luces_freno', 50)->nullable();
                $table->string('estado_principales', 50)->nullable();
                $table->string('luces_reserva', 50)->nullable();
                $table->string('luces_direccionales', 50)->nullable();
                $table->string('luces_estacionarias', 50)->nullable();
                $table->string('luces_laterales', 50)->nullable();
                $table->string('estado_pito', 50)->nullable();
                $table->string('estado_pito_reserva', 50)->nullable();
                $table->string('cumple_audible', 50)->nullable();
                $table->string('cumple_carroceria', 50)->nullable();
                $table->string('estado_correas', 50)->nullable();
                $table->string('estado_parales', 50)->nullable();
                $table->string('estado_cortinas', 50)->nullable();
                $table->string('estado_chapas', 50)->nullable();
                $table->string('cumple_carretilla', 50)->nullable();
                $table->string('cuenta_etiqueta', 50)->nullable();
                $table->string('llantas_rodamientos_dos', 50)->nullable();
                $table->string('estado_carretilla_dos', 50)->nullable();
                $table->string('carretilla_dos', 50)->nullable();
                $table->string('etiqueta', 50)->nullable();
                $table->string('estado_rodamiento', 50)->nullable();
                $table->string('estado_carretilla_uno', 50)->nullable();
                $table->string('carretilla_uno', 50)->nullable();
                $table->text('observaciones')->nullable();
                $table->string('firma_conductor', 191)->nullable();
                $table->string('conductor_operar', 100)->nullable();
                $table->string('vehiculo_operar', 30)->nullable();
                $table->string('vehiculo_bitren', 30)->nullable();
                $table->string('estado_bitren', 50)->nullable();
                $table->string('nombre_flota', 100)->nullable();
                $table->string('apellido_flota', 100)->nullable();
                $table->string('firma_responsable', 191)->nullable();
                $table->string('estado_form', 50)->nullable();
                $table->decimal('cumpl', 8, 2)->nullable();
                $table->decimal('meta_td', 8, 2)->nullable();
                $table->string('tiempo_ejecucion', 50)->nullable();
                $table->unsignedTinyInteger('mes')->nullable();
                $table->unsignedTinyInteger('semana')->nullable();
                $table->unsignedSmallInteger('anio')->nullable();
                $table->unsignedTinyInteger('dia')->nullable();
                $table->decimal('meta', 8, 2)->nullable();
                $table->decimal('cumpl_meta', 8, 2)->nullable();
                $table->timestamps();

                $table->index(['fecha', 'placa_vehiculo'], 'idx_fecha_placa');
                $table->index(['cedula_conductor', 'fecha'], 'idx_cedula_fecha');
            });
            return;
        }

        DB::statement('SET SESSION innodb_strict_mode=0');

        DB::statement('
            CREATE TABLE `checklist_vehiculos` (
              `id`                       BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
              `id_form`                  VARCHAR(191) NOT NULL UNIQUE,
              `estado`                   VARCHAR(50)  NULL,
              `fecha`                    DATETIME     NULL,
              `fecha_fin`                DATETIME     NULL,
              `id_centro`                VARCHAR(50)  NULL,
              `id_regional`              VARCHAR(50)  NULL,
              `regional`                 VARCHAR(100) NULL,
              `centro`                   VARCHAR(100) NULL,
              `operacion`                VARCHAR(100) NULL,
              `cedula_conductor`         VARCHAR(20)  NULL,
              `placa_vehiculo`           VARCHAR(20)  NULL,
              `odometro`                 VARCHAR(30)  NULL,
              `salud_descanso`           VARCHAR(50)  NULL,
              `libre_medicamentos`       VARCHAR(50)  NULL,
              `fugas`                    VARCHAR(50)  NULL,
              `testigos_presion_aire`    VARCHAR(50)  NULL,
              `freno_parqueo`            VARCHAR(50)  NULL,
              `kit_reparto`              VARCHAR(50)  NULL,
              `inventario`               VARCHAR(50)  NULL,
              `capacidad_vehiculo`       VARCHAR(50)  NULL,
              `condiciones_operar`       VARCHAR(50)  NULL,
              `documentos_operar`        VARCHAR(50)  NULL,
              `licencia_vigente`         VARCHAR(50)  NULL,
              `licencia_original`        VARCHAR(50)  NULL,
              `tecnomecanica`            VARCHAR(50)  NULL,
              `soat_vigente`             VARCHAR(50)  NULL,
              `kit_totalidad`            VARCHAR(50)  NULL,
              `repuestos_buen_estado`    VARCHAR(50)  NULL,
              `extintor`                 VARCHAR(50)  NULL,
              `extintor_vigente`         VARCHAR(50)  NULL,
              `botiquin_condiciones`     VARCHAR(50)  NULL,
              `linterna_condiciones`     VARCHAR(50)  NULL,
              `kit_basico`               VARCHAR(50)  NULL,
              `niveles_totalidad`        VARCHAR(50)  NULL,
              `combustible_suficiente`   VARCHAR(50)  NULL,
              `nivel_combustible`        VARCHAR(50)  NULL,
              `liquido_embrague`         VARCHAR(50)  NULL,
              `refrigerante_estado`      VARCHAR(50)  NULL,
              `aceite_estado`            VARCHAR(50)  NULL,
              `estado_hidraulico`        VARCHAR(50)  NULL,
              `aceite_caja`              VARCHAR(50)  NULL,
              `agua_limpiabrisas`        VARCHAR(50)  NULL,
              `cumple_llantas`           VARCHAR(50)  NULL,
              `bandas_rodamientos`       VARCHAR(50)  NULL,
              `deformaciones_costados`   VARCHAR(50)  NULL,
              `labrado_profundidad`      VARCHAR(50)  NULL,
              `cumple_visibilidad`       VARCHAR(50)  NULL,
              `estado_panoramico`        VARCHAR(50)  NULL,
              `estado_retrovisores`      VARCHAR(50)  NULL,
              `estado_limpiabrisas`      VARCHAR(50)  NULL,
              `estado_cinturones`        VARCHAR(50)  NULL,
              `estado_colapies`          VARCHAR(50)  NULL,
              `cerrar_fuera`             VARCHAR(50)  NULL,
              `estado_dashcam`           VARCHAR(50)  NULL,
              `estado_vidrios`           VARCHAR(50)  NULL,
              `cumple_luces`             VARCHAR(50)  NULL,
              `luces_freno`              VARCHAR(50)  NULL,
              `estado_principales`       VARCHAR(50)  NULL,
              `luces_reserva`            VARCHAR(50)  NULL,
              `luces_direccionales`      VARCHAR(50)  NULL,
              `luces_estacionarias`      VARCHAR(50)  NULL,
              `luces_laterales`          VARCHAR(50)  NULL,
              `estado_pito`              VARCHAR(50)  NULL,
              `estado_pito_reserva`      VARCHAR(50)  NULL,
              `cumple_audible`           VARCHAR(50)  NULL,
              `cumple_carroceria`        VARCHAR(50)  NULL,
              `estado_correas`           VARCHAR(50)  NULL,
              `estado_parales`           VARCHAR(50)  NULL,
              `estado_cortinas`          VARCHAR(50)  NULL,
              `estado_chapas`            VARCHAR(50)  NULL,
              `cumple_carretilla`        VARCHAR(50)  NULL,
              `cuenta_etiqueta`          VARCHAR(50)  NULL,
              `llantas_rodamientos_dos`  VARCHAR(50)  NULL,
              `estado_carretilla_dos`    VARCHAR(50)  NULL,
              `carretilla_dos`           VARCHAR(50)  NULL,
              `etiqueta`                 VARCHAR(50)  NULL,
              `estado_rodamiento`        VARCHAR(50)  NULL,
              `estado_carretilla_uno`    VARCHAR(50)  NULL,
              `carretilla_uno`           VARCHAR(50)  NULL,
              `observaciones`            TEXT         NULL,
              `firma_conductor`          VARCHAR(191) NULL,
              `conductor_operar`         VARCHAR(100) NULL,
              `vehiculo_operar`          VARCHAR(30)  NULL,
              `vehiculo_bitren`          VARCHAR(30)  NULL,
              `estado_bitren`            VARCHAR(50)  NULL,
              `nombre_flota`             VARCHAR(100) NULL,
              `apellido_flota`           VARCHAR(100) NULL,
              `firma_responsable`        VARCHAR(191) NULL,
              `estado_form`              VARCHAR(50)  NULL,
              `cumpl`                    DECIMAL(8,2) NULL,
              `meta_td`                  DECIMAL(8,2) NULL,
              `tiempo_ejecucion`         VARCHAR(50)  NULL,
              `mes`                      TINYINT UNSIGNED NULL,
              `semana`                   TINYINT UNSIGNED NULL,
              `anio`                     SMALLINT UNSIGNED NULL,
              `dia`                      TINYINT UNSIGNED NULL,
              `meta`                     DECIMAL(8,2) NULL,
              `cumpl_meta`               DECIMAL(8,2) NULL,
              `created_at`               TIMESTAMP NULL,
              `updated_at`               TIMESTAMP NULL,
              INDEX `idx_fecha_placa`  (`fecha`, `placa_vehiculo`),
              INDEX `idx_cedula_fecha` (`cedula_conductor`, `fecha`)
            ) ENGINE=InnoDB
              ROW_FORMAT=DYNAMIC
              DEFAULT CHARSET=utf8mb4
              COLLATE=utf8mb4_unicode_ci
        ');

        DB::statement('SET SESSION innodb_strict_mode=1');
    }

    public function down(): void
    {
        Schema::dropIfExists('checklist_vehiculos');
    }
};
