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
        $this->record = $record->load(['rent', 'tenant']);
        
        $this->form->fill($this->record->attributesToArray());
    }

    public function form(Form $form): Form
    {
        return TenantRequestResource::form($form)
            ->statePath('data')
            ->model($this->record)
            ->operation('edit'); 
    }

    public function save(): void
    {
        $data = $this->form->getState();
        
        $this->record->update($data);
        $this->record->update(['estatus' => 'en_proceso']);

        if (method_exists($this->record, 'syncWithTenant')) {
            $this->record->syncWithTenant();
        }

        $this->isSubmitted = true;
    }

    public function render()
    {
        return view('livewire.public-tenant-request')
            ->layout('components.layouts.public');
    }
}