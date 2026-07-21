<?php

namespace App\Filament\Resources\LeadResource\Pages;

use App\Filament\Resources\LeadResource;
use App\Services\LeadProspectProcessor;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;

class CreateLead extends CreateRecord
{
    protected static string $resource = LeadResource::class;

    protected ?string $prospectProcessRedirectUrl = null;

    protected function afterCreate(): void
    {
        $this->record->refresh();

        $processor = app(LeadProspectProcessor::class);

        if (! $processor->shouldProcessOnSave($this->record)) {
            return;
        }

        $result = $processor->process($this->record);

        if ($result['notification_title']) {
            $notification = Notification::make()->title($result['notification_title']);

            if ($result['notification_warning']) {
                $notification->warning()->send();
            } else {
                $notification->success()->send();
            }
        }

        if ($result['redirect']) {
            $this->prospectProcessRedirectUrl = $result['redirect'];
        }
    }

    protected function getRedirectUrl(): string
    {
        return $this->prospectProcessRedirectUrl
            ?? $this->getResource()::getUrl('edit', ['record' => $this->record]);
    }

    protected function getCreatedNotification(): ?Notification
    {
        if ($this->prospectProcessRedirectUrl !== null) {
            return null;
        }

        return parent::getCreatedNotification();
    }
}
