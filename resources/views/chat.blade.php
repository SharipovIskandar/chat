@extends('layouts.app')

@section('sidebar')
    <div class="w-1/4 p-4 bg-gray-200">
        @foreach($users as $user)
            <a href="javascript:void(0);" class="block p-2 rounded hover:bg-gray-200 user-item {{ $selectedUserId == $user->id ? 'bg-blue-300' : '' }}" data-user-id="{{ $user->id }}">
                {{ $user->name }}
            </a>
        @endforeach
    </div>
@endsection

@section('content')
    <div class="flex h-full">
        <!-- Right: Chat Box -->
        <div class="flex-1 p-4">
            <div class="flex flex-col h-full">
                <!-- Chat Messages -->
                <div id="chatBox" class="flex-1 p-4 border rounded overflow-y-auto bg-white" style="height: 400px;">
                    <!-- Xabarlar shu yerda ko'rsatiladi -->
                </div>

                <!-- Xabar Yozish Inputi -->
                <form id="messageForm" class="mt-4 flex">
                    <input type="text" id="messageInput" placeholder="Xabar yozing..." class="flex-1 p-2 border rounded-l">
                    <button type="submit" class="px-4 bg-blue-500 text-white rounded-r">Yuborish</button>
                </form>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    <script src="https://cdnjs.cloudflare.com/ajax/libs/axios/1.4.0/axios.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const userList = document.querySelectorAll('.user-item');
            const chatBox = document.getElementById('chatBox');
            const messageInput = document.getElementById('messageInput');
            const messageForm = document.getElementById('messageForm');
            let selectedUserId = {{ $selectedUserId ?? 'null' }};

            // CSRF Tokenni olish
            const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

            // Foydalanuvchini tanlash
            userList.forEach(user => {
                user.addEventListener('click', function () {
                    selectedUserId = this.getAttribute('data-user-id');
                    loadMessages(selectedUserId);

                    // Tanlangan foydalanuvchining orqa fonini o'zgartirish
                    document.querySelectorAll('.user-item').forEach(item => {
                        item.classList.remove('bg-blue-300');
                    });
                    this.classList.add('bg-blue-300');
                });
            });

            // Xabarlarni yuklash
            function loadMessages(userId) {
                axios.get(`/messages/${userId}`)
                    .then(response => {
                        const messages = response.data;
                        chatBox.innerHTML = ''; // Tozalash

                        messages.forEach(msg => {
                            const messageElement = document.createElement('div');
                            messageElement.classList.add('mb-2', 'flex', msg.sender_id === {{ Auth::id() }} ? 'justify-end' : 'justify-start');

                            const bubble = document.createElement('div');
                            bubble.classList.add('p-2', 'rounded', 'max-w-xs', msg.sender_id === {{ Auth::id() }} ? 'bg-blue-500 text-white' : 'bg-gray-300 text-gray-800');
                            bubble.textContent = msg.message;

                            messageElement.appendChild(bubble);
                            chatBox.appendChild(messageElement);
                        });

                        // Chat oynasini pastga siljitish
                        chatBox.scrollTop = chatBox.scrollHeight;
                    })
                    .catch(error => {
                        console.error('Xabarlarni yuklashda xato:', error);
                    });
            }

            // Xabar yuborish
            messageForm.addEventListener('submit', function (e) {
                e.preventDefault();
                if (messageInput.value.trim() !== '' && selectedUserId) {
                    const message = messageInput.value.trim();

                    axios.post('/messages/send', {
                        receiver_id: selectedUserId,
                        message: message
                    }, {
                        headers: {
                            'X-CSRF-TOKEN': csrfToken
                        }
                    })
                        .then(response => {
                            if (response.data.success) {
                                // Xabarni chat oynasiga qo'shish
                                const msg = response.data.message;
                                const messageElement = document.createElement('div');
                                messageElement.classList.add('mb-2', 'flex', 'justify-end');

                                const bubble = document.createElement('div');
                                bubble.classList.add('p-2', 'rounded', 'max-w-xs', 'bg-blue-500', 'text-white');
                                bubble.textContent = msg.message;

                                messageElement.appendChild(bubble);
                                chatBox.appendChild(messageElement);

                                // Chat oynasini pastga siljitish
                                chatBox.scrollTop = chatBox.scrollHeight;

                                // Inputni tozalash
                                messageInput.value = '';
                            }
                        })
                        .catch(error => {
                            console.error('Xabar yuborishda xato:', error);
                        });
                }
            });

            // Agar biron bir foydalanuvchi oldin tanlangan bo'lsa, xabarlarni yuklash
            if (selectedUserId) {
                loadMessages(selectedUserId);
            }
        });
    </script>
@endsection
