# AI Learning

Site web statique permettant d’organiser et de présenter des vidéos YouTube autour de l’apprentissage de l’intelligence artificielle, de l’IA générative, du vibe coding, des tests d’IA et de l’IA locale.

Le site est conçu comme un portail pédagogique simple : l’utilisateur navigue par thème, clique sur une catégorie, puis accède aux vidéos correspondantes intégrées directement dans la page.

## 🌐 Site en ligne

Le site est publié avec GitHub Pages :

https://agileanddevopstoolkit.github.io/ai-learning/

## 🎯 Objectif du projet

Ce dépôt sert à construire un mini-site de ressources vidéo autour de l’IA.

Il permet notamment de regrouper :

- des vidéos d’explication sur les composants de l’IA générative ;
- des vidéos sur le vibe coding ;
- des tests comparatifs d’IA ;
- des ressources sur l’IA locale ;
- des pages thématiques prêtes à être complétées.

Le site est principalement destiné aux personnes qui veulent découvrir progressivement l’IA à travers des vidéos pédagogiques.

## 🧭 Pages disponibles

Le site contient plusieurs sections accessibles via la navigation principale.

### Composants

Page d’accueil du site.

Elle présente un schéma interactif des composants de l’IA générative :

- LLM ;
- Diffusion Model ;
- ASR Model ;
- moteurs d’inférence ;
- interface utilisateur ;
- canevas ;
- API ;
- connecteurs ;
- RAG ;
- Custom GPTs ;
- agents.

Quand l’utilisateur clique sur un composant, la page affiche les vidéos YouTube associées.

### Vibe Coding

Cette page regroupe des vidéos de démonstration d’applications créées ou assistées par IA.

Exemples d’applications référencées :

- Roue des prénoms ;
- Quiz ;
- Extreme Quotation ;
- Burndown Chart.

### Tests d’IA

Cette page regroupe des vidéos de tests et de comparatifs d’IA.

Les thèmes couverts sont notamment :

- questions d’agilité ;
- génération d’images ;
- synthèse de documents ;
- génération de slides ;
- reconnaissance vocale.

### IA Locale

Cette page regroupe des vidéos consacrées à l’utilisation de modèles d’IA directement sur une machine locale.

Elle met en avant l’installation et l’usage d’outils d’IA sans dépendre systématiquement du cloud.

### Pages en construction

Certaines pages sont déjà présentes dans la navigation, mais leur contenu est encore prévu pour plus tard :

- Réflexions / Comprendre l’IA ;
- Speech to Text ;
- Use Cases.

Ces pages servent de placeholders et peuvent être complétées ensuite.

## 🗂️ Structure du dépôt

```text
.
├── index.html
├── vibe-coding.html
├── tests-ia.html
├── ia-locale.html
├── reflexions.html
├── speech-to-text.html
├── use-cases.html
├── style.css
├── videos.json
├── generate_site.php
└── images/
    ├── ...
    └── vibe-coding/
        ├── ...
