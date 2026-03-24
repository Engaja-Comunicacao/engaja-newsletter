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

// =========================
// CATEGORIES & ITEMS
// =========================

function generateRef() {
  return 'ref_' + Math.random().toString(36).substr(2, 9);
}

function addCategory(name = '', categoryId = '', ref = '') {
  // Se não vier referência, gera uma nova (usada para mapear os itens no PHP)
  const catRef = ref || categoryId || generateRef();

  const html = `
    <div class="category card" id="${catRef}" style="border:1px solid #ddd; padding:16px; margin-top:16px; background:#f9fafb;">
      <div style="display:flex; gap:10px; margin-bottom: 12px; align-items:center;">
        <input type="hidden" name="category_id[]" value="${categoryId}">
        <input type="hidden" name="category_ref[]" value="${catRef}">
        
        <input name="category_name[]" value="${escapeHtml(name)}" placeholder="Nome da categoria (Ex: Administrativo)" style="flex:1; margin:0;" required>
        
        <button type="button" onclick="document.getElementById('${catRef}').remove()" class="secondary" style="margin:0;">
          Remover Categoria
        </button>
      </div>

      <div id="items_${catRef}"></div>

      <button type="button" class="secondary" onclick="addNewsItem(null, '${catRef}')" style="margin-top:10px;">
        + Adicionar notícia nesta categoria
      </button>
    </div>
  `;

  document.getElementById('newsItems').insertAdjacentHTML('beforeend', html);
  return catRef;
}

function addNewsItem(item = null, catRef = '') {
  // Se tem ref da categoria, coloca dentro dela. Senão, vai pro container geral.
  const container = catRef ? document.getElementById(`items_${catRef}`) : document.getElementById('generalNewsItems');
  if (!container) return;

  const portal = item?.portal ?? '';
  const newsDate = item?.news_date ?? '';
  const title = item?.title ?? '';
  const desc = item?.description ?? '';
  const link = item?.link_url ?? '';
  const pdfPath = item?.pdf_path ?? '';
  const itemId = item?.id ?? '';
  // Se for edit e já tiver category_id do banco, usamos ele como referência.
  const actualCatRef = catRef || item?.category_id || '';

  const pdfHtml = pdfPath
    ? `<div><small>PDF atual:</small> <a href="${pdfPath}" target="_blank">abrir</a></div>`
    : '';

  const box = document.createElement('div');
  box.className = 'card';
  box.style.marginTop = '10px';
  box.style.borderLeft = '4px solid #3b82f6';

  box.innerHTML = `
    <input type="hidden" name="item_id[]" value="${itemId}">
    <input type="hidden" name="item_keep_pdf[]" value="${pdfPath}">
    <input type="hidden" name="item_category_ref[]" value="${actualCatRef}">

    <div style="display:flex; gap:10px; margin-bottom:10px;">
      <input name="item_portal[]" placeholder="Portal"
        value="${escapeHtml(portal)}"
        style="flex:2; min-width:200px;">

      <input type="date"
        name="item_date[]"
        value="${escapeHtml(newsDate)}"
        style="flex:1; min-width:140px;">
    </div>
    
    <input name="item_title[]" placeholder="Título" value="${escapeHtml(title)}" style="width:100%; margin-bottom:10px;">
    <textarea name="item_desc[]" placeholder="Descrição" style="width:100%; margin-bottom:10px;">${escapeHtml(desc)}</textarea>
    <input name="item_link[]" placeholder="Link" value="${escapeHtml(link)}" style="width:100%; margin-bottom:10px;">

    ${pdfHtml}
    <div style="display:flex; align-items:center; justify-content:space-between;">
      <input type="file" name="item_pdf[]" accept="application/pdf">
      <button type="button" onclick="this.parentElement.parentElement.remove()">Remover</button>
    </div>
  `;

  container.appendChild(box);
}

// =========================
// RENDER EDIT
// =========================

function renderEdit(categories, items) {
  document.getElementById('newsItems').innerHTML = '';
  document.getElementById('generalNewsItems').innerHTML = '';

  // Renderiza categorias
  categories.forEach(cat => {
    // Passa o ID real como referência para os itens acharem
    addCategory(cat.name, cat.id, cat.id); 
  });

  // Renderiza itens
  items.forEach(item => {
    if (item.category_id) {
      addNewsItem(item, item.category_id); // Joga na categoria
    } else {
      addNewsItem(item, ''); // Joga nas gerais
    }
  });
}

// =========================
// UTILS
// =========================

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
