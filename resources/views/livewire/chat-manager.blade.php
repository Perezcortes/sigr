<div class="flex flex-col h-[500px] bg-white border border-gray-200 rounded-xl shadow-sm dark:bg-gray-800 dark:border-gray-700">
    
    <div class="p-4 border-b border-gray-100 dark:border-gray-700 bg-gray-50 rounded-t-xl dark:bg-gray-900 flex justify-between items-center">
        <div>
            <h3 class="font-bold text-gray-800 dark:text-white">Chat de la Renta</h3>
            <p class="text-xs text-gray-500">Historial de mensajes</p>
        </div>
        <div wire:poll.3s class="flex items-center gap-2">
            <span class="relative flex h-2 w-2">
              <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-green-400 opacity-75"></span>
              <span class="relative inline-flex rounded-full h-2 w-2 bg-green-500"></span>
            </span>
            <span class="text-xs text-green-600 font-medium">En vivo</span>
        </div>
    </div>

    <div class="flex-1 overflow-y-auto p-4 space-y-4 bg-gray-50/30 dark:bg-gray-900/50" id="chat-box-{{ $this->rentId }}">
        @forelse($messages as $msg)
            @php
                $isMe = $msg->user_id === auth()->id();
            @endphp

            <div class="flex w-full {{ $isMe ? 'justify-end' : 'justify-start' }}">
                <div class="flex max-w-[80%] {{ $isMe ? 'flex-row-reverse' : 'flex-row' }} gap-2">
                    
                    <img class="w-8 h-8 rounded-full border border-gray-200 mt-1" 
                         src="{{ $msg->user->profile_photo_url ?? 'https://ui-avatars.com/api/?name='.urlencode($msg->user->name ?? 'User') }}" 
                         alt="Avatar">

                    <div class="flex flex-col {{ $isMe ? 'items-end' : 'items-start' }}">
                        <div class="px-4 py-2 text-sm shadow-sm rounded-2xl 
                                    {{ $isMe 
                                        ? 'bg-primary-600 text-white rounded-tr-none' 
                                        : 'bg-white text-gray-700 border border-gray-200 rounded-tl-none dark:bg-gray-700 dark:text-gray-200 dark:border-gray-600' 
                                    }}">
                            {{ $msg->cuerpo }}
                        </div>
                        <span class="text-[10px] text-gray-400 mt-1 px-1">
                            {{ $msg->created_at->format('d/m h:i A') }}
                        </span>
                    </div>
                </div>
            </div>
        @empty
            <div class="h-full flex flex-col items-center justify-center text-gray-400 opacity-50">
                <x-filament::icon icon="heroicon-o-chat-bubble-left-right" class="w-12 h-12 mb-2" />
                <p>No hay mensajes aún.</p>
            </div>
        @endforelse
    </div>

    <div class="p-3 bg-white border-t border-gray-100 rounded-b-xl dark:bg-gray-800 dark:border-gray-700">
        <form wire:submit.prevent="sendMessage" class="flex gap-2">
            <input 
                type="text" 
                wire:model="newMessage"
                class="w-full border-gray-300 rounded-lg focus:border-primary-500 focus:ring-primary-500 text-sm px-4 py-2.5 bg-gray-50 dark:bg-gray-900 dark:border-gray-600 dark:text-white"
                placeholder="Escribe un mensaje..."
                required
            >
            <button type="submit" class="p-2.5 bg-primary-600 text-white rounded-lg hover:bg-primary-700 transition shadow-sm">
                <x-filament::icon icon="heroicon-m-paper-airplane" class="w-5 h-5 -rotate-45" />
            </button>
        </form>
    </div>

    <script>
        // Script simple para bajar el scroll al enviar mensaje
        document.addEventListener('livewire:initialized', () => {
            const scrollBottom = () => {
                const chatBox = document.getElementById('chat-box-{{ $this->rentId }}');
                if(chatBox) chatBox.scrollTop = chatBox.scrollHeight;
            }
            
            scrollBottom(); // Al cargar
            Livewire.on('message-sent', () => setTimeout(scrollBottom, 100)); // Al enviar
        });
    </script>
</div>