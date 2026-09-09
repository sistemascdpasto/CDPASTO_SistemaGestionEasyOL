<?php

namespace Tests\Feature\Seguridad;

use App\Models\Seguridad\GlossaryTerm;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class GlossaryTermIndexTest extends TestCase
{
    use RefreshDatabase;

    private function seguridadUser(): User
    {
        $role = Role::create(['name' => 'Seguridad', 'guard_name' => 'web']);
        Role::create(['name' => 'Colaborador', 'guard_name' => 'web']);
        $user = User::factory()->create();
        $user->assignRole($role);

        return $user;
    }

    private function termino(string $nombre, string $categoria = 'SEÑALIZACIÓN DE LA VÍA'): GlossaryTerm
    {
        return GlossaryTerm::create(['nombre' => $nombre, 'definicion' => "Definición de {$nombre}", 'categoria' => $categoria]);
    }

    public function test_ordena_por_defecto_agrupado_por_categoria(): void
    {
        $user = $this->seguridadUser();

        $this->termino('Zebra', 'CONDICIONES DEL PAVIMENTO');
        $this->termino('Andén', 'SEÑALIZACIÓN DE LA VÍA');
        $this->termino('Berma', 'CONDICIONES DEL PAVIMENTO');

        $response = $this->actingAs($user)->get(route('seguridad.glosario.index'));

        $response->assertInertia(fn ($page) => $page
            ->where('terms.data.0.nombre', 'Berma')
            ->where('terms.data.1.nombre', 'Zebra')
            ->where('terms.data.2.nombre', 'Andén'));
    }

    public function test_filtro_nombre_asc_ordena_alfabeticamente_sin_agrupar_por_categoria(): void
    {
        $user = $this->seguridadUser();

        $this->termino('Zebra', 'CONDICIONES DEL PAVIMENTO');
        $this->termino('Andén', 'SEÑALIZACIÓN DE LA VÍA');
        $this->termino('Berma', 'CONDICIONES DEL PAVIMENTO');

        $response = $this->actingAs($user)->get(route('seguridad.glosario.index', ['orden' => 'nombre_asc']));

        $response->assertInertia(fn ($page) => $page
            ->where('terms.data.0.nombre', 'Andén')
            ->where('terms.data.1.nombre', 'Berma')
            ->where('terms.data.2.nombre', 'Zebra')
            ->where('filters.orden', 'nombre_asc'));
    }

    public function test_filtro_nombre_desc_ordena_alfabeticamente_inverso(): void
    {
        $user = $this->seguridadUser();

        $this->termino('Zebra');
        $this->termino('Andén');
        $this->termino('Berma');

        $response = $this->actingAs($user)->get(route('seguridad.glosario.index', ['orden' => 'nombre_desc']));

        $response->assertInertia(fn ($page) => $page
            ->where('terms.data.0.nombre', 'Zebra')
            ->where('terms.data.1.nombre', 'Berma')
            ->where('terms.data.2.nombre', 'Andén'));
    }
}
