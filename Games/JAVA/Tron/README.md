# 🏍️ Tron

Implémentation du jeu **Tron** en Java avec interface graphique Swing, suivant le pattern **MVC** (Modèle-Vue-Contrôleur).

---

## 📋 Description

Deux joueurs (ou une IA) se déplacent sur une grille et laissent une trace derrière eux. Le premier joueur à percuter un mur, un obstacle ou une trace perd la partie. La grille contient également des obstacles placés aléatoirement au départ.

---

## Aperçu

<img src="screenshots/gameplay.png" alt="Plateau" width="600">

---

## 🎮 Modes de jeu

| Mode | Description |
|------|-------------|
| **Humain vs Humain** | Deux joueurs sur le même clavier |
| **Humain vs IA** | Un joueur humain affronte une IA qui le pourchasse |
| **IA vs IA** | Deux IA s'affrontent de façon autonome |

---

## 🕹️ Contrôles

| Joueur | Haut | Bas | Gauche | Droite |
|--------|------|-----|--------|--------|
| Joueur 1 (Rouge) | `Z` | `S` | `Q` | `D` |
| Joueur 2 (Bleu) | `↑` | `↓` | `←` | `→` |

> Le retournement en sens inverse est interdit (on ne peut pas faire demi-tour).

---

## 🗂️ Structure du projet

```
├── app/
│   └── TronApp.java          # Point d'entrée, sélection du mode de jeu
├── controller/
│   └── TronController.java   # Gestion des entrées clavier et de la boucle de jeu
├── model/
│   └── TronModel.java        # Logique du jeu, grille, déplacements, IA
└── view/
    ├── TronView.java          # Fenêtre principale (JFrame)
    └── BoardPanel.java        # Rendu graphique de la grille (JPanel)
```

---

## ⚙️ Architecture MVC

- **Modèle (`TronModel`)** — Contient l'état de la grille (20×30 cellules), les positions et directions des joueurs, la gestion des collisions, des obstacles et la logique de l'IA.
- **Vue (`TronView` + `BoardPanel`)** — Affiche la grille, les traces et les joueurs. Chaque cellule mesure 25×25 px. Les têtes des joueurs sont représentées par des cercles blancs.
- **Contrôleur (`TronController`)** — Écoute les touches du clavier, pilote la boucle de jeu via un `javax.swing.Timer` (intervalle de 500 ms) et déclenche la fin de partie.

---

## 🤖 Intelligence Artificielle

L'IA dispose de deux comportements selon le mode :

- **Mode chasse** (`Humain vs IA`) — L'IA choisit à chaque tour le mouvement qui minimise la distance de Manhattan avec le joueur adverse.
- **Mode aléatoire** (`IA vs IA`) — Chaque IA choisit un mouvement valide au hasard parmi les cases libres.

Dans les deux cas, l'IA évite les collisions immédiates.

---

## 🗺️ Grille et affichage

| Élément | Couleur |
|---------|---------|
| Case vide | Gris |
| Trace Joueur 1 | Rouge |
| Trace Joueur 2 | Bleu |
| Obstacle | Noir |
| Tête des joueurs | Blanc (cercle) |

25 obstacles sont générés aléatoirement à l'initialisation, jamais à moins de 5 cases d'un joueur.

---

## 🚀 Lancement

### Prérequis

- Java 8 ou supérieur

### Compilation

```bash
javac -d out app/TronApp.java controller/TronController.java model/TronModel.java view/TronView.java view/BoardPanel.java
```

### Exécution

```bash
java -cp out app.TronApp
```

---

## 📌 Règles de fin de partie

- Si un joueur percute un mur, un obstacle ou une trace → l'**autre joueur gagne**.
- Si les deux joueurs entrent en collision simultanément → **Égalité**.
