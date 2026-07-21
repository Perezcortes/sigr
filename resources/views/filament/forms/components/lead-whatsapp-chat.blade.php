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
        <div class="max-h-[28rem] space-y-3 overflow-y-auto rounded-lg border border-gray-200 bg-[#efeae2] dark:bg-[#0b141a] p-4 shadow-inner dark:border-gray-800">
            @foreach ($messages as $message)
                @php
                    $incoming = $message->direction === 'in';
                    $body     = (string) ($message->body ?? '');
                @endphp

                <div class="flex {{ $incoming ? 'justify-start' : 'justify-end' }}">
                    <div class="max-w-[75%] shadow-sm px-3 py-2 text-sm relative {{ $incoming ? 'bg-white text-gray-900 rounded-lg rounded-tl-none dark:bg-[#202c33] dark:text-gray-100' : 'bg-[#d9fdd3] text-gray-900 rounded-lg rounded-tr-none dark:bg-[#005c4b] dark:text-gray-100' }}">
                        <div class="mb-1 text-[10px] font-semibold opacity-65 flex justify-between gap-4">
                            <span>{{ $incoming ? 'Prospecto' : 'Asesor' }}</span>
                            <span>{{ optional($message->sent_at ?? $message->created_at)->format('H:i') }}</span>
                        </div>

                        @if ($body !== '')
                            <div class="whitespace-pre-wrap leading-relaxed">{{ $body }}</div>
                        @else
                            <div class="italic opacity-60">Mensaje sin texto</div>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>
