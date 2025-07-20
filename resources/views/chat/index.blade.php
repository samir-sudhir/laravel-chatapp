@extends('layouts.app')

@section('title', 'Chat - Laravel Chat App')

@section('content')
<div class="flex h-screen bg-gray-100 overflow-hidden">
    <!-- Mobile Menu Button -->
    <button id="mobile-menu-button" class="fixed top-4 left-4 z-50 lg:hidden bg-indigo-600 text-white p-2 rounded-lg shadow-lg">
        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
        </svg>
    </button>

    <!-- Sidebar -->
    <div id="sidebar" class="fixed inset-y-0 left-0 z-40 w-80 bg-white shadow-lg border-r transform -translate-x-full transition-transform duration-300 ease-in-out lg:translate-x-0 lg:static lg:inset-0">
        <!-- Header -->
        <div class="p-4 border-b bg-indigo-600 text-white">
            <div class="flex items-center justify-between">
                <h1 class="text-lg lg:text-xl font-bold">Chat Rooms</h1>
                <div class="flex items-center space-x-2">
                    <span class="text-sm hidden sm:inline">{{ auth()->user()->name }}</span>
                    <form action="{{ route('logout') }}" method="POST" class="inline">
                        @csrf
                        <button type="submit" class="text-indigo-200 hover:text-white">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path>
                            </svg>
                        </button>
                    </form>
                    <!-- Close button for mobile -->
                    <button id="close-sidebar" class="lg:hidden text-indigo-200 hover:text-white">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
            </div>
        </div>
        <!-- New Room Button -->
        <div class="p-4 border-b">
            <button onclick="showCreateRoomModal()" class="w-full bg-indigo-600 text-white py-2 px-4 rounded-lg hover:bg-indigo-700 transition duration-200">
                <svg class="w-5 h-5 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                </svg>
                New Room
            </button>
        </div>
        <!-- Chat Rooms List -->
        <div class="overflow-y-auto h-full" id="chat-rooms-list">
            @foreach($chatRooms as $room)
                <div class="p-4 border-b hover:bg-gray-50 cursor-pointer chat-room-item {{ $room->id == $defaultRoom->id ? 'bg-indigo-50 border-l-4 border-indigo-500' : '' }}" 
                     data-room-id="{{ $room->id }}" onclick="selectRoom({{ $room->id }})">
                    <div class="flex items-center justify-between">
                        <div class="flex-1">
                            <h3 class="font-semibold text-gray-900">{{ $room->name }}</h3>
                            <p class="text-sm text-gray-500">{{ $room->description }}</p>
                        </div>
                        @if($room->is_private)
                            <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                            </svg>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    </div>
    <!-- Overlay for mobile -->
    <div id="sidebar-overlay" class="fixed inset-0 bg-black bg-opacity-50 z-30 hidden lg:hidden"></div>
    <!-- Chat Area -->
    <div class="flex-1 flex flex-col min-h-0 bg-gray-50">
        <!-- Chat Header -->
        <div class="flex-shrink-0 p-3 lg:p-4 border-b bg-white shadow-sm sticky top-0 z-10">
            <div class="flex items-center justify-between">
                <div class="flex-1 min-w-0">
                    <h2 class="text-lg lg:text-xl font-bold text-gray-900 truncate mx-4" id="current-room-name" style="margin-left: 100px;">{{ $defaultRoom->name }}</h2>
                    <p class="text-sm text-gray-500 truncate" id="current-room-description" style="margin-left: 100px;">{{ $defaultRoom->description }}</p>
                </div>
                <div class="flex items-center space-x-2">
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                        <span class="w-2 h-2 bg-green-400 rounded-full mr-1"></span>
                        <span class="hidden sm:inline">Online</span>
                    </span>
                </div>
            </div>
        </div>
        <!-- Messages Area -->
        <div class="flex-1 overflow-y-auto p-2 sm:p-4 space-y-2 sm:space-y-4" id="messages-container" style="scroll-behavior: smooth;">
            <!-- Messages will be loaded here -->
        </div>
        <!-- Message Input -->
        <div class="flex-shrink-0 p-2 sm:p-3 lg:p-4 border-t bg-white sticky bottom-0 z-10">
            <form id="message-form" class="flex items-center space-x-2 lg:space-x-4">
                @csrf
                <input type="hidden" id="chat-room-id" value="{{ $defaultRoom->id }}">
                <div class="flex-1 relative">
                    <input type="text" id="message-input" 
                           class="w-full p-2 lg:p-3 pr-10 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 text-sm lg:text-base" 
                           placeholder="Type your message..." maxlength="1000" autocomplete="off">
                    <div class="absolute right-2 lg:right-3 top-2 lg:top-3 text-gray-400">
                        <svg class="w-4 h-4 lg:w-5 lg:h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"></path>
                        </svg>
                    </div>
                </div>
                <button type="submit" 
                        class="bg-indigo-600 text-white px-4 lg:px-6 py-2 lg:py-3 rounded-lg hover:bg-indigo-700 transition duration-200 disabled:opacity-50 text-sm lg:text-base" 
                        id="send-button">
                    <span class="hidden sm:inline">Send</span>
                    <svg class="w-4 h-4 sm:hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"></path>
                    </svg>
                </button>
            </form>
        </div>
    </div>
</div>
<!-- Create Room Modal -->
<div id="create-room-modal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden items-center justify-center z-50">
    <div class="bg-white rounded-lg p-6 w-full max-w-md">
        <h3 class="text-lg font-medium text-gray-900 mb-4">Create New Room</h3>
        <form id="create-room-form">
            @csrf
            <div class="mb-4">
                <label for="room-name" class="block text-sm font-medium text-gray-700">Room Name</label>
                <input type="text" id="room-name" name="name" required 
                       class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
            </div>
            <div class="mb-4">
                <label for="room-description" class="block text-sm font-medium text-gray-700">Description</label>
                <textarea id="room-description" name="description" rows="3" 
                          class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500"></textarea>
            </div>
            <div class="mb-4">
                <label class="flex items-center">
                    <input type="checkbox" id="room-private" name="is_private" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                    <span class="ml-2 text-sm text-gray-600">Private room</span>
                </label>
            </div>
            <div class="flex justify-end space-x-3">
                <button type="button" onclick="hideCreateRoomModal()" 
                        class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 border border-gray-300 rounded-md hover:bg-gray-50">
                    Cancel
                </button>
                <button type="submit" 
                        class="px-4 py-2 text-sm font-medium text-white bg-indigo-600 border border-transparent rounded-md hover:bg-indigo-700">
                    Create Room
                </button>
            </div>
        </form>
    </div>
</div>

<script>
window.currentRoomId = {{ $defaultRoom->id }};
window.currentUser = {!! json_encode(auth()->user()) !!};

// Sidebar toggle for mobile
const sidebar = document.getElementById('sidebar');
const sidebarOverlay = document.getElementById('sidebar-overlay');
document.getElementById('mobile-menu-button').onclick = function() {
    sidebar.classList.remove('-translate-x-full');
    sidebarOverlay.classList.remove('hidden');
};
document.getElementById('close-sidebar').onclick = function() {
    sidebar.classList.add('-translate-x-full');
    sidebarOverlay.classList.add('hidden');
};
sidebarOverlay.onclick = function() {
    sidebar.classList.add('-translate-x-full');
    sidebarOverlay.classList.add('hidden');
};

// Smooth scroll to bottom of messages
function scrollMessagesToBottom() {
    const container = document.getElementById('messages-container');
    if (container) {
        container.scrollTo({ top: container.scrollHeight, behavior: 'smooth' });
    }
}
// Call this after loading or appending messages
// Example: scrollMessagesToBottom();
</script>
@endsection
