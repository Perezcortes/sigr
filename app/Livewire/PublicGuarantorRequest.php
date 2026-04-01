<?php

namespace App\Livewire;

use App\Models\GuarantorRequest;
use App\Filament\Resources\GuarantorRequestResource;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Livewire\Component;

class PublicGuarantorRequest extends Component implements HasForms
{
    use InteractsWithForms;

    public ?array $data = [];
    public GuarantorRequest $record;
    public bool $isSubmitted = false;

    public function mount(GuarantorRequest $record): void
    {
        $this->record = $record->load(['rent']); 
        $this->form->fill($this->record->attributesToArray());
    }

    public function form(Form $form): Form
    {
        return GuarantorRequestResource::form($form)
            ->statePath('data')
            ->model($this->record)
            ->operation('edit');
    }

    public function save(): void
    {
        $data = $this->form->getState();
        
        $this->record->update($data);
        $this->record->update(['estatus' => 'en_proceso']);

        if (method_exists($this->record, 'syncWithGuarantor')) {
            $this->record->syncWithGuarantor();
        }

        $this->isSubmitted = true;
    }

    public function render()
    {
        return view('livewire.public-guarantor-request')
            ->layout('components.layouts.public');
    }
}