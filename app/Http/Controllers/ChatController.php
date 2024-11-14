<?php

namespace App\Http\Controllers;

use App\Models\Message;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ChatController extends Controller
{
    public function index()
    {
        $users = User::where('id', '!=', Auth::id())->with("lastMessage")->get();
        $selectedUserId = null;

        return view('chat', compact('users', 'selectedUserId'));
    }

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

    public function longPolling($userId)
    {
        $lastMessage = Message::where(function ($query) use ($userId) {
            $query->where('sender_id', Auth::id())->where('receiver_id', $userId);
        })->orWhere(function ($query) use ($userId) {
            $query->where('sender_id', $userId)->where('receiver_id', Auth::id());
        })
            ->orderBy('created_at', 'desc')
            ->first();

        if ($lastMessage) {
            // Xabar faqat foydalanuvchi ko'rmagan bo'lsa qaytaring
            $lastMessageTime = $lastMessage->created_at;
            $lastSeenTime = session('last_seen_time', now()->subSeconds(10)); // Agar xabarlar tekshirilmagan bo'lsa, 10 soniya oldin deb olinadi
            if ($lastMessageTime > $lastSeenTime) {
                session(['last_seen_time' => now()]);
                return response()->json(['message' => $lastMessage]);
            }
        }

        return response()->json(['message' => null]);
    }

    public function markAsRead($messageId)
    {
        $message = Message::where('id', $messageId)
            ->where('receiver_id', auth()->id())
            ->first();

        if ($message) {
            $message->is_read = true;
            $message->save();

            return response()->json(['success' => true]);
        }

        return response()->json(['success' => false], 404);
    }
}
