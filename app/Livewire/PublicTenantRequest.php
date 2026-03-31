<?php

namespace App\Livewire;

use App\Models\TenantRequest;
use App\Filament\Resources\TenantRequestResource;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Livewire\Component;

class PublicTenantRequest extends Component implements HasForms
{
    use InteractsWithForms;

    public ?array $data = [];
    public TenantRequest $record;
    public bool $isSubmitted = false;

    public function mount(TenantRequest $record): void
    {
        // Guardamos el registro y llenamos el formulario con los datos que ya tenga
        $this->record = $record;
        $this->form->fill($record->toArray());
    }

    public function form(Form $form): Form
    {
        // ¡Magia! Reciclamos exactamente el mismo formulario que tienes en tu panel de Admin
        return TenantRequestResource::form($form)
            ->statePath('data')
            ->model($this->record);
    }

    public function save(): void
    {
        // Obtenemos la información, actualizamos la base de datos y marcamos como enviado
        $data = $this->form->getState();
        $this->record->update($data);
        
        // Opcional: Cambiar el estatus automáticamente a 'en_proceso' cuando el cliente lo llena
        $this->record->update(['estatus' => 'en_proceso']);

        $this->isSubmitted = true;
    }

    public function render()
    {
        // Le indicamos que use el layout público sin menú que creamos en el Paso 1
        return view('livewire.public-tenant-request')
            ->layout('components.layouts.public');
    }
}