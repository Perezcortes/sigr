<?php

namespace App\Filament\Resources\TenantResource\Pages;

use App\Filament\Resources\TenantResource;
use App\Models\User;
use Filament\Actions;
use Filament\Forms;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Hash;

class ListTenants extends ListRecords
{
    protected static string $resource = TenantResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Crear Inquilino')
                ->modalHeading('Crear Inquilino Rápido')
                ->modalWidth('md')
                ->form([
                    Forms\Components\Radio::make('tipo_persona')
                        ->label('Tipo de Persona')
                        ->options([
                            'fisica' => 'Persona Física',
                            'moral' => 'Persona Moral',
                        ])
                        ->required()
                        ->live()
                        ->default('fisica')
                        ->columnSpanFull(),

                    // Campos Persona Física
                    Forms\Components\TextInput::make('nombres')
                        ->label('Nombre(s)')
                        ->required(fn (Forms\Get $get) => $get('tipo_persona') === 'fisica')
                        ->visible(fn (Forms\Get $get) => $get('tipo_persona') === 'fisica')
                        ->maxLength(255),

                    Forms\Components\TextInput::make('primer_apellido')
                        ->label('Primer Apellido')
                        ->required(fn (Forms\Get $get) => $get('tipo_persona') === 'fisica')
                        ->visible(fn (Forms\Get $get) => $get('tipo_persona') === 'fisica')
                        ->maxLength(255),

                    Forms\Components\TextInput::make('segundo_apellido')
                        ->label('Segundo Apellido')
                        ->visible(fn (Forms\Get $get) => $get('tipo_persona') === 'fisica')
                        ->maxLength(255),

                    Forms\Components\TextInput::make('email')
                        ->label('Correo')
                        ->email()
                        ->required()
                        // IMPORTANTE: Validamos unicidad en la tabla USERS para evitar conflictos de login
                        ->unique('users', 'email', ignoreRecord: true)
                        ->maxLength(255),

                    Forms\Components\TextInput::make('telefono_celular')
                        ->label('Teléfono Celular')
                        ->tel()
                        ->required(fn (Forms\Get $get) => $get('tipo_persona') === 'fisica')
                        ->visible(fn (Forms\Get $get) => $get('tipo_persona') === 'fisica')
                        ->maxLength(20),

                    // Campos Persona Moral
                    Forms\Components\TextInput::make('razon_social')
                        ->label('Razón Social')
                        ->required(fn (Forms\Get $get) => $get('tipo_persona') === 'moral')
                        ->visible(fn (Forms\Get $get) => $get('tipo_persona') === 'moral')
                        ->maxLength(255),

                    Forms\Components\TextInput::make('telefono')
                        ->label('Teléfono Oficina')
                        ->tel()
                        ->required(fn (Forms\Get $get) => $get('tipo_persona') === 'moral')
                        ->visible(fn (Forms\Get $get) => $get('tipo_persona') === 'moral')
                        ->maxLength(20),
                ])
                // === LÓGICA DE VINCULACIÓN ===
                ->using(function (array $data, string $model): Model {

                    $data['asesor_id'] = auth()->id(); // Guardamos quién lo creó
                    // 1. Determinar el nombre para el usuario y limpiar datos
                    if ($data['tipo_persona'] === 'fisica') {
                        $nombreUsuario = $data['nombres'] . ' ' . $data['primer_apellido'] . ' ' . ($data['segundo_apellido'] ?? '');
                        // Limpiar campos que no corresponden
                        $data['razon_social'] = null;
                        $data['telefono'] = null; // Telefono fijo de moral
                    } else {
                        $nombreUsuario = $data['razon_social'];
                        // Limpiar campos que no corresponden
                        $data['nombres'] = null;
                        $data['primer_apellido'] = null;
                        $data['segundo_apellido'] = null;
                        $data['telefono_celular'] = null;
                    }

                    // 2. Crear o Buscar el Usuario en la tabla `users`
                    $user = User::firstOrCreate(
                        ['email' => $data['email']],
                        [
                            'name' => trim($nombreUsuario),
                            'password' => Hash::make('password123'), // Contraseña por defecto
                            'is_active' => true,
                            'is_tenant' => true, // Activar flag
                            'is_owner' => false,
                        ]
                    );

                    // 3. Si el usuario ya existía, nos aseguramos que tenga el flag activado
                    if (!$user->is_tenant) {
                        $user->update(['is_tenant' => true]);
                    }

                    // 4. Asignar el user_id a los datos del Inquilino
                    $data['user_id'] = $user->id;

                    // 5. Crear y retornar el Inquilino
                    return $model::create($data);
                }),
        ];
    }
}