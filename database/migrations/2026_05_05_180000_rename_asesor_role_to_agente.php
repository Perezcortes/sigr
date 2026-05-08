<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $guard = 'web';

        $asesor = DB::table('roles')->where('guard_name', $guard)->where('name', 'Asesor')->first();
        $agente = DB::table('roles')->where('guard_name', $guard)->where('name', 'Agente')->first();

        if (! $asesor) {
            return;
        }

        if (! $agente) {
            DB::table('roles')->where('id', $asesor->id)->update(['name' => 'Agente']);

            return;
        }

        if ((int) $asesor->id === (int) $agente->id) {
            return;
        }

        DB::table('model_has_roles')->where('role_id', $asesor->id)->update(['role_id' => $agente->id]);

        $permissionIds = DB::table('role_has_permissions')->where('role_id', $asesor->id)->pluck('permission_id');
        foreach ($permissionIds as $permissionId) {
            $exists = DB::table('role_has_permissions')
                ->where('role_id', $agente->id)
                ->where('permission_id', $permissionId)
                ->exists();
            if (! $exists) {
                DB::table('role_has_permissions')->insert([
                    'role_id' => $agente->id,
                    'permission_id' => $permissionId,
                ]);
            }
        }

        DB::table('role_has_permissions')->where('role_id', $asesor->id)->delete();
        DB::table('roles')->where('id', $asesor->id)->delete();
    }

    public function down(): void
    {
        $guard = 'web';
        $agente = DB::table('roles')->where('guard_name', $guard)->where('name', 'Agente')->first();
        if ($agente) {
            DB::table('roles')->where('id', $agente->id)->update(['name' => 'Asesor']);
        }
    }
};
