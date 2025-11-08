<?php

namespace App\Filament\Resources\TenantResource\Pages;

use App\Filament\Resources\TenantResource;
use Filament\Actions;
use Filament\Forms;
use Filament\Resources\Pages\ListRecords;

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
                        ->unique(ignoreRecord: true)
                        ->maxLength(255),

                    Forms\Components\TextInput::make('telefono_celular')
                        ->label('Teléfono')
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
                        ->label('Teléfono')
                        ->tel()
                        ->required(fn (Forms\Get $get) => $get('tipo_persona') === 'moral')
                        ->visible(fn (Forms\Get $get) => $get('tipo_persona') === 'moral')
                        ->maxLength(20),
                ])
                ->mutateFormDataUsing(function (array $data): array {
                    // Si es persona moral, asegurar que telefono_celular sea null
                    if ($data['tipo_persona'] === 'moral') {
                        $data['telefono_celular'] = null;
                    }
                    // Si es persona física, asegurar que telefono sea null
                    if ($data['tipo_persona'] === 'fisica') {
                        $data['telefono'] = null;
                    }
                    return $data;
                }),
        ];
    }
}
