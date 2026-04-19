<?php
/**
 * generate_site.php
 * 
 * Génère un site statique HTML à partir du fichier videos.json
 * Usage : php generate_site.php
 * Produit : index.html + pages secondaires
 */

$jsonFile = __DIR__ . '/videos.json';

if (!file_exists($jsonFile)) {
    die("Erreur : le fichier videos.json est introuvable.\n");
}

$jsonContent = file_get_contents($jsonFile);
$data = json_decode($jsonContent, true);

if ($data === null) {
    die("Erreur : le fichier videos.json est invalide.\n" . json_last_error_msg() . "\n");
}

// Indexer les pages par id pour un accès rapide
$pagesData = [];
foreach ($data['pages'] as $p) {
    $pagesData[$p['id']] = $p;
}

$sujets = $pagesData['composants']['sujets'];
$vibeCodingData = $pagesData['vibe-coding'] ?? null;
$testsIaData = $pagesData['tests-ia'] ?? null;
$iaLocaleData = $pagesData['ia-locale'] ?? null;

// ── Définition des pages du site ──
$pages = [
    ['id' => 'composants',   'label' => 'Composants',                  'file' => 'index.html'],
    ['id' => 'vibe-coding',  'label' => 'Vibe Coding',                 'file' => 'vibe-coding.html'],
    ['id' => 'tests-ia',     'label' => "Tests d'IA",                  'file' => 'tests-ia.html'],
    ['id' => 'ia-locale',    'label' => 'IA Locale',                   'file' => 'ia-locale.html'],
    ['id' => 'reflexions',   'label' => "Réflexions / Comprendre l'IA",'file' => 'reflexions.html'],
    ['id' => 'speech-to-text','label' => 'Speech to Text',             'file' => 'speech-to-text.html'],
    ['id' => 'use-cases',    'label' => 'Use Cases',                   'file' => 'use-cases.html'],
];

// ── Fonctions utilitaires pour le template commun ──

function renderNavbar(array $pages, string $currentId): string {
    $items = '';
    foreach ($pages as $p) {
        $href    = htmlspecialchars($p['file']);
        $label   = htmlspecialchars($p['label']);
        $active  = ($p['id'] === $currentId) ? ' nav__link--active' : '';
        $aria    = ($p['id'] === $currentId) ? ' aria-current="page"' : '';
        $items  .= "      <a class=\"nav__link{$active}\" href=\"{$href}\"{$aria}>{$label}</a>\n";
    }
    return $items;
}

function renderPageHead(string $title): string {
    return <<<HTML
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>{$title}</title>
  <link rel="icon" href="images/favicon.ico" />
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,300..800;1,9..40,300..800&family=Outfit:wght@400;600;700;800&display=swap" rel="stylesheet" />
  <link rel="stylesheet" href="style.css" />
</head>
<body>
HTML;
}

function renderHeader(string $title, string $subtitle, array $pages, string $currentId): string {
    $nav = renderNavbar($pages, $currentId);
    return <<<HTML

  <header class="site-header">
    <h1>{$title}</h1>
    <p class="subtitle">{$subtitle}</p>
    <nav class="site-nav">
{$nav}    </nav>
  </header>
HTML;
}

function renderFooter(): string {
    return <<<HTML

  <footer class="site-footer">
    <p>© 2026 — Les composants de l'IA Générative</p>
  </footer>

</body>
</html>
HTML;
}

// ══════════════════════════════════════════════════════════
// PAGE : Composants (index.html)
// ══════════════════════════════════════════════════════════

// Construire le JS des playlists
$playlistsJs = "const playlists = " . json_encode(
    array_combine(
        array_column($sujets, 'id'),
        array_map(function ($s) {
            return [
                'label' => $s['label'],
                'videos' => $s['videos']
            ];
        }, $sujets)
    ),
    JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT
) . ";";

// ── Layout : reproduire fidèlement le schéma ──
$leftItems = [];
$centerItem = null;
$rightItems = [];

$leftIds    = ['interface-utilisateur', 'canevas', 'api', 'connecteurs', 'rag', 'custom-gpts', 'agents'];
$centerId   = 'moteurs-inference';
$rightIds   = ['llm', 'diffusion-model', 'asr-model'];

foreach ($sujets as $s) {
    if (in_array($s['id'], $leftIds)) {
        $leftItems[] = $s;
    } elseif ($s['id'] === $centerId) {
        $centerItem = $s;
    } elseif (in_array($s['id'], $rightIds)) {
        $rightItems[] = $s;
    }
}

$leftOrder = array_flip($leftIds);
usort($leftItems, fn($a, $b) => $leftOrder[$a['id']] <=> $leftOrder[$b['id']]);
$rightOrder = array_flip($rightIds);
usort($rightItems, fn($a, $b) => $rightOrder[$a['id']] <=> $rightOrder[$b['id']]);

$leftColors = [
    'interface-utilisateur' => '#FFF8E1',
    'canevas'               => '#FFECB3',
    'api'                   => '#FFE082',
    'connecteurs'           => '#FFD54F',
    'rag'                   => '#FFCA28',
    'custom-gpts'           => '#F5A623',
    'agents'                => '#E09000',
];

function renderLeftCell(array $item, string $bg): string {
    $id    = htmlspecialchars($item['id']);
    $label = htmlspecialchars($item['label']);
    $icon  = htmlspecialchars($item['icon']);
    return <<<HTML
      <button class="cell cell--left" data-sujet="{$id}" style="--cell-bg: {$bg}">
        <img src="images/{$icon}" alt="{$label}" class="cell__icon" />
        <span class="cell__label">{$label}</span>
      </button>
HTML;
}

function renderRightCell(array $item): string {
    $id    = htmlspecialchars($item['id']);
    $label = htmlspecialchars($item['label']);
    $icon  = htmlspecialchars($item['icon']);
    return <<<HTML
      <button class="cell cell--right" data-sujet="{$id}">
        <span class="cell__label">{$label}</span>
        <img src="images/{$icon}" alt="{$label}" class="cell__icon" />
      </button>
HTML;
}

$leftHtml = '';
foreach ($leftItems as $item) {
    $leftHtml .= renderLeftCell($item, $leftColors[$item['id']]) . "\n";
}

$rightHtml = '';
foreach ($rightItems as $item) {
    $rightHtml .= renderRightCell($item) . "\n";
}

$centerIcon  = htmlspecialchars($centerItem['icon']);
$centerIdSafe = htmlspecialchars($centerItem['id']);
$centerLabel = htmlspecialchars($centerItem['label']);

$pageHead   = renderPageHead('Composants de l\'IA Générative');
$pageHeader = renderHeader(
    'Les composants de l\'IA Générative',
    'Cliquez sur un composant pour explorer les vidéos associées',
    $pages,
    'composants'
);
$pageFooter = renderFooter();

$composantsHtml = <<<HTML
{$pageHead}
{$pageHeader}

  <main class="main-layout">

    <!-- ═══ Schéma interactif ═══ -->
    <section class="schema-section">
      <div class="schema">
        <!-- Colonne gauche -->
        <div class="schema__col schema__col--left">
{$leftHtml}
        </div>

        <!-- Centre -->
        <button class="schema__center" data-sujet="{$centerIdSafe}">
          <span class="cell__label">{$centerLabel}</span>
          <img src="images/{$centerIcon}" alt="{$centerLabel}" class="cell__icon cell__icon--center" />
        </button>

        <!-- Colonne droite -->
        <div class="schema__col schema__col--right">
{$rightHtml}
        </div>
      </div>
    </section>

    <!-- ═══ Zone playlist ═══ -->
    <section class="playlist-section" id="playlistSection">
      <div class="playlist-header" id="playlistHeader">
        <span class="playlist-header__icon">▶</span>
        <span>Sélectionnez un composant pour voir les vidéos</span>
      </div>
      <div class="playlist-container" id="playlistContainer">
        <!-- Les embeds YouTube seront injectés ici -->
      </div>
    </section>

  </main>

  <script>
    {$playlistsJs}

    const container   = document.getElementById('playlistContainer');
    const header      = document.getElementById('playlistHeader');
    const section     = document.getElementById('playlistSection');
    const allCells    = document.querySelectorAll('[data-sujet]');

    let activeSujet = null;

    allCells.forEach(cell => {
      cell.addEventListener('click', () => {
        const sujet = cell.dataset.sujet;

        // Toggle : si on reclique sur le même, on désélectionne
        if (activeSujet === sujet) {
          cell.classList.remove('cell--active');
          activeSujet = null;
          showEmptyState();
          return;
        }

        // Désélectionner l'ancien
        allCells.forEach(c => c.classList.remove('cell--active'));

        // Sélectionner le nouveau
        cell.classList.add('cell--active');
        activeSujet = sujet;

        const playlist = playlists[sujet];
        if (!playlist) return;

        // Header
        header.innerHTML = '<span class="playlist-header__icon">▶</span>' +
          '<span>' + playlist.label + ' — ' + playlist.videos.length + ' vidéo' +
          (playlist.videos.length > 1 ? 's' : '') + '</span>';

        // Vidéos
        if (playlist.videos.length === 0) {
          container.innerHTML = '<p class="playlist-empty">Aucune vidéo disponible pour ce sujet.</p>';
          return;
        }

        container.innerHTML = playlist.videos.map((v, i) => {
          return '<div class="video-card" style="animation-delay: ' + (i * 0.08) + 's">' +
            '<div class="video-card__embed">' +
              '<iframe src="https://www.youtube-nocookie.com/embed/' + extractYoutubeId(v.youtube_id) + '" ' +
                'title="' + escapeHtml(v.title) + '" frameborder="0" ' +
                'allow="autoplay; fullscreen" ' +
                'referrerpolicy="strict-origin-when-cross-origin" allowfullscreen></iframe>' +
            '</div>' +
            '<p class="video-card__title">' + escapeHtml(v.title) + '</p>' +
          '</div>';
        }).join('');

        // Scroll vers la playlist sur mobile
        if (window.innerWidth < 1100) {
          section.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
      });
    });

    function showEmptyState() {
      header.innerHTML = '<span class="playlist-header__icon">▶</span>' +
        '<span>Sélectionnez un composant pour voir les vidéos</span>';
      container.innerHTML = '';
    }

    function extractYoutubeId(input) {
      if (input.includes('https://youtu.be/')) {
        return input.split('https://youtu.be/')[1];
      }
      return input;
    }

    function escapeHtml(text) {
      const div = document.createElement('div');
      div.textContent = text;
      return div.innerHTML;
    }
  </script>
{$pageFooter}
HTML;

file_put_contents(__DIR__ . '/index.html', $composantsHtml);
echo "✅ Page générée : index.html (Composants)\n";

// ══════════════════════════════════════════════════════════
// PAGE : Vibe Coding (vibe-coding.html)
// ══════════════════════════════════════════════════════════

function extractYoutubeIdPHP(string $input): string {
    if (str_contains($input, 'youtu.be/')) {
        $parts = explode('youtu.be/', $input);
        return $parts[1];
    }
    if (str_contains($input, 'youtube.com/watch')) {
        parse_str(parse_url($input, PHP_URL_QUERY), $qs);
        return $qs['v'] ?? $input;
    }
    return $input;
}

if ($vibeCodingData) {
    $apps = $vibeCodingData['applications'];

    $appsHtml = '';
    foreach ($apps as $app) {
        $name = htmlspecialchars($app['name']);
        $icon = htmlspecialchars($app['icon']);
        $desc = htmlspecialchars($app['description']);
        $videoCount = count($app['videos']);

        $videosHtml = '';
        foreach ($app['videos'] as $v) {
            $ytId    = extractYoutubeIdPHP($v['youtube_id']);
            $vtitle  = htmlspecialchars($v['title']);
            $videosHtml .= <<<HTML
            <div class="vc-video">
              <div class="vc-video__embed">
                <iframe src="https://www.youtube-nocookie.com/embed/{$ytId}"
                  title="{$vtitle}" frameborder="0"
                  allow="autoplay; fullscreen"
                  referrerpolicy="strict-origin-when-cross-origin" allowfullscreen></iframe>
              </div>
              <p class="vc-video__title">{$vtitle}</p>
            </div>
HTML;
            $videosHtml .= "\n";
        }

        $appsHtml .= <<<HTML
      <article class="vc-app">
        <div class="vc-app__header">
          <img src="images/vibe-coding/{$icon}" alt="{$name}" class="vc-app__icon" />
          <div class="vc-app__info">
            <h2 class="vc-app__name">{$name}</h2>
            <p class="vc-app__desc">{$desc}</p>
          </div>
        </div>
        <div class="vc-app__videos">
{$videosHtml}
        </div>
      </article>
HTML;
        $appsHtml .= "\n";
    }

    $vcPageHead = renderPageHead('Vibe Coding — IA Générative');
    $vcPageHeader = renderHeader(
        'Vibe Coding',
        'Applications créées par IA — explorez les vidéos de chaque projet',
        $pages,
        'vibe-coding'
    );
    $vcPageFooter = renderFooter();

    $vibeCodingHtml = <<<HTML
{$vcPageHead}
{$vcPageHeader}

  <main class="main-layout main-layout--wide">
    <section class="vc-section">
{$appsHtml}
    </section>
  </main>
{$vcPageFooter}
HTML;

    file_put_contents(__DIR__ . '/vibe-coding.html', $vibeCodingHtml);
    echo "✅ Page générée : vibe-coding.html (Vibe Coding)\n";
}

// ══════════════════════════════════════════════════════════
// PAGE : Tests d'IA (tests-ia.html)
// ══════════════════════════════════════════════════════════

if ($testsIaData) {
    $themes = $testsIaData['themes'];

    $themesHtml = '';
    foreach ($themes as $theme) {
        $tName = htmlspecialchars($theme['name']);
        $tDesc = htmlspecialchars($theme['description']);

        $videosHtml = '';
        foreach ($theme['videos'] as $v) {
            $ytId   = extractYoutubeIdPHP($v['youtube_id']);
            $vtitle = htmlspecialchars($v['title']);
            $videosHtml .= <<<HTML
            <div class="vc-video">
              <div class="vc-video__embed">
                <iframe src="https://www.youtube-nocookie.com/embed/{$ytId}"
                  title="{$vtitle}" frameborder="0"
                  allow="autoplay; fullscreen"
                  referrerpolicy="strict-origin-when-cross-origin" allowfullscreen></iframe>
              </div>
              <p class="vc-video__title">{$vtitle}</p>
            </div>
HTML;
            $videosHtml .= "\n";
        }

        $themesHtml .= <<<HTML
      <article class="vc-app">
        <div class="vc-app__header vc-app__header--no-icon">
          <div class="vc-app__info">
            <h2 class="vc-app__name">{$tName}</h2>
            <p class="vc-app__desc">{$tDesc}</p>
          </div>
        </div>
        <div class="vc-app__videos">
{$videosHtml}
        </div>
      </article>
HTML;
        $themesHtml .= "\n";
    }

    $tiPageHead = renderPageHead('Tests d\'IA — IA Générative');
    $tiPageHeader = renderHeader(
        'Tests d\'IA',
        'Comparatifs et mises à l\'épreuve des intelligences artificielles',
        $pages,
        'tests-ia'
    );
    $tiPageFooter = renderFooter();

    $testsIaHtml = <<<HTML
{$tiPageHead}
{$tiPageHeader}

  <main class="main-layout main-layout--wide">
    <section class="vc-section">
{$themesHtml}
    </section>
  </main>
{$tiPageFooter}
HTML;

    file_put_contents(__DIR__ . '/tests-ia.html', $testsIaHtml);
    echo "✅ Page générée : tests-ia.html (Tests d'IA)\n";
}

// ══════════════════════════════════════════════════════════
// PAGE : IA Locale (ia-locale.html)
// ══════════════════════════════════════════════════════════

if ($iaLocaleData) {
    $ilDesc = htmlspecialchars($iaLocaleData['description']);

    $videosHtml = '';
    foreach ($iaLocaleData['videos'] as $v) {
        $ytId   = extractYoutubeIdPHP($v['youtube_id']);
        $vtitle = htmlspecialchars($v['title']);
        $videosHtml .= <<<HTML
            <div class="vc-video">
              <div class="vc-video__embed">
                <iframe src="https://www.youtube-nocookie.com/embed/{$ytId}"
                  title="{$vtitle}" frameborder="0"
                  allow="autoplay; fullscreen"
                  referrerpolicy="strict-origin-when-cross-origin" allowfullscreen></iframe>
              </div>
              <p class="vc-video__title">{$vtitle}</p>
            </div>
HTML;
        $videosHtml .= "\n";
    }

    $ilPageHead = renderPageHead('IA Locale — IA Générative');
    $ilPageHeader = renderHeader(
        'IA Locale',
        $ilDesc,
        $pages,
        'ia-locale'
    );
    $ilPageFooter = renderFooter();

    $iaLocaleHtml = <<<HTML
{$ilPageHead}
{$ilPageHeader}

  <main class="main-layout main-layout--wide">
    <section class="vc-section">
      <div class="vc-app__videos">
{$videosHtml}
      </div>
    </section>
  </main>
{$ilPageFooter}
HTML;

    file_put_contents(__DIR__ . '/ia-locale.html', $iaLocaleHtml);
    echo "✅ Page générée : ia-locale.html (IA Locale)\n";
}

// ══════════════════════════════════════════════════════════
// PAGES SECONDAIRES (placeholder)
// ══════════════════════════════════════════════════════════

foreach ($pages as $page) {
    if (in_array($page['id'], ['composants', 'vibe-coding', 'tests-ia', 'ia-locale'])) continue; // déjà générées

    $label     = htmlspecialchars($page['label']);
    $pageHead  = renderPageHead($label . ' — IA Générative');
    $pageHeader = renderHeader(
        $label,
        'Contenu à venir…',
        $pages,
        $page['id']
    );
    $pageFooter = renderFooter();

    $stubHtml = <<<HTML
{$pageHead}
{$pageHeader}

  <main class="main-layout">
    <section class="placeholder-section">
      <div class="placeholder-card">
        <h2>🚧 Page en construction</h2>
        <p>Le contenu de la section <strong>{$label}</strong> sera bientôt disponible.</p>
      </div>
    </section>
  </main>
{$pageFooter}
HTML;

    file_put_contents(__DIR__ . '/' . $page['file'], $stubHtml);
    echo "✅ Page générée : {$page['file']} ({$label})\n";
}