// Busca juegos en webs externas generando enlaces de búsqueda
// Reglas: código en español, simple, sin dependencias externas
(function () {
  'use strict';

  const $ = (sel) => document.querySelector(sel);
  const form = $('#search-external-form');
  if (!form) return; // pestaña clásica sin esta sección

  const nameInput = $('#se-name');
  const md5Input = $('#se-md5');
  const sha1Input = $('#se-sha1');
  const crcInput = $('#se-crc');
  const btnBuild = $('#se-build-links');
  const btnOpenAll = $('#se-open-all');
  const errorsEl = $('#se-errors');
  const results = $('#se-results');
  const linksList = $('#se-links');
  const btnCheckArchive = $('#se-check-archive');
  const csrfInput = $('#se-csrf');
  const archiveBox = $('#se-archive-check');
  const archiveStatus = $('#se-archive-status');
  const container = document.getElementById('search-external');
  const TXT_FOUND = (container && container.getAttribute('data-archive-found')) || 'Found: ';
  const TXT_NOT_FOUND = (container && container.getAttribute('data-archive-not-found')) || 'Not found.';
  const TXT_VIEW = (container && container.getAttribute('data-archive-view')) || 'View on Archive';
  const SE_MD5_INVALID = (container && container.getAttribute('data-se-md5-invalid')) || 'MD5 must be 32 hexadecimal characters.';
  const SE_SHA1_INVALID = (container && container.getAttribute('data-se-sha1-invalid')) || 'SHA1 must be 40 hexadecimal characters.';
  const SE_CRC_INVALID  = (container && container.getAttribute('data-se-crc-invalid')) || 'CRC must be 8 hexadecimal characters.';
  const SE_REQUIRE_ONE  = (container && container.getAttribute('data-se-require-one')) || 'Enter at least one: Name, MD5, SHA1 or CRC.';
  const SE_NO_LINKS     = (container && container.getAttribute('data-se-no-links')) || 'No links were generated.';
  const SE_INVALID_RESP = (container && container.getAttribute('data-se-invalid-response')) || 'Invalid response.';
  const SE_NO_RESPONSE  = (container && container.getAttribute('data-se-no-response')) || 'No response';
  const SE_ALREADY_RUN  = (container && container.getAttribute('data-se-already-running')) || 'A check is already in progress…';
  const SE_WAIT_SECONDS = (container && container.getAttribute('data-se-wait-seconds')) || 'Wait {s}s to try again.';
  const SE_CHECKING     = (container && container.getAttribute('data-se-checking')) || 'Checking…';
  const SE_TOO_MANY     = (container && container.getAttribute('data-se-too-many')) || 'Too many requests. Try again later.';
  const SE_COULDNT      = (container && container.getAttribute('data-se-could-not-query')) || 'Could not query Archive.';
  const SE_COULDNT_ORG  = (container && container.getAttribute('data-se-could-not-query-org')) || 'Could not query Archive.org.';

  const HEX32 = /^[0-9a-fA-F]{32}$/;
  const HEX40 = /^[0-9a-fA-F]{40}$/;
  const HEX8  = /^[0-9a-fA-F]{8}$/;

  // Control de estado para rate limit en UI (frontend)
  let enCurso = false;
  let cooldownHasta = 0; // timestamp ms
  const COOLDOWN_MS = 3000; // Debe estar alineado con backend (3s)
  const textoOriginalBtn = btnCheckArchive?.textContent || 'Comprobar Archive';

  function limpiar() {
    errorsEl.textContent = '';
    linksList.innerHTML = '';
    results.hidden = true;
    btnOpenAll.disabled = true;
  }

  function normalizarTexto(str) {
    return (str || '').trim();
  }

  function construirEnlaces() {
    limpiar();

    const name = normalizarTexto(nameInput.value);
    const md5 = normalizarTexto(md5Input.value);
    const sha1 = normalizarTexto(sha1Input.value);
    const crc = normalizarTexto(crcInput.value);

    // Validaciones suaves
    const errs = [];
    if (md5 && !HEX32.test(md5)) errs.push(SE_MD5_INVALID);
    if (sha1 && !HEX40.test(sha1)) errs.push(SE_SHA1_INVALID);
    if (crc && !HEX8.test(crc)) errs.push(SE_CRC_INVALID);

    if (!name && !md5 && !sha1 && !crc) {
      errs.push(SE_REQUIRE_ONE);
    }

    if (errs.length) {
      errorsEl.textContent = errs.join(' ');
      return;
    }

    const enlaces = [];

    // Utilizamos búsquedas site: para no depender de endpoints propietarios
    // DuckDuckGo
    const ddg = (q) => `https://duckduckgo.com/?q=${encodeURIComponent(q)}`;

    // myrient: por nombre
    if (name) enlaces.push({
      sitio: 'myrient (nombre)',
      url: ddg(`site:myrient.erista.me ${name}`)
    });
    // myrient: por hashes
    if (md5) enlaces.push({ sitio: 'myrient (MD5)', url: ddg(`site:myrient.erista.me ${md5}`) });
    if (sha1) enlaces.push({ sitio: 'myrient (SHA1)', url: ddg(`site:myrient.erista.me ${sha1}`) });
    if (crc) enlaces.push({ sitio: 'myrient (CRC)', url: ddg(`site:myrient.erista.me ${crc}`) });

    // vimm: por nombre (hashes no suelen estar indexados)
    if (name) enlaces.push({
      sitio: 'vimm (nombre)',
      url: ddg(`site:vimm.net ${name}`)
    });

    // archive.org: por nombre y por hashes
    if (name) enlaces.push({
      sitio: 'Archive (nombre)',
      url: ddg(`site:archive.org ${name}`)
    });
    if (md5) enlaces.push({ sitio: 'Archive (MD5)', url: ddg(`site:archive.org ${md5}`) });
    if (sha1) enlaces.push({ sitio: 'Archive (SHA1)', url: ddg(`site:archive.org ${sha1}`) });
    if (crc) enlaces.push({ sitio: 'Archive (CRC)', url: ddg(`site:archive.org ${crc}`) });

    // Google alternativo (opcionales)
    const g = (q) => `https://www.google.com/search?q=${encodeURIComponent(q)}`;
    if (name) enlaces.push({ sitio: 'Google (nombre)', url: g(`${name} rom download`) });
    if (md5) enlaces.push({ sitio: 'Google (MD5)', url: g(`${md5}`) });
    if (sha1) enlaces.push({ sitio: 'Google (SHA1)', url: g(`${sha1}`) });
    if (crc) enlaces.push({ sitio: 'Google (CRC)', url: g(`${crc}`) });

    // Render
    enlaces.forEach((e) => {
      const li = document.createElement('li');
      const a = document.createElement('a');
      a.href = e.url;
      a.target = '_blank';
      a.rel = 'noopener noreferrer';
      a.textContent = `${e.sitio}`;
      li.appendChild(a);
      linksList.appendChild(li);
    });

    if (enlaces.length) {
      results.hidden = false;
      btnOpenAll.disabled = false;
    } else {
      errorsEl.textContent = SE_NO_LINKS;
    }
  }

  function abrirTodas() {
    const anchors = linksList.querySelectorAll('a[href]');
    anchors.forEach((a) => {
      try { window.open(a.href, '_blank', 'noopener'); } catch (_) { /* noop */ }
    });
  }

  function parseAjax(text) {
    if (window.AppUtils && typeof window.AppUtils.parseAjaxJson === 'function') {
      return window.AppUtils.parseAjaxJson(text);
    }
    try { return JSON.parse(text); } catch(_) { return { ok:false, message: SE_INVALID_RESP }; }
  }

  function comprobarArchive() {
    // Throttle/cooldown en UI
    const ahora = Date.now();
    if (enCurso) {
      if (archiveStatus) archiveStatus.textContent = SE_ALREADY_RUN;
      return;
    }
    if (ahora < cooldownHasta) {
      const faltan = Math.ceil((cooldownHasta - ahora) / 1000);
      if (archiveStatus) archiveStatus.textContent = (SE_WAIT_SECONDS || '').replace('{s}', String(faltan));
      return;
    }
    const name = normalizarTexto(nameInput.value);
    const md5 = normalizarTexto(md5Input.value);
    const sha1 = normalizarTexto(sha1Input.value);
    const crc = normalizarTexto(crcInput.value);

    const errs = [];
    if (md5 && !HEX32.test(md5)) errs.push(SE_MD5_INVALID);
    if (sha1 && !HEX40.test(sha1)) errs.push(SE_SHA1_INVALID);
    if (crc && !HEX8.test(crc)) errs.push(SE_CRC_INVALID);
    if (!name && !md5 && !sha1 && !crc) errs.push(SE_REQUIRE_ONE);
    if (errs.length) { errorsEl.textContent = errs.join(' '); return; }

    if (archiveBox) { archiveBox.hidden = false; }
    if (archiveStatus) { archiveStatus.textContent = SE_CHECKING; }
    // Deshabilitar botón mientras se consulta
    enCurso = true;
    if (btnCheckArchive) {
      btnCheckArchive.disabled = true;
      btnCheckArchive.textContent = SE_CHECKING;
      btnCheckArchive.setAttribute('aria-busy', 'true');
    }

    const fd = new FormData();
    fd.set('action', 'search_archive');
    fd.set('ajax', '1');
    if (csrfInput) fd.set('csrf_token', csrfInput.value);
    if (name) fd.set('name', name);
    if (md5) fd.set('md5', md5);
    if (sha1) fd.set('sha1', sha1);
    if (crc) fd.set('crc', crc);

    fetch('./inc/acciones.php', { method: 'POST', body: fd, credentials: 'same-origin' })
      .then(r => r.text().then(t => ({ status: r.status, text: t, retry: r.headers.get('Retry-After') })))
      .then(resp => {
        const data = parseAjax(resp.text) || { ok:false, message: SE_NO_RESPONSE };
        if (resp.status === 429) {
          const retryNum = parseInt(resp.retry || '0', 10);
          const retryMs = isNaN(retryNum) ? COOLDOWN_MS : Math.max(1000, retryNum * 1000);
          cooldownHasta = Date.now() + retryMs;
          if (archiveStatus) archiveStatus.textContent = (data.message || SE_TOO_MANY);
        } else if (data.ok === false) {
          if (archiveStatus) archiveStatus.textContent = 'Error: ' + (data.message || SE_COULDNT);
        } else if (data.found) {
          if (archiveStatus) {
            const a = document.createElement('a');
            a.href = data.link;
            a.target = '_blank';
            a.rel = 'noopener noreferrer';
            a.textContent = data.title || data.identifier || TXT_VIEW;
            archiveStatus.textContent = TXT_FOUND;
            archiveStatus.appendChild(a);
          }
          // Cooldown tras éxito
          cooldownHasta = Date.now() + COOLDOWN_MS;
        } else {
          if (archiveStatus) archiveStatus.textContent = TXT_NOT_FOUND;
          cooldownHasta = Date.now() + COOLDOWN_MS;
        }
      })
      .catch(() => {
        if (archiveStatus) archiveStatus.textContent = SE_COULDNT_ORG;
        cooldownHasta = Date.now() + COOLDOWN_MS;
      })
      .finally(() => {
        enCurso = false;
        if (btnCheckArchive) {
          btnCheckArchive.disabled = false;
          btnCheckArchive.textContent = textoOriginalBtn;
          btnCheckArchive.removeAttribute('aria-busy');
        }
      });
  }

  btnBuild?.addEventListener('click', construirEnlaces);
  btnOpenAll?.addEventListener('click', abrirTodas);
  btnCheckArchive?.addEventListener('click', comprobarArchive);
})();
