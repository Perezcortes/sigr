<?php

namespace App\Livewire;

use App\Models\OwnerRequest;
use App\Filament\Resources\OwnerRequestResource;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Livewire\Component;

class PublicOwnerRequest extends Component implements HasForms
{
    use InteractsWithForms;

    public ?array $data = [];
    public OwnerRequest $record;
    public bool $isSubmitted = false;

    public function mount(OwnerRequest $record): void
    {
        // Cargamos relaciones para que no oculte las pestañas
        $this->record = $record->load(['rent', 'owner']);
        
        // Extraemos los datos base
        $data = $this->record->attributesToArray();
        
        // Inyectamos el ID de la propiedad 
        $data['selected_property_id'] = $this->record->rent?->property_id;
        
        $this->form->fill($data);
    }

    public function form(Form $form): Form
    {
        return OwnerRequestResource::form($form)
            ->statePath('data')
            ->model($this->record)
            ->operation('edit'); 
    }

    public function save(): void
    {
        $data = $this->form->getState();

        unset($data['selected_property_id']);
        
        $this->record->update($data);
        $this->record->update(['estatus' => 'en_proceso']);

        if (method_exists($this->record, 'syncWithOwner')) {
            $this->record->syncWithOwner();
        }

        $this->isSubmitted = true;
    }

    public function render()
    {
        return view('livewire.public-owner-request')
            ->layout('components.layouts.public'); // Reutilizamos el layout universal
    }
}