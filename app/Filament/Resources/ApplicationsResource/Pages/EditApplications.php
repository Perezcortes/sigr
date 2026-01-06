<?php

namespace App\Filament\Resources\ApplicationsResource\Pages;

use App\Filament\Resources\ApplicationsResource;
use App\Models\ApplicationDocument;
use App\Models\Tenant;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Contracts\Support\Htmlable;

class EditApplications extends EditRecord
{
    protected static string $resource = ApplicationsResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    public function mount(int | string $record): void
    {
        parent::mount($record);

        // Cargar relaciones necesarias
        $this->record->load('documents');

        // Cargar tipo_persona desde el tenant si existe
        if ($this->record->user && $this->record->user->is_tenant) {
            $tenant = Tenant::where('user_id', $this->record->user_id)->first();
            if ($tenant && !$this->record->tipo_persona) {
                $this->data['tipo_persona'] = $tenant->tipo_persona ?? 'fisica';
            }
        }
    }

    public function deleteApplicationDocument(int $id): void
    {
        $document = ApplicationDocument::find($id);
        if ($document && $document->application_id === $this->record->id) {
            $document->delete();
            \Filament\Notifications\Notification::make()
                ->success()
                ->title('Documento eliminado')
                ->send();
            
            // Recargar el registro para actualizar la lista de documentos
            $this->record->load('documents');
        }
    }
}
