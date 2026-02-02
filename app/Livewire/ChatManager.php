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