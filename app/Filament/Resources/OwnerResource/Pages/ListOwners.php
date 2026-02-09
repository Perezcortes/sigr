<?php

namespace App\Filament\Resources\OwnerResource\Pages;

use App\Filament\Resources\OwnerResource;
use App\Models\User; 
use Filament\Actions;
use Filament\Forms;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail; // Importar Mail
use App\Mail\TenantCredentialsMail; // Reutilizamos el mismo correo

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
                ->createAnother(false)
                
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

                    // Física
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
                        ->visible(fn (Forms\Get $get) => $get('tipo_persona') === 'fisica')
                        ->maxLength(255),

                    // Moral
                    Forms\Components\TextInput::make('razon_social')
                        ->label('Razón Social')
                        ->required(fn (Forms\Get $get) => $get('tipo_persona') === 'moral')
                        ->visible(fn (Forms\Get $get) => $get('tipo_persona') === 'moral')
                        ->maxLength(255),

                    // Comunes
                    Forms\Components\TextInput::make('email')
                        ->label('Correo')
                        ->email()
                        ->required()
                        ->unique('users', 'email') // Validar en Users
                        ->maxLength(255),

                    Forms\Components\TextInput::make('telefono')
                        ->label('Teléfono')
                        ->tel()
                        ->required()
                        ->maxLength(20),

                    // === INTERRUPTOR CORREO ===
                    Forms\Components\Toggle::make('enviar_correo')
                        ->label('Enviar correo de bienvenida con credenciales')
                        ->default(true)
                        ->onColor('success')
                        ->columnSpanFull(),
                ])
                
                ->using(function (array $data, string $model): Model {
                    
                    $enviarCorreo = $data['enviar_correo'] ?? false;
                    unset($data['enviar_correo']);

                    $data['asesor_id'] = auth()->id();

                    if ($data['tipo_persona'] === 'fisica') {
                        $name = $data['nombres'] . ' ' . $data['primer_apellido'] . ' ' . ($data['segundo_apellido'] ?? '');
                    } else {
                        $name = $data['razon_social'];
                        $data['nombres'] = null;
                        $data['primer_apellido'] = null;
                        $data['segundo_apellido'] = null;
                    }

                    // Password temporal
                    $plainPassword = \Illuminate\Support\Str::random(10);

                    // Usuario
                    $user = User::firstOrCreate(
                        ['email' => $data['email']],
                        [
                            'name' => trim($name),
                            'password' => Hash::make($plainPassword),
                            'is_active' => true,
                            'is_owner' => true, // Flag Owner
                            'is_tenant' => false,
                        ]
                    );

                    if (!$user->is_owner) {
                        $user->update(['is_owner' => true]);
                    }

                    $data['user_id'] = $user->id;

                    $record = $model::create($data);

                    // Datos temporales para after()
                    $record->temp_enviar_correo = $enviarCorreo;
                    $record->temp_plain_password = $plainPassword;
                    $record->temp_user = $user;

                    return $record;
                })

                ->after(function (Model $record) {
                    if ($record->temp_enviar_correo && isset($record->temp_plain_password)) {
                        try {
                            Mail::to($record->email)->send(
                                new TenantCredentialsMail($record->temp_user, $record->temp_plain_password)
                            );
                            
                            \Filament\Notifications\Notification::make()
                                ->success()
                                ->title('Propietario creado y correo enviado')
                                ->send();
                        } catch (\Exception $e) {
                            \Filament\Notifications\Notification::make()
                                ->warning()
                                ->title('Propietario creado, pero falló el correo')
                                ->body($e->getMessage())
                                ->send();
                        }
                    } else {
                        \Filament\Notifications\Notification::make()
                            ->success()
                            ->title('Propietario creado exitosamente')
                            ->send();
                    }
                }),
        ];
    }
}