(() => {
    const app = document.getElementById('aiTutorApp');
    if (!app) return;

    const chatUrl = app.dataset.chatUrl;
    const clearUrl = app.dataset.clearUrl;
    const userAvatar = app.dataset.userAvatar;
    const csrf = app.dataset.csrf;

    const courseSelect = document.getElementById('aiCourseSelect');
    const lessonSelect = document.getElementById('aiLessonSelect');
    const topicInput = document.getElementById('aiTopicInput');
    const messagesEl = document.getElementById('aiChatMessages');
    const input = document.getElementById('aiChatInput');
    const sendBtn = document.getElementById('aiSendBtn');
    const clearBtn = document.getElementById('aiClearBtn');
    const evaluateBtn = document.getElementById('aiEvaluateBtn');
    const suggested = document.getElementById('aiSuggested');

    let mode = 'chat';
    let isLoading = false;

    courseSelect?.addEventListener('change', () => {
        const option = courseSelect.selectedOptions[0];
        lessonSelect.innerHTML = '<option value="">— Choisir une leçon —</option>';
        lessonSelect.disabled = true;

        if (!option?.dataset.lessons) return;

        try {
            const lessons = JSON.parse(option.dataset.lessons);
            lessons.forEach((lesson) => {
                const opt = document.createElement('option');
                opt.value = lesson.id;
                opt.textContent = lesson.title;
                lessonSelect.appendChild(opt);
            });
            lessonSelect.disabled = lessons.length === 0;
        } catch (e) {
            /* ignore */
        }
    });

    suggested?.addEventListener('click', (e) => {
        const btn = e.target.closest('[data-suggest]');
        if (!btn || !input) return;
        input.value = btn.dataset.suggest;
        input.focus();
    });

    function appendBubble(role, content) {
        const bubble = document.createElement('div');
        bubble.className = `sw-chat-bubble sw-chat-bubble--${role === 'user' ? 'user' : 'assistant'}`;

        if (role === 'user') {
            bubble.innerHTML = `
                <img src="${userAvatar}" alt="" class="sw-chat-avatar">
                <div><div class="sw-chat-content"></div></div>
            `;
        } else {
            bubble.innerHTML = `
                <div class="sw-chat-avatar sw-chat-avatar--ai"><i class="fas fa-robot"></i></div>
                <div><div class="sw-chat-content"></div></div>
            `;
        }

        bubble.querySelector('.sw-chat-content').textContent = content;
        messagesEl.appendChild(bubble);
        messagesEl.scrollTop = messagesEl.scrollHeight;
    }

    function showTyping() {
        const el = document.createElement('div');
        el.className = 'sw-chat-bubble sw-chat-bubble--assistant';
        el.id = 'aiTyping';
        el.innerHTML = `
            <div class="sw-chat-avatar sw-chat-avatar--ai"><i class="fas fa-robot"></i></div>
            <div class="sw-chat-content sw-chat-typing"><span></span><span></span><span></span></div>
        `;
        messagesEl.appendChild(el);
        messagesEl.scrollTop = messagesEl.scrollHeight;
    }

    function hideTyping() {
        document.getElementById('aiTyping')?.remove();
    }

    async function sendMessage() {
        const text = input?.value?.trim();
        if (!text || isLoading) return;

        mode = 'chat';
        appendBubble('user', text);
        input.value = '';
        isLoading = true;
        sendBtn.disabled = true;
        showTyping();

        try {
            const res = await fetch(chatUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrf,
                    Accept: 'application/json',
                },
                body: JSON.stringify({
                    message: text,
                    course_id: courseSelect?.value || null,
                    lesson_id: lessonSelect?.value || null,
                    topic: topicInput?.value || null,
                    mode,
                }),
            });

            const data = await res.json();
            hideTyping();

            if (!res.ok) {
                appendBubble('assistant', data.error || 'Une erreur est survenue.');
                return;
            }

            appendBubble('assistant', data.reply);
        } catch (err) {
            hideTyping();
            appendBubble('assistant', 'Impossible de contacter le tuteur IA. Vérifiez votre connexion.');
        } finally {
            isLoading = false;
            sendBtn.disabled = false;
        }
    }

    async function evaluateLevel() {
        if (isLoading) return;

        const prompt = 'Évalue mon niveau sur ce cours. Pose-moi 3 questions progressives pour estimer mon niveau (débutant, intermédiaire, avancé), puis donne des recommandations.';
        appendBubble('user', 'Évaluer mon niveau');
        isLoading = true;
        showTyping();

        try {
            const res = await fetch(chatUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrf,
                    Accept: 'application/json',
                },
                body: JSON.stringify({
                    message: prompt,
                    course_id: courseSelect?.value || null,
                    lesson_id: lessonSelect?.value || null,
                    topic: topicInput?.value || null,
                    mode: 'evaluation',
                }),
            });

            const data = await res.json();
            hideTyping();

            if (!res.ok) {
                appendBubble('assistant', data.error || 'Erreur lors de l\'évaluation.');
                return;
            }

            appendBubble('assistant', data.reply);
        } catch (err) {
            hideTyping();
            appendBubble('assistant', 'Évaluation indisponible pour le moment.');
        } finally {
            isLoading = false;
        }
    }

    async function clearChat() {
        if (!confirm('Effacer l\'historique de cette conversation ?')) return;

        await fetch(`${clearUrl}?course_id=${courseSelect?.value || ''}`, {
            method: 'DELETE',
            headers: { 'X-CSRF-TOKEN': csrf, Accept: 'application/json' },
        });

        messagesEl.innerHTML = `
            <div class="sw-chat-bubble sw-chat-bubble--assistant">
                <div class="sw-chat-avatar sw-chat-avatar--ai"><i class="fas fa-robot"></i></div>
                <div><div class="sw-chat-content">Conversation effacée. Comment puis-je vous aider ?</div></div>
            </div>
        `;
    }

    sendBtn?.addEventListener('click', sendMessage);
    input?.addEventListener('keydown', (e) => {
        if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            sendMessage();
        }
    });
    evaluateBtn?.addEventListener('click', evaluateLevel);
    clearBtn?.addEventListener('click', clearChat);

    if (messagesEl) {
        messagesEl.scrollTop = messagesEl.scrollHeight;
    }
})();
