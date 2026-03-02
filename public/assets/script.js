function parseEmails(raw) {
  return String(raw ?? '')
    .split(/[\s,;]+/g)   // separa por espaço, vírgula, ; e quebras de linha
    .map(s => s.trim())
    .filter(Boolean);
}

function isValidEmail(email) {
  // validação simples no front (a validação real continua no PHP)
  return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
}

function getExistingRecipientSet(listEl) {
  const set = new Set();
  listEl.querySelectorAll('input[name="recipient_emails[]"]').forEach((i) => {
    const v = (i.value || '').trim().toLowerCase();
    if (v) set.add(v);
  });
  return set;
}

function createEmailTag(email) {
  const span = document.createElement('span');
  span.title = 'Clique para remover';
  span.style.cursor = 'pointer';

  // texto visível
  span.appendChild(document.createTextNode(email + ' '));

  // hidden input para enviar ao PHP
  const hidden = document.createElement('input');
  hidden.type = 'hidden';
  hidden.name = 'recipient_emails[]';
  hidden.value = email;

  span.appendChild(hidden);

  // remover ao clicar
  span.addEventListener('click', () => span.remove());

  return span;
}

function addEmailTag() {
  const input = document.getElementById('emailInput');
  const list  = document.getElementById('emailsList');
  if (!input || !list) return;

  const raw = (input.value || '').trim();
  if (!raw) return;

  const existing = getExistingRecipientSet(list);
  const emails = parseEmails(raw);

  let addedAny = false;

  for (const emRaw of emails) {
    const em = emRaw.trim();
    const key = em.toLowerCase();

    if (!isValidEmail(em)) continue;
    if (existing.has(key)) continue;

    existing.add(key);
    list.appendChild(createEmailTag(em));
    addedAny = true;
  }

  if (addedAny) {
    input.value = '';
  }
  input.focus();
}

// Enter adiciona
document.addEventListener('DOMContentLoaded', () => {
  const input = document.getElementById('emailInput');
  if (!input) return;

  input.addEventListener('keydown', (e) => {
    if (e.key === 'Enter') {
      e.preventDefault();
      addEmailTag();
    }
  });

  // colou uma lista? adiciona tudo
  input.addEventListener('paste', () => {
    setTimeout(addEmailTag, 0);
  });
});
  
// Newsletter items
function addNewsItem(existingItems) {
  const wrap = document.getElementById('newsItems');
  if (!wrap) return;
  // Se veio lista de itens (modo edição), renderiza todos de uma vez
  if (Array.isArray(existingItems)) {
    wrap.innerHTML = '';
    existingItems.forEach(item => addNewsItem(item));
    return;
  }
  // Se veio 1 item (modo edição), usa como defaults
  const item = existingItems || null;
  const box = document.createElement('div');
  box.className = 'card';
  box.style.marginTop = '16px';
  const portal = item?.portal ?? '';
  const newsDate = item?.news_date ?? ''; // já vem YYYY-MM-DD do banco
  const title = item?.title ?? '';
  const desc = item?.description ?? '';
  const link = item?.link_url ?? '';
  const pdfPath = item?.pdf_path ?? '';
  const itemId = item?.id ?? '';
  const pdfHtml = pdfPath
    ? `<div style="margin-top:6px;">
        <small class="muted">PDF atual:</small>
        <a href="${pdfPath}" target="_blank" rel="noopener noreferrer">abrir</a>
      </div>`
    : '';
  box.innerHTML = `
    <input type="hidden" name="item_id[]" value="${itemId}">
    <input type="hidden" name="item_keep_pdf[]" value="${pdfPath}">
    <div class="row">
      <div>
        <label><small class="muted">Portal</small></label>
        <input name="item_portal[]" placeholder="Portal (ex: MegaWhat)" value="${escapeHtml(portal)}">
      </div>
      <div>
        <label><small class="muted">Data</small></label>
        <input type="date" name="item_date[]" value="${escapeAttr(newsDate)}">
      </div>
    </div>
    <label><small class="muted">Título</small></label>
    <input name="item_title[]" placeholder="Título da notícia" required value="${escapeAttr(title)}">
    <label><small class="muted">Descrição</small></label>
    <textarea name="item_desc[]" placeholder="Descrição">${escapeHtml(desc)}</textarea>
    <label><small class="muted">Link da notícia</small></label>
    <input name="item_link[]" placeholder="https://..." value="${escapeAttr(link)}">
    <label><small class="muted">PDF (opcional)</small></label>
    ${pdfHtml}
    <input type="file" name="item_pdf[]" accept="application/pdf">
    <button type="button" class="secondary" onclick="this.parentElement.remove()">Remover notícia</button>
  `;
  wrap.appendChild(box);
}

function escapeHtml(str) {
  return String(str ?? '')
    .replaceAll('&', '&amp;')
    .replaceAll('<', '&lt;')
    .replaceAll('>', '&gt;')
    .replaceAll('"', '&quot;')
    .replaceAll("'", '&#039;');
}

function escapeAttr(str) {
  // pra value="..."
  return escapeHtml(str);
}
