import './bootstrap';

// Chat functionality
class ChatApp {
    constructor() {
        this.currentRoomId = window.currentRoomId;
        this.currentUser = window.currentUser;
        this.messagesContainer = document.getElementById('messages-container');
        this.messageForm = document.getElementById('message-form');
        this.messageInput = document.getElementById('message-input');
        this.sendButton = document.getElementById('send-button');
        this.chatRoomId = document.getElementById('chat-room-id');
        
        this.init();
    }

    init() {
        this.loadMessages(this.currentRoomId);
        this.setupEventListeners();
        this.setupBroadcasting();
    }

    setupEventListeners() {
        // Message form submission
        this.messageForm.addEventListener('submit', (e) => {
            e.preventDefault();
            this.sendMessage();
        });

        // Enter key to send message
        this.messageInput.addEventListener('keypress', (e) => {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                this.sendMessage();
            }
        });

        // Create room form
        const createRoomForm = document.getElementById('create-room-form');
        if (createRoomForm) {
            createRoomForm.addEventListener('submit', (e) => {
                e.preventDefault();
                this.createRoom();
            });
        }
    }

    setupBroadcasting() {
        // Leave previous channel if exists
        if (this.currentChannel) {
            window.Echo.leave(`chat-room.${this.previousRoomId}`);
        }
        
        // Listen for new messages in the current room
        this.currentChannel = window.Echo.private(`chat-room.${this.currentRoomId}`)
            .listen('MessageSent', (e) => {
                // Only add message if it's not from current user (to avoid duplicates)
                if (e.message.user.id !== this.currentUser.id) {
                    this.appendMessage(e.message);
                }
            });
        
        this.previousRoomId = this.currentRoomId;
    }
    

    async loadMessages(roomId) {
        try {
            const response = await fetch(`/chat/room/${roomId}`);
            const data = await response.json();
            
            this.messagesContainer.innerHTML = '';
            
            data.messages.forEach(message => {
                this.appendMessage(message);
            });
            
            this.scrollToBottom();
        } catch (error) {
            console.error('Error loading messages:', error);
        }
    }

    async sendMessage() {
        const content = this.messageInput.value.trim();
        if (!content) return;

        this.sendButton.disabled = true;
        this.sendButton.textContent = 'Sending...';

        try {
            const response = await fetch('/chat/message', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify({
                    content: content,
                    chat_room_id: this.currentRoomId
                })
            });

            if (response.ok) {
                const data = await response.json();
                // Display the message immediately for the sender
                this.appendMessage(data.message);
                this.messageInput.value = '';
            } else {
                console.error('Error sending message');
            }
        } catch (error) {
            console.error('Error sending message:', error);
        } finally {
            this.sendButton.disabled = false;
            this.sendButton.textContent = 'Send';
        }
    }

    appendMessage(message) {
        const messageElement = document.createElement('div');
        const isCurrentUser = message.user.id === this.currentUser.id;
        const messageTime = new Date(message.created_at).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
        
        messageElement.className = `flex ${isCurrentUser ? 'justify-end' : 'justify-start'}`;
        messageElement.innerHTML = `
            <div class="max-w-xs lg:max-w-md px-4 py-2 rounded-lg ${isCurrentUser ? 'bg-indigo-500 text-white' : 'bg-white text-gray-900'} shadow">
                ${!isCurrentUser ? `<div class="text-xs font-semibold text-gray-500 mb-1">${message.user.name}</div>` : ''}
                <div class="text-sm">${this.escapeHtml(message.content)}</div>
                <div class="text-xs ${isCurrentUser ? 'text-indigo-200' : 'text-gray-500'} mt-1">${messageTime}</div>
            </div>
        `;
        
        this.messagesContainer.appendChild(messageElement);
        this.scrollToBottom();
    }

    scrollToBottom() {
        this.messagesContainer.scrollTop = this.messagesContainer.scrollHeight;
    }

    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    async createRoom() {
        const formData = new FormData(document.getElementById('create-room-form'));
        const roomData = {
            name: formData.get('name'),
            description: formData.get('description'),
            is_private: formData.get('is_private') === 'on'
        };

        try {
            const response = await fetch('/chat/room', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify(roomData)
            });

            if (response.ok) {
                const data = await response.json();
                this.hideCreateRoomModal();
                location.reload(); // Refresh to show new room
            } else {
                console.error('Error creating room');
            }
        } catch (error) {
            console.error('Error creating room:', error);
        }
    }

    hideCreateRoomModal() {
        document.getElementById('create-room-modal').classList.add('hidden');
        document.getElementById('create-room-form').reset();
    }
}

// Mobile menu functionality
function setupMobileMenu() {
    const mobileMenuButton = document.getElementById('mobile-menu-button');
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('sidebar-overlay');
    const closeSidebar = document.getElementById('close-sidebar');
    
    function showSidebar() {
        sidebar.classList.remove('-translate-x-full');
        overlay.classList.remove('hidden');
        document.body.classList.add('overflow-hidden');
    }
    
    function hideSidebar() {
        sidebar.classList.add('-translate-x-full');
        overlay.classList.add('hidden');
        document.body.classList.remove('overflow-hidden');
    }
    
    mobileMenuButton.addEventListener('click', showSidebar);
    closeSidebar.addEventListener('click', hideSidebar);
    overlay.addEventListener('click', hideSidebar);
    
    // Close sidebar when room is selected on mobile
    document.addEventListener('click', function(e) {
        if (e.target.closest('.chat-room-item')) {
            setTimeout(hideSidebar, 100);
        }
    });
}

// Global functions
window.selectRoom = async function(roomId) {
    // Update current room
    window.currentRoomId = roomId;
    window.chatApp.currentRoomId = roomId;
    
    // Update UI
    document.querySelectorAll('.chat-room-item').forEach(item => {
        item.classList.remove('bg-indigo-50', 'border-l-4', 'border-indigo-500');
    });
    
    const selectedRoom = document.querySelector(`[data-room-id="${roomId}"]`);
    selectedRoom.classList.add('bg-indigo-50', 'border-l-4', 'border-indigo-500');
    
    // Load messages for selected room
    await window.chatApp.loadMessages(roomId);
    
    // Update room info
    const roomName = selectedRoom.querySelector('h3').textContent;
    const roomDescription = selectedRoom.querySelector('p').textContent;
    document.getElementById('current-room-name').textContent = roomName;
    document.getElementById('current-room-description').textContent = roomDescription;
    document.getElementById('chat-room-id').value = roomId;
    
    // Setup new broadcasting for the room
    window.chatApp.setupBroadcasting();
};

window.showCreateRoomModal = function() {
    document.getElementById('create-room-modal').classList.remove('hidden');
    document.getElementById('create-room-modal').classList.add('flex');
};

window.hideCreateRoomModal = function() {
    window.chatApp.hideCreateRoomModal();
};

// Initialize chat app when DOM is loaded
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        if (window.currentRoomId) {
            window.chatApp = new ChatApp();
        }
        setupMobileMenu();
    });
} else {
    if (window.currentRoomId) {
        window.chatApp = new ChatApp();
    }
    setupMobileMenu();
}
