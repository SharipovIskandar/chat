<?php

namespace App\Http\Controllers;

use App\Models\Message;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ChatController extends Controller
{
    public function loadMessages($userId)
    {
        $messages = Message::where(function($query) use ($userId) {
            $query->where('sender_id', Auth::id())->where('receiver_id', $userId);
        })->orWhere(function($query) use ($userId) {
            $query->where('sender_id', $userId)->where('receiver_id', Auth::id());
        })->get();

        foreach ($messages as $message) {
            $message->sender_name = $message->sender->name;
        }

        return response()->json($messages);
    }


    public function sendMessage(Request $request)
    {
        $message = new Message();
        $message->sender_id = Auth::id();
        $message->receiver_id = $request->receiver_id;
        $message->message = $request->message;
        $message->save();

        return response()->json(['success' => true, 'message' => $message]);
    }

    public function index(Request $request)
    {
        $users = User::where('id', '!=', Auth::id())->get();
        $selectedUserId = $request->input('selectedUserId');
        return view('chat', compact('users', 'selectedUserId'));
    }


}
