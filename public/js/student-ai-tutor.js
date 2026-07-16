(() => {
    const app = document.getElementById('aiTutorApp');
    if (!app) return;

    const chatUrl = app.dataset.chatUrl;
    const clearUrl = app.dataset.clearUrl;
    const historyUrl = app.dataset.historyUrl;
    const createUrl = app.dataset.createUrl;
    const renameBase = app.dataset.renameUrl;
    const deleteBase = app.dataset.deleteUrl;
    const userAvatar = app.dataset.userAvatar;
    const csrf = app.dataset.csrf;
    const isPremium = app.dataset.isPremium === '1';

    const courseSelect = document.getElementById('aiCourseSelect');
    const lessonSelect = document.getElementById('aiLessonSelect');
    const topicInput = document.getElementById('aiTopicInput');
    const messagesEl = document.getElementById('aiChatMessages');
    const input = document.getElementById('aiChatInput');
    const sendBtn = document.getElementById('aiSendBtn');
    const clearBtn = document.getElementById('aiClearBtn');
    const evaluateBtn = document.getElementById('aiEvaluateBtn');
    const suggested = document.getElementById('aiSuggested');
    const modes = document.getElementById('aiModes');
    const conversationList = document.getElementById('aiConversationList');
    const newConversationBtn = document.getElementById('aiNewConversation');

    let mode = 'chat';
    let isLoading = false;
    let conversationId = app.dataset.conversationId ? Number(app.dataset.conversationId) : null;

    function renderMarkdown(text) {
        if (window.marked && typeof window.marked.parse === 'function') {
            return window.marked.parse(text || '');
        }
        return (text || '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/\n/g, '<br>');
    }

    function hydrateMarkdown() {
        messagesEl?.querySelectorAll('[data-md]').forEach((el) => {
            const raw = el.textContent;
            el.innerHTML = renderMarkdown(raw);
            el.removeAttribute('data-md');
        });
    }

    function setActiveConversation(id) {
        conversationId = id;
        app.dataset.conversationId = id || '';
        conversationList?.querySelectorAll('.sw-ai-conversation').forEach((el) => {
            el.classList.toggle('is-active', Number(el.dataset.id) === Number(id));
        });
    }

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

    modes?.addEventListener('click', (e) => {
        const btn = e.target.closest('[data-mode]');
        if (!btn) return;

        modes.querySelectorAll('.sw-ai-mode').forEach((el) => el.classList.remove('is-active'));
        btn.classList.add('is-active');
        mode = btn.dataset.mode || 'chat';

        if (btn.dataset.prompt && input) {
            input.value = btn.dataset.prompt;
            input.focus();
        }
    });

    suggested?.addEventListener('click', (e) => {
        const btn = e.target.closest('[data-suggest]');
        if (!btn || !input) return;
        input.value = btn.dataset.suggest;
        input.focus();
    });

    function appendBubble(role, content, asMarkdown = false) {
        const bubble = document.createElement('div');
        bubble.className = `sw-chat-bubble sw-chat-bubble--${role === 'user' ? 'user' : 'assistant'}`;

        if (role === 'user') {
            bubble.innerHTML = `
                <img src="${userAvatar}" alt="" class="sw-chat-avatar">
                <div><div class="sw-chat-content"></div></div>
            `;
            bubble.querySelector('.sw-chat-content').textContent = content;
        } else {
            bubble.innerHTML = `
                <div class="sw-chat-avatar sw-chat-avatar--ai"><i class="fas fa-robot"></i></div>
                <div><div class="sw-chat-content"></div></div>
            `;
            const contentEl = bubble.querySelector('.sw-chat-content');
            if (asMarkdown) {
                contentEl.innerHTML = renderMarkdown(content);
            } else {
                contentEl.textContent = content;
            }
        }

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

    function welcomeHtml() {
        return `
            <div class="sw-chat-bubble sw-chat-bubble--assistant" id="aiWelcome">
                <div class="sw-chat-avatar sw-chat-avatar--ai"><i class="fas fa-robot"></i></div>
                <div><div class="sw-chat-content">Nouvelle conversation. Comment puis-je vous aider ?</div></div>
            </div>
        `;
    }

    async function loadHistory(id) {
        if (!messagesEl) return;
        const res = await fetch(`${historyUrl}?conversation_id=${id || ''}`, {
            headers: { Accept: 'application/json' },
        });
        const data = await res.json();
        messagesEl.innerHTML = welcomeHtml();
        (data.messages || []).forEach((msg) => {
            appendBubble(msg.role, msg.content, msg.role === 'assistant');
        });
    }

    async function postChat(message, chatMode) {
        const res = await fetch(chatUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrf,
                Accept: 'application/json',
            },
            body: JSON.stringify({
                message,
                course_id: courseSelect?.value || null,
                lesson_id: lessonSelect?.value || null,
                topic: topicInput?.value || null,
                mode: chatMode,
                conversation_id: conversationId || null,
            }),
        });

        const data = await res.json();
        return { res, data };
    }

    function upsertConversationInList(id, title) {
        if (!conversationList) return;
        let item = conversationList.querySelector(`[data-id="${id}"]`);
        if (!item) {
            conversationList.querySelector('.sw-ai-conversation-empty')?.remove();
            item = document.createElement('li');
            item.className = 'sw-ai-conversation';
            item.dataset.id = id;
            item.innerHTML = `
                <button type="button" class="sw-ai-conversation__open" data-open-conversation="${id}"></button>
                <button type="button" class="sw-ai-conversation__delete" data-delete-conversation="${id}" aria-label="Supprimer"><i class="fas fa-trash"></i></button>
            `;
            conversationList.prepend(item);
        }
        item.querySelector('.sw-ai-conversation__open').textContent = title;
        setActiveConversation(id);
    }

    async function sendMessage(overrideText = null, overrideMode = null) {
        if (!isPremium) {
            appendBubble('assistant', 'Passez Premium pour discuter avec le tuteur IA.');
            return;
        }

        const text = (overrideText ?? input?.value ?? '').trim();
        if (!text || isLoading) return;

        const activeMode = overrideMode || mode || 'chat';
        appendBubble('user', overrideText && overrideMode === 'evaluation' ? 'Évaluer mon niveau' : text);
        if (!overrideText && input) input.value = '';
        isLoading = true;
        if (sendBtn) sendBtn.disabled = true;
        showTyping();

        try {
            const { res, data } = await postChat(text, activeMode);
            hideTyping();

            if (!res.ok) {
                appendBubble('assistant', data.error || 'Une erreur est survenue.');
                return;
            }

            if (data.conversation_id) {
                upsertConversationInList(data.conversation_id, data.conversation_title || 'Discussion');
            }

            appendBubble('assistant', data.reply, true);
        } catch (err) {
            hideTyping();
            appendBubble('assistant', 'Impossible de contacter le tuteur IA. Vérifiez votre connexion.');
        } finally {
            isLoading = false;
            if (sendBtn) sendBtn.disabled = !isPremium;
            messagesEl.scrollTop = messagesEl.scrollHeight;
        }
    }

    async function evaluateLevel() {
        const prompt = 'Évalue mon niveau sur ce cours. Pose-moi 3 questions progressives pour estimer mon niveau (débutant, intermédiaire, avancé), puis donne des recommandations.';
        await sendMessage(prompt, 'evaluation');
    }

    async function clearChat() {
        if (!confirm('Effacer l\'historique de cette conversation ?')) return;

        await fetch(`${clearUrl}?course_id=${courseSelect?.value || ''}`, {
            method: 'DELETE',
            headers: { 'X-CSRF-TOKEN': csrf, Accept: 'application/json' },
        });

        if (messagesEl) messagesEl.innerHTML = welcomeHtml();
    }

    conversationList?.addEventListener('click', async (e) => {
        const openBtn = e.target.closest('[data-open-conversation]');
        const deleteBtn = e.target.closest('[data-delete-conversation]');

        if (deleteBtn) {
            const id = deleteBtn.dataset.deleteConversation;
            if (!confirm('Supprimer cette conversation ?')) return;
            await fetch(`${deleteBase}/${id}`, {
                method: 'DELETE',
                headers: { 'X-CSRF-TOKEN': csrf, Accept: 'application/json' },
            });
            deleteBtn.closest('.sw-ai-conversation')?.remove();
            if (Number(conversationId) === Number(id)) {
                setActiveConversation(null);
                if (messagesEl) messagesEl.innerHTML = welcomeHtml();
            }
            return;
        }

        if (openBtn) {
            const id = Number(openBtn.dataset.openConversation);
            setActiveConversation(id);
            await loadHistory(id);
        }
    });

    newConversationBtn?.addEventListener('click', async () => {
        const res = await fetch(createUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrf,
                Accept: 'application/json',
            },
            body: JSON.stringify({
                course_id: courseSelect?.value || null,
                title: 'Nouvelle discussion',
            }),
        });
        const data = await res.json();
        if (data.conversation) {
            upsertConversationInList(data.conversation.id, data.conversation.title);
            if (messagesEl) messagesEl.innerHTML = welcomeHtml();
        }
    });

    sendBtn?.addEventListener('click', () => sendMessage());
    input?.addEventListener('keydown', (e) => {
        if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            sendMessage();
        }
    });
    evaluateBtn?.addEventListener('click', evaluateLevel);
    clearBtn?.addEventListener('click', clearChat);

    hydrateMarkdown();
    if (messagesEl) messagesEl.scrollTop = messagesEl.scrollHeight;
})();
