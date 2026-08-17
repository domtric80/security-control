// Auto-dismiss alerts after 5 s
const initRequisitiSecApp = () => {
  document.querySelectorAll('.alert-dismissible').forEach(el => {
    setTimeout(() => {
      const bsAlert = bootstrap.Alert.getOrCreateInstance(el);
      bsAlert.close();
    }, 5000);
  });

  // Confirm dangerous actions
  document.querySelectorAll('[data-confirm]').forEach(el => {
    el.addEventListener('click', e => {
      if (!confirm(el.dataset.confirm || 'Confermare?')) e.preventDefault();
    });
  });

  // Toggle "all" checkboxes in a group
  document.querySelectorAll('[data-toggle-group]').forEach(master => {
    const group = master.dataset.toggleGroup;
    const slaves = document.querySelectorAll(`[data-group="${group}"]`);
    master.addEventListener('change', () => {
      slaves.forEach(s => { s.checked = master.checked; });
    });
  });

  window.syncPirAttachmentForm = form => {
    const type = form?.querySelector('.pir-attachment-type');
    const fileField = form?.querySelector('.pir-file-field');
    const linkField = form?.querySelector('.pir-link-field');
    const fileInput = fileField?.querySelector('input');
    const linkInput = linkField?.querySelector('input');
    const isLink = type?.value === 'link';

    fileField?.classList.toggle('d-none', isLink);
    linkField?.classList.toggle('d-none', !isLink);

    if (fileInput) {
      fileInput.required = !isLink;
      fileInput.disabled = isLink;
      if (isLink) fileInput.value = '';
    }
    if (linkInput) {
      linkInput.required = isLink;
      linkInput.disabled = !isLink;
      if (!isLink) linkInput.value = '';
    }
  };

  const canAutoFillAttachmentTitle = titleInput => {
    return titleInput && (titleInput.value.trim() === '' || titleInput.dataset.autoTitle === '1');
  };

  const setAttachmentTitle = (form, title, force = false) => {
    const titleInput = form?.querySelector('.pir-attachment-title');
    const cleanTitle = (title || '').trim();
    if (!cleanTitle || (!force && !canAutoFillAttachmentTitle(titleInput))) return false;
    titleInput.value = cleanTitle;
    titleInput.dataset.autoTitle = '1';
    return true;
  };

  const fetchLinkTitle = async (form, force = false) => {
    const urlInput = form?.querySelector('.pir-attachment-url');
    const status = form?.querySelector('.pir-link-title-status');
    const url = urlInput?.value.trim();
    const titleInput = form?.querySelector('.pir-attachment-title');
    if (!url || (!force && !canAutoFillAttachmentTitle(titleInput))) return;

    if (status) status.textContent = 'Recupero titolo in corso...';
    try {
      const endpoint = form?.dataset.titleEndpoint || 'link_title.php';
      const response = await fetch(`${endpoint}?url=${encodeURIComponent(url)}`, {
        headers: { Accept: 'application/json' }
      });
      if (!response.ok) {
        if (status) status.textContent = 'Titolo non recuperabile: puoi inserirlo manualmente.';
        return;
      }
      const payload = await response.json();
      if (payload?.title && setAttachmentTitle(form, payload.title, force)) {
        if (status) status.textContent = 'Titolo recuperato automaticamente. Puoi modificarlo.';
      } else if (status) {
        status.textContent = 'Titolo non trovato: puoi inserirlo manualmente.';
      }
    } catch (_) {
      if (status) status.textContent = 'Titolo non recuperabile: puoi inserirlo manualmente.';
      // Il titolo è solo un aiuto: se il recupero fallisce, l'utente può compilarlo a mano.
    }
  };

  document.querySelectorAll('.pir-attachment-form').forEach(form => {
    const type = form.querySelector('.pir-attachment-type');
    const titleInput = form.querySelector('.pir-attachment-title');
    const fileInput = form.querySelector('.pir-attachment-file');
    const urlInput = form.querySelector('.pir-attachment-url');
    const fetchTitleButton = form.querySelector('.pir-fetch-title');
    let titleFetchTimer = null;
    type?.addEventListener('change', () => window.syncPirAttachmentForm(form));
    titleInput?.addEventListener('input', () => {
      titleInput.dataset.autoTitle = titleInput.value.trim() === '' ? '1' : '0';
    });
    fileInput?.addEventListener('change', () => {
      const fileName = fileInput.files?.[0]?.name || '';
      setAttachmentTitle(form, fileName);
    });
    urlInput?.addEventListener('input', () => {
      clearTimeout(titleFetchTimer);
      titleFetchTimer = setTimeout(() => fetchLinkTitle(form), 800);
    });
    urlInput?.addEventListener('blur', () => fetchLinkTitle(form));
    urlInput?.addEventListener('change', () => fetchLinkTitle(form));
    fetchTitleButton?.addEventListener('click', () => fetchLinkTitle(form, true));
    window.syncPirAttachmentForm(form);
  });

  document.querySelectorAll('.pir-add-participant').forEach(button => {
    button.addEventListener('click', () => {
      const wrapper = button.closest('.col-12');
      const body = wrapper?.querySelector('.pir-participants-body');
      const lastRow = body?.querySelector('tr:last-child');
      if (!body || !lastRow) return;

      const newRow = lastRow.cloneNode(true);
      newRow.querySelectorAll('input').forEach(input => {
        input.value = '';
      });
      newRow.querySelectorAll('select').forEach(select => {
        select.value = '1';
      });
      body.appendChild(newRow);
    });
  });

  const validatePirReviewForm = form => {
    const field = selector => form.querySelector(selector) || document.querySelector(`${selector}[form="${form.id}"]`);
    const status = field('.pir-review-status');
    const applicazione = field('.pir-review-applicazione');
    const rientro = field('.pir-review-rientro');
    const participantSelect = field('.pir-review-participant');
    const participantRadio = field('.pir-review-ref-participant');
    const analystRadio = field('.pir-review-ref-analyst');
    const errorBox = document.querySelector(`[data-form-id="${form.id}"] .pir-review-errors`)
      || form.querySelector('.pir-review-errors');
    const submitButton = field('.pir-review-submit');
    const errors = [];
    const selectedStatus = status?.value || '';

    applicazione?.classList.remove('is-invalid');
    rientro?.classList.remove('is-invalid');
    participantSelect?.classList.remove('is-invalid');

    if (selectedStatus !== '' && !applicazione?.value.trim()) {
      errors.push('Compila il campo Applicazione / motivazione.');
      applicazione?.classList.add('is-invalid');
    }
    if (selectedStatus === 'KO' && !rientro?.value.trim()) {
      errors.push('Per uno stato KO indica Rientro / eccezione.');
      rientro?.classList.add('is-invalid');
    }
    if (selectedStatus !== '') {
      if (participantRadio?.checked && !participantSelect?.value) {
        errors.push('Se scegli un partecipante, seleziona il nome dal menu.');
        participantSelect?.classList.add('is-invalid');
      }
      if (analystRadio?.checked && form.dataset.hasAnalyst !== '1') {
        errors.push('Assegna prima un analista sicurezza PIR nel riquadro Gestione PIR sicurezza.');
      }
    }

    if (errorBox) {
      errorBox.classList.toggle('d-none', errors.length === 0);
      errorBox.innerHTML = errors.length ? `<ul class="mb-0">${errors.map(error => `<li>${error}</li>`).join('')}</ul>` : '';
    }
    if (submitButton) {
      submitButton.disabled = errors.length > 0;
    }
    return errors.length === 0;
  };

  document.querySelectorAll('.pir-review-form').forEach(form => {
    const field = selector => form.querySelector(selector) || document.querySelector(`${selector}[form="${form.id}"]`);
    const modal = document.querySelector(`[data-form-id="${form.id}"]`) || form.querySelector('.modal');
    const participantSelect = field('.pir-review-participant');
    const participantRadio = field('.pir-review-ref-participant');
    const analystRadio = field('.pir-review-ref-analyst');

    participantSelect?.addEventListener('change', () => {
      if (participantSelect.value && participantRadio) {
        participantRadio.checked = true;
      }
      validatePirReviewForm(form);
    });
    analystRadio?.addEventListener('change', () => validatePirReviewForm(form));
    participantRadio?.addEventListener('change', () => validatePirReviewForm(form));
    ['.pir-review-status', '.pir-review-applicazione', '.pir-review-rientro'].forEach(selector => {
      const reviewField = field(selector);
      reviewField?.addEventListener('input', () => validatePirReviewForm(form));
      reviewField?.addEventListener('change', () => validatePirReviewForm(form));
    });
    modal?.addEventListener('show.bs.modal', () => validatePirReviewForm(form));
    form.addEventListener('submit', event => {
      if (!validatePirReviewForm(form)) {
        event.preventDefault();
      }
    });
    validatePirReviewForm(form);
  });
};

if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', initRequisitiSecApp);
} else {
  initRequisitiSecApp();
}
