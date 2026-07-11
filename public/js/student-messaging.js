(() => {
    const app = document.getElementById('messagingApp');
    if (!app) return;

    const threadUrl = app.dataset.threadUrl;
    const sendUrl = app.dataset.sendUrl;
    const userId = parseInt(app.dataset.userId, 10);
    const userAvatar = app.dataset.userAvatar;
    const csrf = app.dataset.csrf;

    const convList = document.getElementById('convList');
    const convSearch = document.getElementById('convSearch');
    const chatMessages = document.getElementById('chatMessages');
    const chatEmpty = document.getElementById('chatEmpty');
    const chatCompose = document.getElementById('chatCompose');
    const chatInput = document.getElementById('chatInput');
    const chatSendBtn = document.getElementById('chatSendBtn');
    const chatHeaderUser = document.getElementById('chatHeaderUser');
    const chatHeaderPlaceholder = document.getElementById('chatHeaderPlaceholder');
    const chatHeaderAvatar = document.getElementById('chatHeaderAvatar');
    const chatHeaderName = document.getElementById('chatHeaderName');
    const chatHeaderCourse = document.getElementById('chatHeaderCourse');
    const newConvSelect = document.getElementById('newConvSelect');
    const startConvBtn = document.getElementById('startConvBtn');

    let activeOtherId = null;
    let activeCourseId = null;
    let pollTimer = null;

    function selectConversation(otherId, courseId, name, avatar, course) {
        activeOtherId = otherId;
        activeCourseId = courseId;

        convList?.querySelectorAll('.sw-conv-item').forEach((el) => {
            el.classList.toggle('is-active', parseInt(el.dataset.otherId, 10) === otherId && parseInt(el.dataset.courseId, 10) === courseId);
        });

        if (chatHeaderUser) {
            chatHeaderUser.style.display = 'flex';
            chatHeaderPlaceholder.style.display = 'none';
            chatHeaderAvatar.src = avatar;
            chatHeaderName.textContent = name;
            chatHeaderCourse.textContent = course;
        }

        chatCompose.style.display = 'block';
        loadThread();

        if (pollTimer) clearInterval(pollTimer);
        pollTimer = setInterval(loadThread, 8000);
    }

    function renderMessages(messages) {
        if (!messages.length) {
            chatMessages.innerHTML = '<div class="empty-state premium-empty"><p>Aucun message. Envoyez le premier !</p></div>';
            return;
        }

        chatMessages.innerHTML = messages.map((m) => {
            const isMine = m.from_user_id === userId;
            const avatar = isMine ? userAvatar : (m.sender?.avatar || '');
            const bubbleClass = isMine ? 'user' : 'other';

            return `
                <div class="sw-chat-bubble sw-chat-bubble--${bubbleClass}">
                    <img src="${avatar}" alt="" class="sw-chat-avatar">
                    <div>
                        <div class="sw-chat-content">${escapeHtml(m.body)}</div>
                        <div class="sw-chat-meta">${formatTime(m.created_at)}</div>
                    </div>
                </div>
            `;
        }).join('');

        chatMessages.scrollTop = chatMessages.scrollHeight;
    }

    async function loadThread() {
        if (!activeOtherId || !activeCourseId) return;

        try {
            const res = await fetch(`${threadUrl}?other_user_id=${activeOtherId}&course_id=${activeCourseId}`, {
                headers: { Accept: 'application/json' },
            });
            const data = await res.json();

            if (res.ok) {
                renderMessages(data.messages || []);
            }
        } catch (e) {
            /* silent */
        }
    }

    async function sendMessage() {
        const body = chatInput?.value?.trim();
        if (!body || !activeOtherId || !activeCourseId) return;

        chatSendBtn.disabled = true;

        try {
            const res = await fetch(sendUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrf,
                    Accept: 'application/json',
                },
                body: JSON.stringify({
                    recipient_id: activeOtherId,
                    course_id: activeCourseId,
                    body,
                }),
            });

            const data = await res.json();

            if (res.ok) {
                chatInput.value = '';
                await loadThread();
            } else {
                alert(data.error || 'Envoi impossible.');
            }
        } catch (e) {
            alert('Erreur réseau.');
        } finally {
            chatSendBtn.disabled = false;
        }
    }

    convList?.addEventListener('click', (e) => {
        const item = e.target.closest('.sw-conv-item');
        if (!item) return;

        selectConversation(
            parseInt(item.dataset.otherId, 10),
            parseInt(item.dataset.courseId, 10),
            item.dataset.name,
            item.dataset.avatar,
            item.dataset.course,
        );
    });

    startConvBtn?.addEventListener('click', () => {
        const val = newConvSelect?.value;
        if (!val) return;

        const [teacherId, courseId] = val.split(':').map(Number);
        const opt = newConvSelect.selectedOptions[0];

        selectConversation(teacherId, courseId, opt.dataset.teacher, opt.dataset.avatar, opt.dataset.course);
    });

    convSearch?.addEventListener('input', () => {
        const q = convSearch.value.toLowerCase();
        convList?.querySelectorAll('.sw-conv-item').forEach((el) => {
            el.style.display = el.textContent.toLowerCase().includes(q) ? '' : 'none';
        });
    });

    chatSendBtn?.addEventListener('click', sendMessage);
    chatInput?.addEventListener('keydown', (e) => {
        if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            sendMessage();
        }
    });

    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML.replace(/\n/g, '<br>');
    }

    function formatTime(iso) {
        try {
            return new Date(iso).toLocaleString('fr-FR', { hour: '2-digit', minute: '2-digit', day: '2-digit', month: 'short' });
        } catch (e) {
            return '';
        }
    }
})();
