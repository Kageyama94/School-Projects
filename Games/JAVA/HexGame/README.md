# Hex Game

Implémentation du jeu de société **Hex** en Java avec une interface graphique Swing.

## Règles du jeu

Le Hex se joue sur un plateau de 11×11 cases hexagonales entre deux joueurs :

- **Rouge** doit relier le bord **gauche** au bord **droit**
- **Bleu** doit relier le bord **haut** au bord **bas**

Les joueurs placent chacun leur tour un pion sur une case vide. Le premier à créer un chemin continu entre ses deux bords gagne.

![Plateau](screenshots/plateau.png)

## Lancer le jeu

```bash
javac HexGame.java
java HexGame
```

## Fonctionnalités

- Plateau 11×11 avec rendu hexagonal
- Détection automatique de la victoire (DFS)
- Option de rejouer en fin de partie
