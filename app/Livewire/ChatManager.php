<?php

namespace App\Livewire;

use App\Models\Message;
use Livewire\Component;
use Illuminate\Support\Facades\Auth;

class ChatManager extends Component
{
    public $rentId;
    public $newMessage = '';

    public function mount($rentId)
    {
        $this->rentId = $rentId;
        $this->markAsRead(); 
    }

    // Método para marcar mensajes como vistos
    public function markAsRead()
    {
        Message::where('rent_id', $this->rentId)
            ->where('user_id', '!=', Auth::id()) // Mensajes que no son míos
            ->where('visto', false)
            ->update(['visto' => true]);
    }

    // Ejecutar también cuando se renderiza (por si llegan mensajes mientras estás en la pantalla)
    public function rendering($view, $data)
    {
        $this->markAsRead();
    }

    public function sendMessage()
    {
        $this->validate([
            'newMessage' => 'required|string|max:1000',
        ]);

        Message::create([
            'rent_id' => $this->rentId,
            'user_id' => Auth::id(),
            'cuerpo' => $this->newMessage, 
            'visto' => false,             
        ]);

        $this->newMessage = '';
        
        $this->dispatch('message-sent'); 
    }

    public function render()
    {
        return view('livewire.chat-manager', [
            'messages' => Message::with('user')
                ->where('rent_id', $this->rentId)
                ->orderBy('created_at', 'asc')
                ->get()
        ]);
    }
}