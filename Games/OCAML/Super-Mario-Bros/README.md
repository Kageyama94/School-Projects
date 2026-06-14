# Super Mario Bros (OCaml)

Un jeu de plateforme à défilement horizontal façon Super Mario Bros, rendu entièrement en **caractères ASCII** dans une fenêtre graphique. Écrit en OCaml avec la bibliothèque `Graphics`.

Le personnage traverse un niveau généré aléatoirement (sol troué, obstacles, pièces), doit éviter les trous et atteindre le château à l'extrémité pour gagner.

## Aperçu

- Personnage et décor dessinés en art ASCII (`{*}` / `/O\` / `/ \` pour le héros)
- Défilement horizontal avec caméra qui suit le joueur
- Niveau **différent à chaque partie** (trous, obstacles et pièces générés aléatoirement)
- Physique de mouvement avec accélération, inertie et gravité
- Tir de projectiles
- Score, écrans de victoire et de défaite

## Contrôles

| Touche | Action |
|--------|--------|
| `Q` | Aller à gauche |
| `D` | Aller à droite |
| `Z` | Sauter |
| `A` | Activer / désactiver le mode course (plus rapide, saut plus haut) |
| `F` | Tirer |
| `Entrée` | Rejouer (sur l'écran gagné/perdu) |
| `Échap` | Quitter |

## Règles

- Ramasser une pièce (`o`) rapporte **100 points**.
- Tomber dans un trou fait perdre la partie.
- Atteindre la porte du château termine le niveau par une victoire.
- Le mode course (touche `A`) augmente la vitesse et la hauteur de saut, mais rallonge la distance de freinage.

## Prérequis

- OCaml (testé avec la version 5.2.1)
- [opam](https://opam.ocaml.org/) (gestionnaire de paquets OCaml)
- dune (système de build)
- La bibliothèque `graphics`

Installation des dépendances :

```
opam install dune graphics
```

## Compilation et lancement

Depuis le dossier du projet :

```
dune build
dune exec ./mario.exe
```

## Structure du projet

```
.
├── mario.ml       # tout le code du jeu
├── dune           # configuration de build
├── dune-project   # déclaration du projet dune
└── README.md
```

Le fichier `mario.ml` est organisé en sections : configuration (réglages et physique), données ASCII, génération du niveau, état du jeu, logique (collisions et mise à jour), rendu, et boucle principale.

## Notes techniques

- Le niveau est une grille de caractères de 300 colonnes sur 18 lignes.
- La physique utilise des positions et vitesses flottantes, arrondies à la case pour les collisions.
- Le rendu utilise le double buffering (`auto_synchronize false` + `synchronize`) pour éviter le scintillement.
- La bibliothèque `Graphics` ne permettant pas de lire l'état clavier en temps réel, un système de minuteurs de persistance (`hold`) lisse les entrées pour un déplacement fluide.
