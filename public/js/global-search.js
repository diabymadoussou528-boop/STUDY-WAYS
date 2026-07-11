(() => {
  const input = document.getElementById('globalSearch');
  if (!input) return;

  let panel = document.getElementById('globalSearchResults');
  if (!panel) {
    panel = document.createElement('div');
    panel.id = 'globalSearchResults';
    panel.className = 'global-search-panel search-dropdown search-dropdown--courses';
    panel.style.cssText = 'position:absolute;top:100%;left:0;right:0;background:#fff;border:1px solid #eee;border-radius:12px;box-shadow:0 12px 40px rgba(0,0,0,.12);z-index:100;display:none;max-height:420px;overflow:auto;margin-top:8px;';
    input.parentElement.style.position = 'relative';
    input.parentElement.appendChild(panel);
  }

  let timer;

  input.addEventListener('keydown', (e) => {
    if (e.key === 'Enter') {
      e.preventDefault();
      const q = input.value.trim();
      if (q.length >= 2) {
        window.location.href = `/courses/search?q=${encodeURIComponent(q)}`;
      }
    }
  });

  input.addEventListener('input', () => {
    clearTimeout(timer);
    const q = input.value.trim();
    if (q.length < 2) {
      panel.style.display = 'none';
      return;
    }

    timer = setTimeout(async () => {
      const res = await fetch(`/courses/search/preview?q=${encodeURIComponent(q)}`, {
        headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
      });
      const data = await res.json();

      if (!data.results?.length) {
        panel.innerHTML = `<div class="search-empty" style="padding:14px;text-align:center;color:#888;">Aucun cours trouvé</div>`;
        panel.style.display = 'block';
        return;
      }

      panel.innerHTML = `
        <div class="search-dropdown__header">Cours</div>
        ${data.results.map((c) => `
          <a href="${c.url}" class="search-preview-row" style="display:grid;grid-template-columns:56px 1fr;gap:10px;padding:10px 14px;text-decoration:none;color:inherit;border-bottom:1px solid #f3f4f6;">
            <img src="${c.thumbnail}" alt="" style="width:56px;height:32px;object-fit:cover;border-radius:4px;">
            <div><strong style="font-size:.82rem;display:block;">${c.title}</strong><small style="color:#888;">${c.instructors}</small></div>
          </a>
        `).join('')}
        <div class="search-dropdown__footer" style="padding:10px;text-align:center;">
          <a href="${data.results_url}" style="color:#8B2032;font-weight:700;text-decoration:none;">Voir tous les résultats</a>
        </div>
      `;
      panel.style.display = 'block';
    }, 250);
  });

  document.addEventListener('click', (e) => {
    if (!panel.contains(e.target) && e.target !== input) {
      panel.style.display = 'none';
    }
  });
})();
