<?php

namespace App\Http\Controllers;

use App\Models\Message;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MessageController extends Controller
{
    public function index()
    {
        $users = User::all(); // Barcha foydalanuvchilarni olish
        return view('chat.index', compact('users'));
    }

    public function loadMessages($userId)
    {
        // Faqat ma'lum foydalanuvchi bilan xabarlar
        $messages = Message::where(function($query) use ($userId) {
            $query->where('sender_id', Auth::id())
                ->where('receiver_id', $userId);
        })
            ->orWhere(function($query) use ($userId) {
                $query->where('sender_id', $userId)
                    ->where('receiver_id', Auth::id());
            })
            ->get();

        return response()->json($messages);
    }

    public function sendMessage(Request $request)
    {
        $validated = $request->validate([
            'receiver_id' => 'required|integer|exists:users,id',
            'message' => 'required|string'
        ]);

        $message = new Message();
        $message->sender_id = Auth::id();
        $message->receiver_id = $validated['receiver_id'];
        $message->message = $validated['message'];
        $message->save();

        return response()->json(['success' => true, 'message' => $message]);
    }
}

