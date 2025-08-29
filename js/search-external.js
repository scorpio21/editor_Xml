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
    if (md5 && !HEX32.test(md5)) errs.push('MD5 debe tener 32 caracteres hexadecimales.');
    if (sha1 && !HEX40.test(sha1)) errs.push('SHA1 debe tener 40 caracteres hexadecimales.');
    if (crc && !HEX8.test(crc)) errs.push('CRC debe tener 8 caracteres hexadecimales.');

    if (!name && !md5 && !sha1 && !crc) {
      errs.push('Introduce al menos un dato: Nombre, MD5, SHA1 o CRC.');
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
      errorsEl.textContent = 'No se generaron enlaces.';
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
    try { return JSON.parse(text); } catch(_) { return { ok:false, message:'Respuesta no válida.' }; }
  }

  function comprobarArchive() {
    // Throttle/cooldown en UI
    const ahora = Date.now();
    if (enCurso) {
      if (archiveStatus) archiveStatus.textContent = 'Ya hay una comprobación en curso…';
      return;
    }
    if (ahora < cooldownHasta) {
      const faltan = Math.ceil((cooldownHasta - ahora) / 1000);
      if (archiveStatus) archiveStatus.textContent = `Espera ${faltan}s para volver a intentar.`;
      return;
    }
    const name = normalizarTexto(nameInput.value);
    const md5 = normalizarTexto(md5Input.value);
    const sha1 = normalizarTexto(sha1Input.value);
    const crc = normalizarTexto(crcInput.value);

    const errs = [];
    if (md5 && !HEX32.test(md5)) errs.push('MD5 debe tener 32 caracteres hexadecimales.');
    if (sha1 && !HEX40.test(sha1)) errs.push('SHA1 debe tener 40 caracteres hexadecimales.');
    if (crc && !HEX8.test(crc)) errs.push('CRC debe tener 8 caracteres hexadecimales.');
    if (!name && !md5 && !sha1 && !crc) errs.push('Introduce al menos un dato: Nombre, MD5, SHA1 o CRC.');
    if (errs.length) { errorsEl.textContent = errs.join(' '); return; }

    if (archiveBox) { archiveBox.hidden = false; }
    if (archiveStatus) { archiveStatus.textContent = 'Comprobando…'; }
    // Deshabilitar botón mientras se consulta
    enCurso = true;
    if (btnCheckArchive) {
      btnCheckArchive.disabled = true;
      btnCheckArchive.textContent = 'Comprobando…';
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
        const data = parseAjax(resp.text) || { ok:false, message:'Sin respuesta' };
        if (resp.status === 429) {
          const retryNum = parseInt(resp.retry || '0', 10);
          const retryMs = isNaN(retryNum) ? COOLDOWN_MS : Math.max(1000, retryNum * 1000);
          cooldownHasta = Date.now() + retryMs;
          if (archiveStatus) archiveStatus.textContent = (data.message || 'Demasiadas solicitudes. Intenta más tarde.');
        } else if (data.ok === false) {
          if (archiveStatus) archiveStatus.textContent = 'Error: ' + (data.message || 'No se pudo consultar Archive.');
        } else if (data.found) {
          if (archiveStatus) {
            const a = document.createElement('a');
            a.href = data.link;
            a.target = '_blank';
            a.rel = 'noopener noreferrer';
            a.textContent = data.title || data.identifier || 'Ver en Archive';
            archiveStatus.textContent = 'Encontrado: ';
            archiveStatus.appendChild(a);
          }
          // Cooldown tras éxito
          cooldownHasta = Date.now() + COOLDOWN_MS;
        } else {
          if (archiveStatus) archiveStatus.textContent = 'No encontrado.';
          cooldownHasta = Date.now() + COOLDOWN_MS;
        }
      })
      .catch(() => {
        if (archiveStatus) archiveStatus.textContent = 'No se pudo consultar Archive.org.';
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
