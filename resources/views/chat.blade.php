@extends('layouts.app')

@section('sidebar')
    <div class="w-full md:w-1/4 p-4 bg-gradient-to-br from-blue-50 to-indigo-100 border-r border-gray-300 h-screen overflow-y-auto shadow-lg dark:bg-gray-800 dark:border-gray-600">
        <h2 class="text-xl font-semibold text-gray-800 dark:text-white mb-6">Foydalanuvchilar</h2>
        @foreach($users as $user)
            <a href="javascript:void(0);" class="block p-4 rounded-lg mb-4 hover:bg-blue-200 user-item {{ $selectedUserId == $user->id ? 'bg-blue-500 text-white' : 'bg-gray-100' }} dark:hover:bg-blue-700 dark:bg-gray-600 dark:text-white transition-colors duration-200 ease-in-out" data-user-id="{{ $user->id }}">
                <div class="flex items-center">
                    <div class="w-12 h-12 bg-gray-400 rounded-full flex items-center justify-center text-white text-xl">
                        {{ substr($user->name, 0, 1) }}
                    </div>
                    <span class="ml-4 font-medium text-gray-700 dark:text-white">{{ $user->name }}</span>
                </div>
            </a>
        @endforeach
    </div>
@endsection

@section('content')
    <div class="flex flex-1">
        <!-- Right: Chat Box -->
        <div class="flex-1 p-6">
            <div class="flex flex-col h-full bg-white rounded-lg shadow-lg dark:bg-gray-700 dark:text-white">
                <!-- Chat Messages -->
                <div id="chatBox" class="flex-1 p-4 border-b border-gray-300 rounded-t-lg overflow-y-auto hidden dark:border-gray-600">
                    <!-- Xabarlar shu yerda ko'rsatiladi -->
                </div>

                <!-- Xabar Yozish Inputi -->
                <form id="messageForm" class="mt-6 flex items-center hidden">
                    <input type="text" id="messageInput" placeholder="Xabar yozing..." class="flex-1 p-4 border rounded-l-lg focus:outline-none focus:ring-2 focus:ring-blue-500 dark:bg-gray-800 dark:text-white dark:border-gray-600 transition duration-200 ease-in-out shadow-md" />
                    <button type="submit" class="bg-blue-500 hover:bg-blue-600 text-white p-4 rounded-r-lg ml-2 transition-all duration-200 ease-in-out dark:bg-blue-600 dark:hover:bg-blue-700">Yuborish</button>
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
                        item.classList.remove('bg-blue-500', 'text-white');
                        item.classList.add('bg-gray-100');
                    });
                    this.classList.add('bg-blue-500', 'text-white');

                    // Chatni ko'rsatish va Inputni ochish
                    chatBox.classList.remove('hidden');
                    messageForm.classList.remove('hidden');
                });
            });

            // Xabarlarni yuklash
            function loadMessages(userId) {
                axios.get(`/messages/${userId}`)
                    .then(response => {
                        const messages = response.data;

                        // Xabarlarni vaqtga ko'ra tartiblash
                        messages.sort((a, b) => {
                            const dateA = new Date(a.created_at);
                            const dateB = new Date(b.created_at);
                            return dateA - dateB;
                        });

                        chatBox.innerHTML = ''; // Tozalash

                        messages.forEach(msg => {
                            const messageElement = document.createElement('div');
                            messageElement.classList.add('mb-4', 'flex', msg.sender_id === {{ Auth::id() }} ? 'justify-end' : 'justify-start');

                            const bubble = document.createElement('div');
                            const bubbleClass = (msg.sender_id === {{ Auth::id() }})
                                ? ['p-4', 'rounded-xl', 'max-w-xs', 'bg-blue-500', 'text-white', 'shadow-lg', 'transition', 'duration-200', 'ease-in-out']
                                : ['p-4', 'rounded-xl', 'max-w-xs', 'bg-gray-300', 'text-gray-800', 'shadow-lg'];

                            bubble.classList.add(...bubbleClass);
                            bubble.textContent = msg.message;

                            messageElement.appendChild(bubble);

                            // Vaqtni ko'rsatish xabar oxirida
                            const timestamp = document.createElement('div');
                            timestamp.classList.add('text-xs', 'text-gray-500', 'mt-1', 'opacity-70');
                            const messageTime = new Date(msg.created_at).toLocaleTimeString([], {
                                hour: '2-digit', minute: '2-digit'
                            });
                            timestamp.textContent = messageTime;

                            bubble.appendChild(timestamp); // Vaqtni xabar bubble oxiriga qo'shish
                            chatBox.appendChild(messageElement);
                        });

                        // Chat oynasini pastga siljitish
                        chatBox.scrollTop = chatBox.scrollHeight;
                    })
                    .catch(error => {
                        console.error('Xabarlarni yuklashda xato:', error);
                    });
            }

            // Xabarlarni yuborish
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
                                messageElement.classList.add('mb-4', 'flex', 'justify-end');

                                const bubble = document.createElement('div');
                                bubble.classList.add('p-4', 'rounded-xl', 'max-w-xs', 'bg-blue-500', 'text-white', 'shadow-lg');
                                bubble.textContent = msg.message;

                                messageElement.appendChild(bubble);

                                // Vaqtni xabar oxirida ko'rsatish
                                const timestamp = document.createElement('div');
                                timestamp.classList.add('text-xs', 'text-gray-500', 'mt-1', 'opacity-70');
                                const messageTime = new Date(msg.created_at).toLocaleTimeString([], {
                                    hour: '2-digit', minute: '2-digit'
                                });
                                timestamp.textContent = messageTime;

                                bubble.appendChild(timestamp); // Vaqtni xabar bubble oxiriga qo'shish
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
