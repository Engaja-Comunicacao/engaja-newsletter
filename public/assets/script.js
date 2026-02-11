function addEmailTag(){
    const input = document.getElementById('emailInput');
    const list  = document.getElementById('emailsList');
    if(!input || !list) return;
  
    const value = (input.value || '').trim();
    if(!value) return;
  
    // tag visual
    const span = document.createElement('span');
    span.innerText = value;
  
    // hidden input para enviar ao PHP
    const hidden = document.createElement('input');
    hidden.type = 'hidden';
    hidden.name = 'recipient_emails[]';
    hidden.value = value;
  
    span.appendChild(hidden);
  
    // remover ao clicar
    span.style.cursor = 'pointer';
    span.title = 'Clique para remover';
    span.addEventListener('click', () => span.remove());
  
    list.appendChild(span);
    input.value = '';
  }
  
  // Newsletter items
  function addNewsItem(){
    const wrap = document.getElementById('newsItems');
    if(!wrap) return;
  
    const idx = wrap.children.length;
  
    const box = document.createElement('div');
    box.className = 'card';
    box.style.marginTop = '16px';
  
    box.innerHTML = `
      <div class="row">
        <div>
          <label><small class="muted">Portal</small></label>
          <input name="item_portal[]" placeholder="Portal (ex: MegaWhat)">
        </div>
        <div>
          <label><small class="muted">Data</small></label>
          <input type="date" name="item_date[]">
        </div>
      </div>
  
      <label><small class="muted">Título</small></label>
      <input name="item_title[]" placeholder="Título da notícia" required>
  
      <label><small class="muted">Descrição</small></label>
      <textarea name="item_desc[]" placeholder="Descrição"></textarea>
  
      <label><small class="muted">Link da notícia</small></label>
      <input name="item_link[]" placeholder="https://...">
  
      <label><small class="muted">PDF (opcional)</small></label>
      <input type="file" name="item_pdf[]" accept="application/pdf">
  
      <button type="button" class="secondary" onclick="this.parentElement.remove()">Remover notícia</button>
    `;
  
    wrap.appendChild(box);
  }
  