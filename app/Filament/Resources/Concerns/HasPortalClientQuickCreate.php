<?php

namespace App\Filament\Resources\Concerns;

use App\Mail\TenantCredentialsMail;
use App\Models\User;
use Filament\Actions;
use Filament\Forms;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

trait HasPortalClientQuickCreate
{
    protected function portalClientCreateAction(string $entityLabel): Actions\CreateAction
    {
        $flagColumn = static::$resource::portalFlagColumn();

        return Actions\CreateAction::make()
            ->label("Crear {$entityLabel}")
            ->modalHeading("Crear {$entityLabel} Rápido")
            ->modalWidth('md')
            ->createAnother(false)
            ->modalSubmitActionLabel("Crear {$entityLabel}")
            ->form([
                Forms\Components\TextInput::make('name')
                    ->label('Nombre completo')
                    ->required()
                    ->maxLength(255),

                Forms\Components\TextInput::make('email')
                    ->label('Correo')
                    ->email()
                    ->required()
                    ->unique('users', 'email')
                    ->maxLength(255),

                Forms\Components\TextInput::make('mobile')
                    ->label('Teléfono celular')
                    ->tel()
                    ->required()
                    ->maxLength(20),

                Forms\Components\TextInput::make('telefono')
                    ->label('Teléfono fijo')
                    ->tel()
                    ->maxLength(20),

                Forms\Components\Toggle::make('enviar_correo')
                    ->label('Enviar correo de bienvenida con credenciales')
                    ->default(true)
                    ->onColor('success')
                    ->columnSpanFull(),
            ])
            ->using(function (array $data, string $model) use ($flagColumn): Model {
                $enviarCorreo = $data['enviar_correo'] ?? false;
                unset($data['enviar_correo']);

                $plainPassword = Str::random(10);
                $creator = auth()->user();

                $attributes = [
                    'name' => trim($data['name']),
                    'mobile' => $data['mobile'],
                    'telefono' => $data['telefono'] ?? null,
                    'password' => Hash::make($plainPassword),
                    'is_active' => true,
                    $flagColumn => true,
                ];

                if ($creator->hasRole('Agente')) {
                    $attributes['asesor_id'] = $creator->id;
                    $attributes['office_id'] = $creator->office_id;
                }

                $user = User::firstOrCreate(
                    ['email' => $data['email']],
                    $attributes
                );

                $wasNew = $user->wasRecentlyCreated;

                if (! $wasNew) {
                    $updates = [
                        'name' => trim($data['name']),
                        'mobile' => $data['mobile'],
                        'telefono' => $data['telefono'] ?? null,
                        $flagColumn => true,
                    ];

                    if ($creator->hasRole('Agente')) {
                        $updates['asesor_id'] = $creator->id;
                        $updates['office_id'] = $creator->office_id;
                    }

                    $user->update($updates);
                } elseif ($creator->hasRole('Agente') && $user->asesor_id !== $creator->id) {
                    $user->update([
                        'asesor_id' => $creator->id,
                        'office_id' => $creator->office_id,
                    ]);
                }

                $user->temp_was_new = $wasNew;
                $user->temp_enviar_correo = $enviarCorreo && $wasNew;
                $user->temp_plain_password = $wasNew ? $plainPassword : null;

                return $user;
            })
            ->after(function (Model $record) use ($entityLabel): void {
                if (! ($record->temp_was_new ?? true)) {
                    Notification::make()
                        ->info()
                        ->title('Cuenta ya existía')
                        ->body("Se actualizó como {$entityLabel}. No se envió correo (la contraseña no se regeneró).")
                        ->send();

                    return;
                }

                if ($record->temp_enviar_correo && isset($record->temp_plain_password)) {
                    try {
                        Mail::to($record->email)->send(
                            new TenantCredentialsMail($record, $record->temp_plain_password)
                        );

                        Notification::make()
                            ->success()
                            ->title("{$entityLabel} creado y correo enviado")
                            ->body("Se envió la bienvenida a {$record->email}")
                            ->send();
                    } catch (\Exception $e) {
                        Notification::make()
                            ->warning()
                            ->title("{$entityLabel} creado, pero falló el correo")
                            ->body($e->getMessage())
                            ->send();
                    }
                } else {
                    Notification::make()
                        ->success()
                        ->title("{$entityLabel} creado exitosamente")
                        ->send();
                }
            });
    }
}
