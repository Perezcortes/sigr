<div class="flex flex-col h-[600px] bg-[#e5ddd5] dark:bg-[#0b141a] border border-gray-200 rounded-xl shadow-2xl dark:border-gray-700 overflow-hidden relative">
    
    <div class="absolute inset-0 opacity-10 pointer-events-none" 
         style="background-image: url('https://user-images.githubusercontent.com/15075759/28719144-86dc0f70-73b1-11e7-911d-60d70fcded21.png'); background-size: 400px;">
    </div>

    <div class="relative z-10 p-3 bg-[#161848] flex justify-between items-center shadow-md">
        <div class="flex items-center gap-3">
            <div class="bg-white/10 p-2 rounded-full cursor-pointer hover:bg-white/20 transition">
                 <x-filament::icon icon="heroicon-m-home-modern" class="w-5 h-5 text-[#26cad3]" />
            </div>
            <div>
                <h3 class="font-bold text-white text-base leading-tight">Chat de la Propiedad</h3>
                <p class="text-[11px] flex items-center gap-1 text-gray-600 dark:text-gray-300/90">
                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 dark:bg-[#26cad3] animate-pulse"></span>
                En línea
                </p>
            </div>
        </div>
    </div>

    <div class="flex-1 overflow-y-auto p-4 space-y-6 relative z-10 chat-scrollbar" id="chat-box-{{ $this->rentId }}">

        @forelse($messages as $msg)
            @php
                $isMe = $msg->user_id === auth()->id();
            @endphp

            <div class="flex w-full {{ $isMe ? 'justify-end' : 'justify-start' }} group animate-in fade-in slide-in-from-bottom-2 duration-300">
                
                <div class="flex max-w-[85%] gap-3 {{ $isMe ? 'flex-row-reverse text-right' : 'flex-row text-left' }}">
                    
                    <div class="flex-shrink-0 mt-1">
                        <img class="w-9 h-9 rounded-full border-2 {{ $isMe ? 'border-[#161848]' : 'border-gray-300' }} shadow-sm object-cover" 
                             src="{{ $msg->user->profile_photo_url ?? 'https://ui-avatars.com/api/?name='.urlencode($msg->user->name ?? 'User') }}" 
                             alt="{{ $msg->user->name }}">
                    </div>

                    <div class="flex flex-col {{ $isMe ? 'items-end' : 'items-start' }}">
                        
                        <div class="px-4 py-2 text-sm shadow-md rounded-2xl relative min-w-[100px]
                                    {{ $isMe 
                                        ? 'chat-bubble-me bg-[#161848] text-white rounded-tr-none' 
                                        : 'chat-bubble-them bg-white text-gray-800 border border-gray-200 rounded-tl-none dark:bg-gray-800 dark:text-gray-200 dark:border-gray-700' 
                                    }}">
                            
                            {{ $msg->cuerpo }}

                        </div>
                        
                        <span class="text-[11px] font-medium mt-1 opacity-70 {{ $isMe ? 'text-gray-600 dark:text-gray-400' : 'text-gray-500' }}">
                            {{ $msg->user->name }} • {{ $msg->created_at->format('h:i A') }}
                        </span>
                    </div>
                </div>
            </div>
        @empty
            <div class="h-full flex flex-col items-center justify-center">
                <div class="bg-white/80 dark:bg-gray-800/80 p-6 rounded-2xl shadow-sm text-center max-w-xs backdrop-blur-sm">
                    <div class="bg-[#161848]/10 p-3 rounded-full w-fit mx-auto mb-3">
                        <x-filament::icon icon="heroicon-o-chat-bubble-left-right" class="w-8 h-8 text-[#161848] dark:text-[#26cad3]" />
                    </div>
                    <h4 class="font-bold text-gray-800 dark:text-white mb-1">No hay mensajes</h4>
                    <p class="text-xs text-gray-500">Envía un mensaje para comenzar la gestión.</p>
                </div>
            </div>
        @endforelse
    </div>

    <div class="p-3 bg-white dark:bg-gray-900 border-t border-gray-200 dark:border-gray-700 relative z-20">
        <form wire:submit.prevent="sendMessage" class="flex items-end gap-2">
            
            <button type="button" class="p-3 text-gray-400 hover:text-gray-600 transition">
                <x-filament::icon icon="heroicon-m-paper-clip" class="w-6 h-6" />
            </button>

            <div class="flex-1 bg-gray-50 dark:bg-gray-800 rounded-2xl border border-gray-300 dark:border-gray-600 flex items-center shadow-inner focus-within:ring-2 focus-within:ring-[#161848]/20 focus-within:border-[#161848]">
                <input 
                    type="text" 
                    wire:model="newMessage"
                    class="w-full bg-transparent border-none text-sm px-4 py-3 focus:ring-0 placeholder-gray-400 text-gray-800 dark:text-white"
                    placeholder="Escribe un mensaje..."
                    required
                >
            </div>

            <button type="submit" 
                    class="p-3 bg-[#161848] hover:bg-[#161848]/90 text-white rounded-full shadow-lg transition transform active:scale-95 flex items-center justify-center"
                    wire:loading.attr="disabled">
                <x-filament::icon icon="heroicon-m-paper-airplane" class="w-5 h-5 translate-x-0.5" />
            </button>
        </form>
    </div>

    <script>
        document.addEventListener('livewire:initialized', () => {
            const scrollBottom = () => {
                const chatBox = document.getElementById('chat-box-{{ $this->rentId }}');
                if(chatBox) chatBox.scrollTop = chatBox.scrollHeight;
            }
            scrollBottom(); 
            Livewire.on('message-sent', () => setTimeout(scrollBottom, 50));
        });
    </script>
</div>