(() => {
  'use strict';

  const input = document.getElementById('siteSearch');
  const form = input?.closest('form');
  const dropdown = document.getElementById('searchResults');
  const btn = document.getElementById('siteSearchBtn');
  let timer;

  if (!input) return;

  function goToResults(q) {
    const query = (q || input.value).trim();
    if (query.length < 2) return;
    window.location.href = `/courses/search?q=${encodeURIComponent(query)}`;
  }

  form?.addEventListener('submit', (e) => {
    e.preventDefault();
    goToResults();
  });

  btn?.addEventListener('click', () => goToResults());

  input.addEventListener('keydown', (e) => {
    if (e.key === 'Enter') {
      e.preventDefault();
      goToResults();
    }
  });

  if (!dropdown) return;

  input.addEventListener('input', () => {
    clearTimeout(timer);
    const q = input.value.trim();

    if (q.length < 2) {
      dropdown.hidden = true;
      dropdown.innerHTML = '';
      return;
    }

    timer = setTimeout(async () => {
      try {
        const res = await fetch(`/courses/search/preview?q=${encodeURIComponent(q)}`, {
          headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
        });
        const data = await res.json();
        renderPreview(data);
      } catch {
        dropdown.hidden = true;
      }
    }, 280);
  });

  function renderPreview(data) {
    if (!data.results?.length) {
      dropdown.innerHTML = `<div class="search-empty">Aucun cours pour « ${escapeHtml(data.query)} »</div>`;
      dropdown.hidden = false;
      dropdown.classList.add('search-dropdown--courses');
      return;
    }

    const rows = data.results.map((course) => `
      <a href="${course.url}" class="search-preview-row">
        <img src="${course.thumbnail}" alt="">
        <div>
          <strong>${escapeHtml(course.title)}</strong>
          <small>${escapeHtml(course.instructors)}${course.category ? ' · ' + escapeHtml(course.category) : ''}</small>
        </div>
      </a>
    `).join('');

    dropdown.innerHTML = `
      <div class="search-dropdown__header">Cours</div>
      ${rows}
      <div class="search-dropdown__footer">
        <a href="${data.results_url}">Voir tous les résultats (${data.total})</a>
      </div>
    `;
    dropdown.hidden = false;
    dropdown.classList.add('search-dropdown--courses');
  }

  document.addEventListener('click', (e) => {
    if (!dropdown.contains(e.target) && e.target !== input && e.target !== btn) {
      dropdown.hidden = true;
    }
  });

  function escapeHtml(str) {
    return String(str)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;');
  }
})();
