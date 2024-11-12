@extends('layouts.app')

@section('sidebar')
    <div class="w-full md:w-1/4 p-6 bg-gradient-to-br from-indigo-200 to-blue-400 border-r border-gray-300 h-screen shadow-xl dark:bg-gray-800 dark:border-gray-600 overflow-y-auto">
        <h2 class="text-xl font-semibold text-gray-800 dark:text-white mb-6">Foydalanuvchilar</h2>
        @foreach($users as $user)
            <a href="javascript:void(0);" class="block p-4 rounded-lg mb-4 hover:bg-indigo-200 user-item {{ $selectedUserId == $user->id ? 'bg-indigo-500 text-white' : 'bg-gray-100' }} dark:hover:bg-indigo-600 dark:bg-gray-700 dark:text-white transition-colors duration-200 ease-in-out" data-user-id="{{ $user->id }}">
                <div class="flex items-center">
                    <div class="w-14 h-14 bg-indigo-500 rounded-full flex items-center justify-center text-white text-xl">
                        {{ substr($user->name, 0, 1) }}
                    </div>
                    <span class="ml-4 font-medium text-gray-700 dark:text-white">{{ $user->name }}</span>
                </div>
            </a>
        @endforeach
    </div>
@endsection

@section('content')
    <div class="flex flex-1 h-screen">
        <div class="flex-1 p-6 flex flex-col">
            <div class="flex-1 bg-white rounded-lg shadow-lg dark:bg-gray-700 dark:text-white flex flex-col">
                <div id="chatBox" class="flex-1 p-6 border-b border-gray-300 rounded-t-lg overflow-y-auto dark:border-gray-600" style="max-height: calc(100vh - 100px);">
                </div>
                <form id="messageForm" class="flex items-center mt-4">
                    <input type="text" id="messageInput" placeholder="Xabar yozing..." class="flex-1 p-4 border rounded-l-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 dark:bg-gray-800 dark:text-white dark:border-gray-600 transition duration-200 ease-in-out shadow-lg"/>
                    <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white p-4 rounded-r-lg ml-2 transition-all duration-200 ease-in-out dark:bg-indigo-600 dark:hover:bg-indigo-700">Yuborish</button>
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
            const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

            userList.forEach(user => {
                user.addEventListener('click', function () {
                    selectedUserId = this.getAttribute('data-user-id');
                    loadMessages(selectedUserId);
                    document.querySelectorAll('.user-item').forEach(item => {
                        item.classList.remove('bg-indigo-500', 'text-white');
                        item.classList.add('bg-gray-100');
                    });
                    this.classList.add('bg-indigo-500', 'text-white');
                    chatBox.classList.remove('hidden');
                    messageForm.classList.remove('hidden');
                });
            });

            function loadMessages(userId) {
                axios.get(`/messages/${userId}`)
                    .then(response => {
                        const messages = response.data;
                        messages.sort((a, b) => new Date(a.created_at) - new Date(b.created_at));
                        chatBox.innerHTML = '';

                        messages.forEach(msg => {
                            const messageElement = document.createElement('div');
                            messageElement.classList.add('mb-4', 'flex', msg.sender_id === {{ Auth::id() }} ? 'justify-end' : 'justify-start');

                            const bubble = document.createElement('div');
                            const bubbleClass = (msg.sender_id === {{ Auth::id() }})
                                ? ['p-4', 'rounded-xl', 'max-w-xs', 'bg-indigo-500', 'text-white', 'shadow-lg']
                                : ['p-4', 'rounded-xl', 'max-w-xs', 'bg-gray-300', 'text-gray-800', 'shadow-lg'];

                            bubble.classList.add(...bubbleClass);
                            bubble.textContent = msg.message;

                            messageElement.appendChild(bubble);

                            const timestamp = document.createElement('div');
                            timestamp.classList.add('text-xs', 'text-gray-500', 'mt-1', 'opacity-70');
                            const messageTime = new Date(msg.created_at).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
                            timestamp.textContent = messageTime;

                            bubble.appendChild(timestamp);
                            chatBox.appendChild(messageElement);
                        });
                        scrollBottom();
                    })
                    .catch(error => console.error('Xabarlarni yuklashda xato:', error));

                longPollForNewMessages(userId);
            }

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
                                const msg = response.data.message;
                                const messageElement = document.createElement('div');
                                messageElement.classList.add('mb-4', 'flex', 'justify-end');

                                const bubble = document.createElement('div');
                                bubble.classList.add('p-4', 'rounded-xl', 'max-w-xs', 'bg-indigo-500', 'text-white', 'shadow-lg');
                                bubble.textContent = msg.message;

                                messageElement.appendChild(bubble);

                                const timestamp = document.createElement('div');
                                timestamp.classList.add('text-xs', 'text-gray-500', 'mt-1', 'opacity-70');
                                const messageTime = new Date(msg.created_at).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
                                timestamp.textContent = messageTime;

                                bubble.appendChild(timestamp);
                                chatBox.appendChild(messageElement);
                                messageInput.value = '';
                                scrollBottom();
                                playNotificationSoundWhenSendMessage();
                            }
                        })
                        .catch(error => console.error('Xabar yuborishda xato:', error));
                }
            });

            function longPollForNewMessages(userId) {
                axios.get(`/messages/long-polling/${userId}`)
                    .then(response => {
                        const newMessage = response.data.message;
                        if (newMessage && newMessage.sender_id !== {{ Auth::id() }}) {
                            playNotificationSoundWhenLoadMessage();
                            const messageElement = document.createElement('div');
                            messageElement.classList.add('mb-4', 'flex', newMessage.sender_id === {{ Auth::id() }} ? 'justify-end' : 'justify-start');

                            const bubble = document.createElement('div');
                            const bubbleClass = (newMessage.sender_id === {{ Auth::id() }})
                                ? ['p-4', 'rounded-xl', 'max-w-xs', 'bg-indigo-500', 'text-white', 'shadow-lg']
                                : ['p-4', 'rounded-xl', 'max-w-xs', 'bg-gray-300', 'text-gray-800', 'shadow-lg'];

                            bubble.classList.add(...bubbleClass);
                            bubble.textContent = newMessage.message;

                            messageElement.appendChild(bubble);

                            const timestamp = document.createElement('div');
                            timestamp.classList.add('text-xs', 'text-gray-500', 'mt-1', 'opacity-70');
                            const messageTime = new Date(newMessage.created_at).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
                            timestamp.textContent = messageTime;

                            bubble.appendChild(timestamp);
                            chatBox.appendChild(messageElement);
                            scrollBottom();
                        }
                        longPollForNewMessages(userId);
                    })
                    .catch(error => console.error('Xabarlar tekshirilishda xato:', error));
            }

            if (selectedUserId) loadMessages(selectedUserId);
        });

        function scrollBottom() {
            const chatBox = document.getElementById('chatBox');
            chatBox.scrollTop = chatBox.scrollHeight;
        }
        if(Notification.permission === "default"){
            Notification.requestPermission().then(permission => {
                if(permission === "granted"){
                    const notificationOptions = {
                        body: "Sizga yangi habar bor",
                        icon: "https://example.com",
                        tag:  "new-message"
                    }
                    let notification = new Notification("Yangi habar bor!!!!!!", notificationOptions);

                    notification.onclick = () => {
                        window.open("https://example.com")
                        notification.close()
                    };
                    notification.onclose = () => {
                        console.log("Bildirishnoma yopildi!")
                    }
                }else{
                    console.log("Bildirish nomalar rad etildi! Bilganingni qil Jetli")
                }
            })
        }
        function playNotificationSoundWhenLoadMessage(){
            const audio = new Audio('/build/assets/mixkit-bell-notification-933.wav');
            audio.play();
        }

        function playNotificationSoundWhenSendMessage(){
            const audio = new Audio('/build/assets/sentmessage_1.mp3');
            audio.play();
        }
    </script>
@endsection
