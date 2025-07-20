<?php

namespace App\Http\Controllers;

use App\Events\MessageSent;
use App\Models\ChatRoom;
use App\Models\Message;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ChatController extends Controller
{
    public function index()
    {
        $chatRooms = ChatRoom::with('creator', 'messages.user')
            ->orderBy('updated_at', 'desc')
            ->get();

        $defaultRoom = $chatRooms->first();
        if (!$defaultRoom) {
            $defaultRoom = ChatRoom::create([
                'name' => 'General',
                'description' => 'General chat room',
                'created_by' => Auth::id(),
            ]);
        }

        return view('chat.index', compact('chatRooms', 'defaultRoom'));
    }

    public function show(ChatRoom $chatRoom)
    {
        $messages = $chatRoom->messages()
            ->with('user')
            ->orderBy('created_at', 'asc')
            ->get();

        return response()->json([
            'messages' => $messages,
            'chatRoom' => $chatRoom
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'content' => 'required|string|max:1000',
            'chat_room_id' => 'required|exists:chat_rooms,id',
            'type' => 'sometimes|string|in:text,image,file'
        ]);

        $message = Message::create([
            'content' => $request->content,
            'user_id' => Auth::id(),
            'chat_room_id' => $request->chat_room_id,
            'type' => $request->type ?? 'text',
        ]);

        $message->load('user');

        // Update chat room's updated_at timestamp
        $chatRoom = ChatRoom::find($request->chat_room_id);
        $chatRoom->touch();

        // Broadcast the message
        broadcast(new MessageSent($message));

        return response()->json([
            'message' => $message,
            'success' => true
        ]);
    }

    public function createRoom(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:500',
            'is_private' => 'boolean'
        ]);

        $chatRoom = ChatRoom::create([
            'name' => $request->name,
            'description' => $request->description,
            'is_private' => $request->is_private ?? false,
            'created_by' => Auth::id(),
        ]);

        return response()->json([
            'chatRoom' => $chatRoom,
            'success' => true
        ]);
    }
}
