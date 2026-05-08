@php
    /** @var \App\Models\Lead|null $record */
    $record = $getRecord();
    $messages = collect();

    if ($record) {
        $messages = $record->whatsappConversation()->latest('created_at')->limit(100)->get()->sortBy('created_at');
    }
@endphp

<div wire:poll.8s class="space-y-3">
    <div class="text-xs text-gray-500">
        Conversación vinculada al interesado.
    </div>

    @if (! $record || $messages->isEmpty())
        <div class="rounded-lg border border-dashed border-gray-300 p-4 text-sm text-gray-500">
            Aún no hay mensajes en este chat.
        </div>
    @else
        <div class="max-h-[26rem] space-y-2 overflow-y-auto rounded-lg border border-gray-200 bg-gray-50 p-3 dark:border-gray-700 dark:bg-gray-900/40">
            @foreach ($messages as $message)
                @php
                    $incoming = $message->direction === 'in';
                    $body     = (string) ($message->body ?? '');
                @endphp

                <div class="flex {{ $incoming ? 'justify-start' : 'justify-end' }}">
                    <div class="max-w-[78%] rounded-xl px-3 py-2 text-sm {{ $incoming ? 'bg-white shadow dark:bg-gray-800' : 'bg-primary-600 text-white' }}">
                        <div class="mb-1 text-[11px] opacity-70">
                            {{ $incoming ? 'Prospecto' : 'Asesor' }}
                            ·
                            {{ optional($message->sent_at ?? $message->created_at)->format('d/m/Y H:i') }}
                        </div>

                        @if ($body !== '')
                            <div class="whitespace-pre-wrap">{{ $body }}</div>
                        @else
                            <div class="italic opacity-60">Mensaje sin texto</div>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>
