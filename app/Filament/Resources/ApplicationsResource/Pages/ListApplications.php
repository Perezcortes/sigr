<?php

namespace App\Filament\Resources\ApplicationsResource\Pages;

use App\Filament\Resources\ApplicationsResource;
use App\Models\Application;
use App\Models\User;
use Filament\Actions;
use Filament\Forms;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;

class ListApplications extends ListRecords
{
    protected static string $resource = ApplicationsResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('crear_solicitud')
                ->label('Crear Solicitud')
                ->icon('heroicon-o-plus')
                ->color('primary')
                ->modalHeading('Crear Nueva Solicitud')
                ->modalWidth('md')
                ->form([
                    Forms\Components\Select::make('user_id')
                        ->label('Inquilino')
                        ->relationship('user', 'name', modifyQueryUsing: fn (Builder $query) => $query->where('is_tenant', true))
                        ->getOptionLabelFromRecordUsing(fn (User $record) => $record->name . ' (' . $record->email . ')')
                        ->searchable(['name', 'email'])
                        ->preload()
                        ->required()
                        ->live(),

                    Forms\Components\Select::make('tipo_persona')
                        ->label('Tipo de Persona')
                        ->options([
                            'fisica' => 'Persona Física',
                            'moral' => 'Persona Moral',
                        ])
                        ->required()
                        ->live()
                        ->default('fisica'),

                    Forms\Components\Select::make('tipo_inmueble')
                        ->label('Tipo de Inmueble')
                        ->options([
                            'residencial' => 'Inmuebles Residenciales',
                            'comercial' => 'Inmuebles Comerciales',
                        ])
                        ->required()
                        ->live()
                        ->default('residencial'),
                ])
                ->action(function (array $data) {
                    // Crear la Application con los datos básicos
                    $application = Application::create([
                        'user_id' => $data['user_id'],
                        'tipo_persona' => $data['tipo_persona'],
                        'tipo_inmueble' => $data['tipo_inmueble'],
                        'estatus' => 'pendiente',
                    ]);

                    // Redirigir a EditApplications
                    return redirect(ApplicationsResource::getUrl('edit', ['record' => $application->id]));
                }),
        ];
    }
}
