<?php
declare(strict_types=1);
require_once __DIR__ . '/../inc/i18n.php';
?>
<!-- Modal ayuda -->
<div class="modal" id="helpModal" aria-hidden="true" role="dialog" aria-modal="true" aria-labelledby="helpTitle">
  <div class="modal-content" role="document">
    <button type="button" class="close" aria-label="<?= htmlspecialchars(t('common.close')) ?>" onclick="closeHelpModal()">&times;</button>
    <h3 id="helpTitle"><?= htmlspecialchars(t('help.title')) ?></h3>
    <nav aria-label="<?= htmlspecialchars(t('help.nav.aria')) ?>">
      <ol>
        <li><a href="#h-requisitos"><?= htmlspecialchars(t('help.nav.requirements')) ?></a></li>
        <li><a href="#h-primeros-pasos"><?= htmlspecialchars(t('help.nav.getting_started')) ?></a></li>
        <li><a href="#h-listado"><?= htmlspecialchars(t('help.nav.listing')) ?></a></li>
        <li><a href="#h-editar"><?= htmlspecialchars(t('help.nav.edit')) ?></a></li>
        <li><a href="#h-eliminar"><?= htmlspecialchars(t('help.nav.delete')) ?></a></li>
        <li><a href="#h-masivo"><?= htmlspecialchars(t('help.nav.bulk')) ?></a></li>
        <li><a href="#h-busqueda"><?= htmlspecialchars(t('help.nav.search')) ?></a></li>
        <li><a href="#h-exportar"><?= htmlspecialchars(t('help.nav.export')) ?></a></li>
        <li><a href="#h-compactar"><?= htmlspecialchars(t('help.nav.compact')) ?></a></li>
        <li><a href="#h-restaurar"><?= htmlspecialchars(t('help.nav.restore')) ?></a></li>
        <li><a href="#h-idioma"><?= htmlspecialchars(t('help.nav.lang')) ?></a></li>
        <li><a href="#h-consejos"><?= htmlspecialchars(t('help.nav.tips')) ?></a></li>
        <li><a href="#h-soporte"><?= htmlspecialchars(t('help.nav.support')) ?></a></li>
      </ol>
    </nav>

    <section id="h-requisitos">
      <h4><?= htmlspecialchars(t('help.s1.title')) ?></h4>
      <ul>
        <li><?= t('help.s1.li1') ?></li>
        <li><?= t('help.s1.li2') ?></li>
        <li><?= t('help.s1.li3') ?></li>
      </ul>
      <p><a href="#helpTitle"><?= htmlspecialchars(t('help.back_to_index')) ?></a></p>
    </section>

    <section id="h-primeros-pasos">
      <h4><?= htmlspecialchars(t('help.s2.title')) ?></h4>
      <ol>
        <li><?= t('help.s2.ol1') ?></li>
        <li><?= t('help.s2.ol2') ?></li>
        <li><?= t('help.s2.ol3') ?></li>
      </ol>
      <p><small><?= t('help.s2.security') ?></small></p>
      <p><small><?= t('help.s2.tip') ?></small></p>
      <p><a href="#helpTitle"><?= htmlspecialchars(t('help.back_to_index')) ?></a></p>
    </section>

    <section id="h-listado">
      <h4><?= htmlspecialchars(t('help.s3.title')) ?></h4>
      <ul>
        <li><?= t('help.s3.li1') ?></li>
        <li><?= t('help.s3.li2') ?></li>
        <li><?= t('help.s3.li3') ?></li>
      </ul>
      <p><a href="#helpTitle"><?= htmlspecialchars(t('help.back_to_index')) ?></a></p>
    </section>

    <section id="h-editar">
      <h4><?= htmlspecialchars(t('help.s4.title')) ?></h4>
      <ol>
        <li><?= t('help.s4.ol1') ?></li>
        <li><?= t('help.s4.ol2') ?></li>
        <li><?= t('help.s4.ol3') ?></li>
        <li><?= t('help.s4.ol4') ?></li>
        <li><?= t('help.s4.ol5') ?></li>
      </ol>
      <p><small><?= t('help.s4.note') ?></small></p>
      <p><a href="#helpTitle"><?= htmlspecialchars(t('help.back_to_index')) ?></a></p>
    </section>

    <section id="h-eliminar">
      <h4><?= htmlspecialchars(t('help.s5.title')) ?></h4>
      <ol>
        <li><?= t('help.s5.ol1') ?></li>
        <li><?= t('help.s5.ol2') ?></li>
      </ol>
      <p><small><?= t('help.s5.note') ?></small></p>
      <p><a href="#helpTitle"><?= htmlspecialchars(t('help.back_to_index')) ?></a></p>
    </section>

    <section id="h-masivo">
      <h4><?= htmlspecialchars(t('help.s6.title')) ?></h4>
      <ol>
        <li><?= t('help.s6.ol1') ?></li>
        <li><?= t('help.s6.ol2a') ?>
          <ul>
            <li><?= t('help.s6.ol2b1') ?></li>
            <li><?= t('help.s6.ol2b2') ?></li>
          </ul>
        </li>
        <li><?= t('help.s6.ol3') ?></li>
        <li><?= t('help.s6.ol4') ?></li>
      </ol>
      <p><small><?= t('help.s6.note') ?></small></p>
      <p><a href="#helpTitle"><?= htmlspecialchars(t('help.back_to_index')) ?></a></p>
    </section>

    <section id="h-busqueda">
      <h4><?= htmlspecialchars(t('help.s7.title')) ?></h4>
      <ul>
        <li><?= t('help.s7.li1') ?>
          <ul>
            <li><?= t('help.s7.li2') ?></li>
            <li><?= t('help.s7.li3') ?></li>
            <li><?= t('help.s7.li4') ?></li>
          </ul>
        </li>
      </ul>
      <p><a href="#helpTitle"><?= htmlspecialchars(t('help.back_to_index')) ?></a></p>
    </section>

    <section id="h-exportar">
      <h4><?= htmlspecialchars(t('help.s8.title')) ?></h4>
      <ul>
        <li><?= t('help.s8.li1') ?></li>
        <li><?= t('help.s8.li2') ?></li>
      </ul>
      <p><a href="#helpTitle"><?= htmlspecialchars(t('help.back_to_index')) ?></a></p>
    </section>

    <section id="h-compactar">
      <h4><?= htmlspecialchars(t('help.s9.title')) ?></h4>
      <ul>
        <li><?= t('help.s9.li1') ?></li>
        <li><?= t('help.s9.li2') ?></li>
      </ul>
      <p><a href="#helpTitle"><?= htmlspecialchars(t('help.back_to_index')) ?></a></p>
    </section>

    <section id="h-restaurar">
      <h4><?= htmlspecialchars(t('help.s10.title')) ?></h4>
      <ul>
        <li><?= t('help.s10.li1') ?></li>
      </ul>
      <p><a href="#helpTitle"><?= htmlspecialchars(t('help.back_to_index')) ?></a></p>
    </section>

    <section id="h-idioma">
      <h4><?= htmlspecialchars(t('help.s11.title')) ?></h4>
      <ul>
        <li><?= t('help.s11.li1') ?></li>
        <li><?= t('help.s11.li2') ?></li>
      </ul>
      <p><a href="#helpTitle"><?= htmlspecialchars(t('help.back_to_index')) ?></a></p>
    </section>

    <section id="h-consejos">
      <h4><?= htmlspecialchars(t('help.s12.title')) ?></h4>
      <ul>
        <li><?= t('help.s12.li1') ?></li>
        <li><?= t('help.s12.li2') ?></li>
        <li><?= t('help.s12.li3') ?></li>
        <li><?= t('help.s12.kb_title') ?>
          <ul>
            <li><?= t('help.s12.kb_li1') ?></li>
            <li><?= t('help.s12.kb_li2') ?></li>
            <li><?= t('help.s12.kb_li3') ?></li>
          </ul>
        </li>
      </ul>
      <p><a href="#helpTitle"><?= htmlspecialchars(t('help.back_to_index')) ?></a></p>
    </section>

    <section id="h-soporte">
      <h4><?= htmlspecialchars(t('help.s13.title')) ?></h4>
      <ul>
        <li><?= t('help.s13.li1') ?></li>
        <li><?= t('help.s13.li2') ?></li>
      </ul>
      <p><a href="#helpTitle"><?= htmlspecialchars(t('help.back_to_index')) ?></a></p>
    </section>
  </div>
</div>
