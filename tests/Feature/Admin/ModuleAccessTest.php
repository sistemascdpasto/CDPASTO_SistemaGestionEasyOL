<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use App\Models\UserSubmoduleAccess;
use App\Support\ModuleAccessRegistry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ModuleAccessTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (['Administrador', 'Seguridad', 'Gente', 'Flota', 'Colaborador'] as $rol) {
            Role::findOrCreate($rol, 'web');
        }
    }

    private function usuario(string $rol): User
    {
        $user = User::factory()->create();
        $user->assignRole($rol);

        return $user;
    }

    public function test_sin_personalizar_un_usuario_conserva_acceso_completo_a_su_rol(): void
    {
        $user = $this->usuario('Gente');

        // Ruta real de un submódulo de Gente, sin personalización: debe
        // entrar igual que antes de esta funcionalidad.
        $this->actingAs($user)->get(route('gente.calificaciones.index'))->assertOk();
    }

    public function test_un_usuario_personalizado_solo_entra_a_los_submodulos_otorgados(): void
    {
        $user = $this->usuario('Gente');
        $user->update(['modulos_personalizados' => true]);
        UserSubmoduleAccess::create(['user_id' => $user->id, 'module_slug' => 'gente', 'submodule_key' => 'calificaciones']);

        $this->actingAs($user)->get(route('gente.calificaciones.index'))->assertOk();
        $this->actingAs($user)->get(route('gente.ausentismo.index'))->assertForbidden();
    }

    public function test_un_usuario_personalizado_sin_el_rol_sigue_bloqueado_aunque_se_le_otorgue_el_submodulo(): void
    {
        // El rol sigue siendo el piso: otorgar explícitamente un submódulo de
        // Flota a alguien sin el rol Flota (ni Administrador) no le da acceso.
        $user = $this->usuario('Gente');
        $user->update(['modulos_personalizados' => true]);
        UserSubmoduleAccess::create(['user_id' => $user->id, 'module_slug' => 'flota', 'submodule_key' => 'vehiculos']);

        $this->actingAs($user)->get(route('flota.vehiculos.index'))->assertForbidden();
    }

    public function test_administrador_siempre_tiene_acceso_sin_importar_la_personalizacion(): void
    {
        $admin = $this->usuario('Administrador');
        $admin->update(['modulos_personalizados' => true]);
        // Sin ninguna fila en user_submodule_access: un usuario normal personalizado
        // sin filas quedaría sin acceso a nada, pero Administrador siempre pasa.

        $this->actingAs($admin)->get(route('flota.vehiculos.index'))->assertOk();
        $this->actingAs($admin)->get(route('seguridad.dispositivos.index'))->assertOk();
    }

    public function test_la_ruta_generica_de_submodulo_tambien_respeta_la_personalizacion(): void
    {
        // /modules/gente/plan-padrinos se sirve desde la ruta comodín
        // genérica (routes/web.php), no desde routes/gente.php.
        $user = $this->usuario('Gente');
        $user->update(['modulos_personalizados' => true]);
        UserSubmoduleAccess::create(['user_id' => $user->id, 'module_slug' => 'gente', 'submodule_key' => 'calificaciones']);

        $this->actingAs($user)->get('/modules/gente/plan-padrinos')->assertForbidden();

        UserSubmoduleAccess::create(['user_id' => $user->id, 'module_slug' => 'gente', 'submodule_key' => 'plan-padrinos']);
        $this->actingAs($user)->get('/modules/gente/plan-padrinos')->assertOk();
    }

    public function test_el_panel_de_administrador_guarda_la_personalizacion_al_crear_un_usuario(): void
    {
        $admin = $this->usuario('Administrador');

        $this->actingAs($admin)->post(route('admin.users.store'), [
            'first_name' => 'Ana',
            'last_name' => 'Ruiz',
            'identification_number' => '900111222',
            'email' => '',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'roles' => ['Gente'],
            'is_active' => true,
            'modulos_personalizados' => true,
            'submodulos' => ['gente' => ['calificaciones', 'ausentismo']],
        ])->assertRedirect(route('admin.users.index'));

        $creado = User::where('identification_number', '900111222')->firstOrFail();
        $this->assertTrue($creado->modulos_personalizados);
        $this->assertEqualsCanonicalizing(
            ['calificaciones', 'ausentismo'],
            $creado->submoduleAccess()->where('module_slug', 'gente')->pluck('submodule_key')->all(),
        );
    }

    public function test_el_panel_rechaza_un_submodulo_que_no_existe_en_el_catalogo(): void
    {
        $admin = $this->usuario('Administrador');

        $this->actingAs($admin)->post(route('admin.users.store'), [
            'first_name' => 'Ana',
            'last_name' => 'Ruiz',
            'identification_number' => '900111333',
            'email' => '',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'roles' => ['Gente'],
            'is_active' => true,
            'modulos_personalizados' => true,
            'submodulos' => ['gente' => ['no-existe']],
        ])->assertSessionHasErrors('submodulos');

        $this->assertSame(0, User::where('identification_number', '900111333')->count());
    }

    public function test_apagar_la_personalizacion_limpia_los_submodulos_otorgados(): void
    {
        $admin = $this->usuario('Administrador');
        $user = $this->usuario('Gente');
        $user->update(['modulos_personalizados' => true]);
        UserSubmoduleAccess::create(['user_id' => $user->id, 'module_slug' => 'gente', 'submodule_key' => 'calificaciones']);

        $this->actingAs($admin)->put(route('admin.users.update', $user), [
            'first_name' => 'Ana',
            'last_name' => 'Ruiz',
            'identification_number' => $user->identification_number,
            'email' => '',
            'roles' => ['Gente'],
            'is_active' => true,
            'modulos_personalizados' => false,
            'submodulos' => [],
        ])->assertRedirect(route('admin.users.index'));

        $user->refresh();
        $this->assertFalse($user->modulos_personalizados);
        $this->assertSame(0, $user->submoduleAccess()->count());
    }

    public function test_el_catalogo_de_submodulos_coincide_con_las_cantidades_esperadas(): void
    {
        $registro = ModuleAccessRegistry::all();

        $this->assertCount(24, $registro['seguridad']);
        $this->assertCount(9, $registro['gente']);
        $this->assertCount(4, $registro['flota']);
    }
}
