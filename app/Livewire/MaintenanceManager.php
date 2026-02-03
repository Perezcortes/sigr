<?php

namespace App\Livewire;

use App\Models\Ticket;
use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms;
use Filament\Notifications\Notification;
use Livewire\Component;
use Illuminate\Support\Facades\Auth;

class MaintenanceManager extends Component implements HasForms, HasActions
{
    use InteractsWithForms;
    use InteractsWithActions;

    public $rentId;

    public function mount($rentId)
    {
        $this->rentId = $rentId;
    }

    public function render()
    {
        // Separamos los tickets: Activos vs Terminados
        $tickets = Ticket::where('rent_id', $this->rentId)->orderBy('created_at', 'desc')->get();
        
        return view('livewire.maintenance-manager', [
            'activeTickets' => $tickets->where('estatus', '!=', 'terminado'),
            'completedTickets' => $tickets->where('estatus', 'terminado'),
        ]);
    }

    // --- AGREGAR REPORTE (Botón Naranja +) ---
    public function crearTicketAction(): Action
    {
        return Action::make('crearTicket')
            ->label('Agregar reporte')
            ->color('warning') // Naranja en Filament
            ->icon('heroicon-m-plus')
            ->button()
            ->modalHeading('Nuevo reporte de mantenimiento')
            ->form([
                Forms\Components\TextInput::make('titulo')
                    ->label('Tipo de problema (Título)')
                    ->placeholder('Ej: Fuga de agua, Ventana rota')
                    ->required(),
                
                Forms\Components\Textarea::make('descripcion')
                    ->label('Observaciones / Detalles')
                    ->rows(3)
                    ->required(),

                Forms\Components\FileUpload::make('evidencia')
                    ->label('Foto del daño')
                    ->image()
                    ->directory('mantenimiento'),
            ])
            ->action(function (array $data) {
                Ticket::create([
                    'rent_id' => $this->rentId,
                    'user_id' => Auth::id(),
                    'titulo' => $data['titulo'],
                    'descripcion' => $data['descripcion'],
                    'evidencia' => $data['evidencia'],
                    'estatus' => 'sin_revisar',
                ]);
                Notification::make()->title('Reporte creado')->success()->send();
            });
    }

    // --- ACCIÓN 2: VER/EDITAR REPORTE (Al dar clic en la lista) ---
    public function editarTicketAction(): Action
    {
        return Action::make('editarTicket')
            ->label('Detalles')
            ->modalHeading('Detalles del Reporte')
            ->model(Ticket::class)
            ->record(fn (array $arguments) => Ticket::find($arguments['record'] ?? null))
            ->fillForm(fn (Ticket $record) => [
                'evidencia_view' => $record->evidencia,
                'reportado_por' => $record->user->name ?? 'Usuario',
                'descripcion_view' => $record->descripcion,
                'estatus' => $record->estatus,
            ])
            ->form([
                // Imagen de Referencia
                Forms\Components\FileUpload::make('evidencia_view')
                    ->hiddenLabel()
                    ->image()
                    ->disabled() // Solo ver
                    ->dehydrated(false)
                    ->columnSpanFull(),

                // Reportado por
                Forms\Components\TextInput::make('reportado_por')
                    ->label('Reportado por:')
                    ->disabled()
                    ->prefixIcon('heroicon-m-user'),

                // Observaciones
                Forms\Components\Textarea::make('descripcion_view')
                    ->label('Observaciones')
                    ->disabled()
                    ->rows(3)
                    ->columnSpanFull(),

                // Estatus (Editable)
                Forms\Components\Select::make('estatus')
                    ->label('Estatus actual')
                    ->options([
                        'sin_revisar' => 'Sin Revisar',
                        'en_proceso' => 'En Proceso',
                        'terminado' => 'Terminado',
                    ])
                    ->selectablePlaceholder(false)
                    ->required()
                    ->native(false),
            ])
            ->modalSubmitActionLabel('Guardar cambios')
            ->color('warning') // Botón de guardar naranja
            ->action(function (Ticket $record, array $data) {
                $record->update(['estatus' => $data['estatus']]);
                Notification::make()->title('Estatus actualizado')->success()->send();
            });
    }
}