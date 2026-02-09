<?php

namespace App\Filament\Resources\TenantResource\Pages;

use App\Filament\Resources\TenantResource;
use App\Models\User; 
use Filament\Actions;
use Filament\Forms;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail; // Importar Mail
use App\Mail\TenantCredentialsMail; // Importar tu Mailable

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
                ->createAnother(false) 
                ->modalSubmitActionLabel('Crear Inquilino') 
                
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
                        ->visible(fn (Forms\Get $get) => $get('tipo_persona') === 'fisica')
                        ->maxLength(255),

                    // Campos Persona Moral
                    Forms\Components\TextInput::make('razon_social')
                        ->label('Razón Social')
                        ->required(fn (Forms\Get $get) => $get('tipo_persona') === 'moral')
                        ->visible(fn (Forms\Get $get) => $get('tipo_persona') === 'moral')
                        ->maxLength(255),

                    Forms\Components\TextInput::make('telefono') // Teléfono Oficina Moral
                        ->label('Teléfono Oficina')
                        ->tel()
                        ->required(fn (Forms\Get $get) => $get('tipo_persona') === 'moral')
                        ->visible(fn (Forms\Get $get) => $get('tipo_persona') === 'moral')
                        ->maxLength(20),

                    // Campos Comunes
                    Forms\Components\TextInput::make('email')
                        ->label('Correo')
                        ->email()
                        ->required()
                        ->unique('users', 'email') // Validar en USERS
                        ->maxLength(255),

                    Forms\Components\TextInput::make('telefono_celular') // Celular Física
                        ->label('Teléfono Celular')
                        ->tel()
                        ->required(fn (Forms\Get $get) => $get('tipo_persona') === 'fisica')
                        ->visible(fn (Forms\Get $get) => $get('tipo_persona') === 'fisica')
                        ->maxLength(20),

                    // === INTERRUPTOR DE ENVÍO DE CORREO ===
                    Forms\Components\Toggle::make('enviar_correo')
                        ->label('Enviar correo de bienvenida con credenciales')
                        ->default(true)
                        ->onColor('success')
                        ->columnSpanFull(),
                ])
                
                ->using(function (array $data, string $model): Model {
                    // Extraer decisión de envío
                    $enviarCorreo = $data['enviar_correo'] ?? false;
                    unset($data['enviar_correo']); // Quitar para no guardar en BD

                    $data['asesor_id'] = auth()->id();

                    if ($data['tipo_persona'] === 'fisica') {
                        $nombreUsuario = $data['nombres'] . ' ' . $data['primer_apellido'] . ' ' . ($data['segundo_apellido'] ?? '');
                        $data['razon_social'] = null;
                        $data['telefono'] = null;
                    } else {
                        $nombreUsuario = $data['razon_social'];
                        $data['nombres'] = null;
                        $data['primer_apellido'] = null;
                        $data['segundo_apellido'] = null;
                        $data['telefono_celular'] = null;
                    }

                    // Generar Contraseña Temporal
                    $plainPassword = \Illuminate\Support\Str::random(10); 

                    // Crear Usuario
                    $user = User::firstOrCreate(
                        ['email' => $data['email']],
                        [
                            'name' => trim($nombreUsuario),
                            'password' => Hash::make($plainPassword), // Encriptada
                            'is_active' => true,
                            'is_tenant' => true,
                            'is_owner' => false,
                        ]
                    );

                    if (!$user->is_tenant) {
                        $user->update(['is_tenant' => true]);
                    }

                    $data['user_id'] = $user->id;

                    // Crear Registro
                    $record = $model::create($data);

                    // Guardar datos temporales en el objeto para el hook 'after'
                    $record->temp_enviar_correo = $enviarCorreo;
                    $record->temp_plain_password = $plainPassword; // Necesitamos la plana para el correo
                    $record->temp_user = $user;

                    return $record;
                })

                ->after(function (Model $record) {
                    // Recuperar datos temporales
                    if ($record->temp_enviar_correo && isset($record->temp_plain_password)) {
                        try {
                            Mail::to($record->email)->send(
                                new TenantCredentialsMail($record->temp_user, $record->temp_plain_password)
                            );
                            
                            \Filament\Notifications\Notification::make()
                                ->success()
                                ->title('Inquilino creado y correo enviado')
                                ->body("Se envió la bienvenida a {$record->email}")
                                ->send();
                        } catch (\Exception $e) {
                            \Filament\Notifications\Notification::make()
                                ->warning()
                                ->title('Inquilino creado, pero falló el correo')
                                ->body($e->getMessage())
                                ->send();
                        }
                    } else {
                        \Filament\Notifications\Notification::make()
                            ->success()
                            ->title('Inquilino creado exitosamente')
                            ->send();
                    }
                }),
        ];
    }
}