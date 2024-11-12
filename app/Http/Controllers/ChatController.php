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
        $users = User::where('id', '!=', Auth::id())->get();
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

        if ($lastMessage && $lastMessage->created_at > now()->subSeconds(5)) {
            return response()->json(['message' => $lastMessage]);
        }

        return response()->json(['message' => null]);
    }


}
