<?php
/**
 * generate_site.php
 * 
 * Génère un site statique HTML à partir du fichier videos.json
 * Usage : php generate_site.php
 * Produit : index.html
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

$sujets = $data['sujets'];

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
// Colonne gauche : Interface Utilisateur, Canevas, API, Connecteurs, RAG, Custom GPTs, Agents
// Centre : Moteurs d'inférences
// Colonne droite : LLM, Diffusion Model, ASR Model

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

// Trier selon l'ordre voulu
$leftOrder = array_flip($leftIds);
usort($leftItems, fn($a, $b) => $leftOrder[$a['id']] <=> $leftOrder[$b['id']]);
$rightOrder = array_flip($rightIds);
usort($rightItems, fn($a, $b) => $rightOrder[$a['id']] <=> $rightOrder[$b['id']]);

// Couleurs par rangée gauche (dégradé jaune → orange)
$leftColors = [
    'interface-utilisateur' => '#FFF8E1',
    'canevas'               => '#FFECB3',
    'api'                   => '#FFE082',
    'connecteurs'           => '#FFD54F',
    'rag'                   => '#FFCA28',
    'custom-gpts'           => '#F5A623',
    'agents'                => '#E09000',
];

// ── Génération des blocs HTML ──

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
$centerId    = htmlspecialchars($centerItem['id']);
$centerLabel = htmlspecialchars($centerItem['label']);

// ── Template HTML ──
$html = <<<HTML
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Composants de l'IA Générative</title>
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,300..800;1,9..40,300..800&family=Outfit:wght@400;600;700;800&display=swap" rel="stylesheet" />
  <link rel="stylesheet" href="style.css" />
</head>
<body>

  <header class="site-header">
    <h1>Les composants de l'IA Générative</h1>
    <p class="subtitle">Cliquez sur un composant pour explorer les vidéos associées</p>
  </header>

  <main class="main-layout">

    <!-- ═══ Schéma interactif ═══ -->
    <section class="schema-section">
      <div class="schema">
        <!-- Colonne gauche -->
        <div class="schema__col schema__col--left">
{$leftHtml}
        </div>

        <!-- Centre -->
        <button class="schema__center" data-sujet="{$centerId}">
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

  <footer class="site-footer">
    <p>© 2026 — Les composants de l'IA Générative</p>
  </footer>

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
              '<iframe src="https://www.youtube-nocookie.com/embed/' + v.youtube_id + '" ' +
                'title="' + escapeHtml(v.title) + '" frameborder="0" ' +
                'allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" ' +
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

    function escapeHtml(text) {
      const div = document.createElement('div');
      div.textContent = text;
      return div.innerHTML;
    }
  </script>

</body>
</html>
HTML;

// Écriture
$outputFile = __DIR__ . '/index.html';
file_put_contents($outputFile, $html);
echo "✅ Site généré avec succès : {$outputFile}\n";
