<?php

namespace App\Filament\Resources\OwnerResource\Pages;

use App\Filament\Resources\OwnerResource;
use Filament\Actions;
use Filament\Forms;
use Filament\Resources\Pages\ListRecords;

class ListOwners extends ListRecords
{
    protected static string $resource = OwnerResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Crear Propietario')
                ->modalHeading('Crear Propietario Rápido')
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
                        ->columnSpanFull(),

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

                    Forms\Components\TextInput::make('razon_social')
                        ->label('Razón Social')
                        ->required(fn (Forms\Get $get) => $get('tipo_persona') === 'moral')
                        ->visible(fn (Forms\Get $get) => $get('tipo_persona') === 'moral')
                        ->maxLength(255),

                    Forms\Components\TextInput::make('email')
                        ->label('Correo')
                        ->email()
                        ->required()
                        // IMPORTANTE: Validar único en la tabla USERS, no solo en owners
                        ->unique('users', 'email') 
                        ->maxLength(255),

                    Forms\Components\TextInput::make('telefono')
                        ->label('Teléfono')
                        ->tel()
                        ->required()
                        ->maxLength(20),
                ])
                // Guardar tanto en owners como en users
                ->using(function (array $data, string $model): \Illuminate\Database\Eloquent\Model {
                    
                    // Asignar el asesor actual
                    $data['asesor_id'] = auth()->id();

                    // 1. Construir el nombre completo para el Usuario
                    if ($data['tipo_persona'] === 'fisica') {
                        $name = $data['nombres'] . ' ' . $data['primer_apellido'] . ' ' . ($data['segundo_apellido'] ?? '');
                    } else {
                        $name = $data['razon_social'];
                        // Limpiamos campos de persona física para la BD de owners
                        $data['nombres'] = null;
                        $data['primer_apellido'] = null;
                        $data['segundo_apellido'] = null;
                    }

                    // 2. Crear (o buscar) el Usuario en la tabla `users`
                    // Usamos firstOrCreate por seguridad, aunque la validación unique debería prevenirlo
                    $user = \App\Models\User::firstOrCreate(
                        ['email' => $data['email']],
                        [
                            'name' => trim($name),
                            'password' => \Illuminate\Support\Facades\Hash::make('password123'), // Contraseña temporal
                            'is_active' => true,
                            'is_owner' => true,  // <--- ACTIVAMOS EL FLAG
                            'is_tenant' => false,
                        ]
                    );

                    // Si el usuario ya existía (por ejemplo era inquilino), aseguramos que sea owner también
                    if (!$user->is_owner) {
                        $user->update(['is_owner' => true]);
                    }

                    // 3. Crear el registro en `owners` vinculado al usuario
                    // El modelo Owner debe tener 'user_id' en su $fillable
                    $data['user_id'] = $user->id;

                    return $model::create($data);
                }),
        ];
    }
}
