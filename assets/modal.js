document.addEventListener("DOMContentLoaded", function() {
  // Insert modal HTML if missing (defensive)
  if (!document.getElementById("plannedModal")) {
    const modalHtml = `
    <div id="plannedModal" class="modal-backdrop no-print" aria-hidden="true" style="display:none;">
      <div class="modal" role="dialog" aria-modal="true" aria-labelledby="plannedModalTitle">
        <header class="modal-header">
          <h3 id="plannedModalTitle">Tambah Catatan</h3>
          <button class="modal-close" aria-label="Tutup">&times;</button>
        </header>
        <div class="modal-body">
          <form id="plannedFormPlaceholder">
            <input type="hidden" name="action" value="add_note" />
            <label> Tanggal
              <input type="date" name="note_date" />
            </label>
            <label> Prioritas
              <select name="note_priority">
                <option>Normal</option>
                <option>Tinggi</option>
                <option>Rendah</option>
              </select>
            </label>
            <label> Catatan
              <textarea name="note_text" placeholder="Contoh: Membuat report selama 1 minggu"></textarea>
            </label>
            <div class="modal-actions">
              <button type="button" class="btn-plain modal-cancel">Batal</button>
              <button type="submit" class="btn-primary">Simpan</button>
            </div>
          </form>
        </div>
      </div>
    </div>`;
    document.body.insertAdjacentHTML('beforeend', modalHtml);
  }

  const modal = document.getElementById('plannedModal');
  if (!modal) return;

  function showModal() {
    modal.style.display = 'flex';
    modal.setAttribute('aria-hidden','false');
    document.body.style.overflow = 'hidden';
    const first = modal.querySelector('input, textarea, select, button');
    if (first) first.focus();
  }
  function hideModal() {
    modal.style.display = 'none';
    modal.setAttribute('aria-hidden','true');
    document.body.style.overflow = '';
  }

  modal.querySelectorAll('.modal-close, .modal-cancel').forEach(b => b.addEventListener('click', function(e){ e.preventDefault(); hideModal(); }));
  modal.addEventListener('click', function(e){ if (e.target === modal) hideModal(); });
  document.addEventListener('keydown', function(e){ if (e.key === 'Escape' && modal.getAttribute('aria-hidden') === 'false') hideModal(); });

  // Move inline form (if exists) into modal, and hide original wrapper
  // Important: ignore any form already inside the modal to avoid hiding the modal itself
  const inlineForm = Array.from(document.querySelectorAll('form')).find(f =>
    !f.closest('#plannedModal') &&
    f.querySelector('textarea[name="note_text"], textarea[name="catatan"], input[name="note_date"], input[name="tanggal"]')
  );

  if (inlineForm && !inlineForm.closest('#plannedModal')) {
    const wrap = inlineForm.closest('section, .card, .dashboard-card, .tasks-card, .planned, .planned-list') || inlineForm.parentElement;
    if (wrap) { wrap.style.display = 'none'; }
    const modalBody = modal.querySelector('.modal-body');
    const placeholder = modalBody.querySelector('#plannedFormPlaceholder');
    if (placeholder) placeholder.remove();
    modalBody.appendChild(inlineForm);
  }

  // Hook + buttons (common selectors)
  const openBtns = Array.from(document.querySelectorAll('.add-btn, .task-add-btn, .tasks-card .add-btn, .planned .add-btn'));
  if (openBtns.length === 0) {
    const plus = Array.from(document.querySelectorAll('button,a')).find(el => el.textContent && el.textContent.trim() === '+');
    if (plus) openBtns.push(plus);
  }
  openBtns.forEach(btn => btn.addEventListener('click', function(e){ e.preventDefault(); showModal(); }));

});