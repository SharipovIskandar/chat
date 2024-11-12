<?php

namespace App\Http\Controllers;

use App\Models\Message;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ChatController extends Controller
{
    // Chat sahifasini ko'rsatish uchun index metodi
    public function index()
    {
        // Hozirgi foydalanuvchi ma'lumotlarini olish
        $users = User::where('id', '!=', Auth::id())->get(); // Faqat boshqa foydalanuvchilar
        $selectedUserId = null; // Boshlang'ich foydalanuvchi tanlanmagan

        return view('chat', compact('users', 'selectedUserId'));
    }

    // Foydalanuvchilar o'rtasida xabarlarni yuklash
    public function loadMessages($userId)
    {
        $messages = Message::where(function($query) use ($userId) {
            $query->where('sender_id', Auth::id())->where('receiver_id', $userId);
        })->orWhere(function($query) use ($userId) {
            $query->where('sender_id', $userId)->where('receiver_id', Auth::id());
        })
            ->orderBy('created_at', 'asc')
            ->get();

        return response()->json($messages);
    }

    // Yangi xabarni yuborish
    public function sendMessage(Request $request)
    {
        $validated = $request->validate([
            'receiver_id' => 'required|exists:users,id',
            'message' => 'required|string|max:1000',
        ]);

        $message = new Message();
        $message->sender_id = Auth::id();
        $message->receiver_id = $validated['receiver_id'];
        $message->message = $validated['message'];
        $message->save();

        return response()->json(['success' => true, 'message' => $message]);
    }

    // Long polling orqali xabarlarni olish
    public function longPolling($userId)
    {
        $lastMessage = Message::where(function ($query) use ($userId) {
            $query->where('sender_id', Auth::id())->where('receiver_id', $userId);
        })->orWhere(function ($query) use ($userId) {
            $query->where('sender_id', $userId)->where('receiver_id', Auth::id());
        })
            ->orderBy('created_at', 'desc')
            ->first();

        if ($lastMessage && !session()->has('last_message_time') || strtotime($lastMessage->created_at) > session('last_message_time')) {
            session(['last_message_time' => strtotime($lastMessage->created_at)]); // Xabar yuborilgandan keyin vaqtni saqlash
            return response()->json(['message' => $lastMessage]);
        }

        return response()->json(['message' => null]);
    }

}
