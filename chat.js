

document.addEventListener('DOMContentLoaded', function() {
    const facultyData = {
        "Dr. Quantum": {
            subject: "Physics & Chemistry",
            icon: "fas fa-atom",
            prompt: "You are Dr. Quantum, an expert in Physics and Chemistry. Explain concepts clearly with real-world examples and analogies. Keep responses under 200 words."
        },
        "Math Mentor": {
            subject: "Mathematics",
            icon: "fas fa-calculator",
            prompt: "You are Math Mentor, an expert in mathematics. Provide clear step-by-step explanations from basic to advanced level. Show calculations when needed."
        },
        "Bio Sage": {
            subject: "Biology",
            icon: "fas fa-dna",
            prompt: "You are Bio Sage, an expert in Biology. Help students understand biological processes with clarity and analogies. Use simple language."
        },
        "Lit Guide": {
            subject: "English Literature",
            icon: "fas fa-book",
            prompt: "You are Lit Guide, an English literature expert. Analyze texts, explain themes, and provide literary insights. Quote relevant passages when helpful."
        },
        "Geo Explorer": {
            subject: "Geography",
            icon: "fas fa-globe-americas",
            prompt: "You are Geo Explorer, a geography expert. Explain topics clearly using real-world examples and maps. Include relevant statistics when possible."
        },
        "Code Master": {
            subject: "Computer Science",
            icon: "fas fa-laptop-code",
            prompt: "You are Code Master, a programming expert. Teach algorithms, provide code examples, and explain CS concepts clearly. Use code blocks for examples."
        }
    };

    let currentFaculty = "Dr. Quantum";
    let conversationHistory = [
        { role: "system", content: facultyData[currentFaculty].prompt }
    ];

    const chatInput = document.querySelector('.chat-input-field');
    const sendButton = document.querySelector('.send-button');
    const chatMessages = document.querySelector('.chat-messages');
    const chatTitle = document.querySelector('.chat-title');

    // Check if user is logged in
    checkLoginStatus();

    function checkLoginStatus() {
        fetch('check_login.php')
            .then(response => response.json())
            .then(data => {
                if (!data.loggedIn) {
                    window.location.href = 'login.htm';
                } else {
                    // Load previous messages if any
                    loadPreviousMessages();
                }
            });
    }

    function loadPreviousMessages() {
        fetch('chat_api.php?action=get_messages&faculty=' + encodeURIComponent(currentFaculty), {
            method: 'GET',
            headers: {
                'Content-Type': 'application/json',
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.messages) {
                chatMessages.innerHTML = '';
                data.messages.forEach(msg => {
                    addMessage(msg.role === 'user' ? 'user' : 'ai', msg.content);
                });
            } else {
                // Initial welcome message
                addMessage('ai', `Hello! I'm ${currentFaculty}, your AI assistant for ${facultyData[currentFaculty].subject}. How can I help you today?`);
            }
        })
        .catch(err => {
            console.error("Error loading messages:", err);
            // Initial welcome message if loading fails
            addMessage('ai', `Hello! I'm ${currentFaculty}, your AI assistant for ${facultyData[currentFaculty].subject}. How can I help you today?`);
        });
    }

    function addMessage(role, text) {
        const messageDiv = document.createElement('div');
        messageDiv.className = `message ${role}`;
        const icon = role === 'user' ? 'fas fa-user' : facultyData[currentFaculty].icon;

        // Sanitize text to prevent XSS
        const sanitizedText = text.replace(/</g, "&lt;").replace(/>/g, "&gt;");
        
        messageDiv.innerHTML = `
            <div class="message-avatar"><i class="${icon}"></i></div>
            <div class="message-content">
                <div class="message-text">${sanitizedText}</div>
                <div class="message-time">${new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })}</div>
            </div>
        `;

        chatMessages.appendChild(messageDiv);
        chatMessages.scrollTop = chatMessages.scrollHeight;

        conversationHistory.push({
            role: role === 'user' ? 'user' : 'assistant',
            content: text
        });
    }

    function showLoading() {
        const loadingDiv = document.createElement('div');
        loadingDiv.className = 'message ai';
        loadingDiv.id = 'loading-message';
        loadingDiv.innerHTML = `
            <div class="message-avatar"><i class="${facultyData[currentFaculty].icon}"></i></div>
            <div class="message-content">
                <div class="message-text"><div class="loading"></div> Thinking...</div>
            </div>
        `;
        chatMessages.appendChild(loadingDiv);
        chatMessages.scrollTop = chatMessages.scrollHeight;
    }

    function hideLoading() {
        const loading = document.getElementById('loading-message');
        if (loading) loading.remove();
    }

    async function sendMessage() {
    const userInput = chatInput.value.trim();
    if (!userInput) return;

    addMessage('user', userInput);
    chatInput.value = '';
    showLoading();

    try {
        const response = await fetch('chat_api.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                faculty: currentFaculty,
                user_message: userInput,
                action: 'send_message'
            })
        });

        const data = await response.json();
        hideLoading();

        if (data.error) {
            addMessage('ai', `⚠️ Error: ${data.error}`);
        } else {
            addMessage('ai', data.response);
        }
    } catch (err) {
        hideLoading();
        console.error("API Error:", err);
        addMessage('ai', "⚠️ Sorry, I'm having trouble connecting. Please try again.");
    }
}

    // Event listeners
    sendButton.addEventListener('click', sendMessage);
    chatInput.addEventListener('keydown', (e) => {
        if (e.key === 'Enter') sendMessage();
    });

    document.querySelectorAll('.faculty-item').forEach(item => {
    item.addEventListener('click', async () => {
        document.querySelectorAll('.faculty-item').forEach(i => i.classList.remove('active'));
        item.classList.add('active');

        const newFaculty = item.querySelector('.faculty-name').textContent;
        currentFaculty = newFaculty;
        const subject = facultyData[currentFaculty].subject;

        // Update UI
        document.querySelector('.chat-title').textContent = `${currentFaculty} - ${subject}`;
        chatInput.placeholder = `Message ${currentFaculty}...`;

        // Clear current messages and load new ones
        chatMessages.innerHTML = '';
        await loadPreviousMessages();
    });
});
});