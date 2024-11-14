document.addEventListener('DOMContentLoaded', function () {
    const chatBox = document.getElementById('chatBox');
    const messageForm = document.getElementById('messageForm');
    const messageInput = document.getElementById('messageInput');

    // Yangi xabarni chatga qo'shish
    function addMessageToChat(msg, justifyClass, bgClass, textClass) {
        const messageElement = document.createElement('div');
        messageElement.classList.add('mb-4', 'flex', justifyClass);

        const bubble = document.createElement('div');
        bubble.classList.add('p-4', 'rounded-xl', 'max-w-xs', bgClass, textClass, 'shadow-lg', 'new-message');
        bubble.textContent = msg;

        const timestamp = document.createElement('div');
        timestamp.classList.add('text-xs', 'text-gray-500', 'mt-1', 'opacity-70');
        timestamp.textContent = new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });

        bubble.appendChild(timestamp);
        messageElement.appendChild(bubble);
        chatBox.appendChild(messageElement);
        scrollBottom();
    }

    // Chat pastiga scroll qilish
    function scrollBottom() {
        chatBox.scrollTop = chatBox.scrollHeight;
    }

    // Xabar yuborish formasi
    messageForm.addEventListener('submit', function (e) {
        e.preventDefault();
        const message = messageInput.value.trim();
        if (message !== '') {
            addMessageToChat(message, 'justify-end', 'bg-indigo-500', 'text-white');
            messageInput.value = ''; // Xabarni yuborganidan keyin inputni tozalash
        }
    });
});
