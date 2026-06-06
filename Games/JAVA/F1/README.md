# 🏎️ F1 — Jeu de Course en Java

Un jeu de course développé en Java avec une architecture **MVC**, une interface graphique **Swing**, et un système d'événements observateur.

---

## Aperçu

F1 simule une course entre trois voitures sur un circuit paramétrable. Chaque voiture possède une politique de déplacement configurable (son, conduite ivre, hybride). Le jeu tourne par *ticks* réguliers et offre une interface graphique complète avec tableau de bord, classement en direct et historique rejouable.

---

## Architecture

Le projet suit le pattern **MVC** strict, organisé en packages :

```
F1/
├── app/
│   └── Main.java               # Point d'entrée
├── assets
│   └── vrooom.wav              # Source son
├── controller/
│   └── GameController.java     # Contrôleur principal
├── model/
│   ├── GameConfig.java         # Constantes de configuration
│   ├── observer/               # Système événementiel
│   │   ├── ObservableModel.java
│   │   ├── AbstractObservableModel.java
│   │   ├── ModelListener.java
│   │   └── ModelEvent.java
│   ├── car/                    # Modèle voiture
│   │   ├── Car.java
│   │   └── policy/             # Stratégies de déplacement
│   │       ├── Policy.java
│   │       ├── Movement.java
│   │       ├── SoundBooster.java
│   │       ├── DrunkDriver.java
│   │       ├── HybridSystem.java
│   │       ├── EnergySource.java
│   │       ├── Sound.java
│   │       └── CarOption.java
│   ├── game/                   # Modèle de jeu
│   │   ├── Game.java
│   │   ├── Player.java
│   │   ├── Ticker.java
│   │   ├── TickProcessor.java
│   │   └── History.java
│   └── track/                  # Modèle du circuit
│       ├── Track.java
│       └── TrackFactory.java
└── view/                       # Interface graphique Swing
    ├── Frame.java
    ├── GamePanel.java
    ├── Dashboard.java
    ├── CarsPanel.java
    ├── ControlPanel.java
    ├── LeaderboardPanel.java
    ├── SetupPanel.java
    ├── SwingTicker.java
    ├── SoundPlayer.java
    └── ViewConfig.java
```

---

## Fonctionnalités

### 🎮 Gameplay
- Course sur un circuit avec virages numérotés et limites de vitesse
- 3 joueurs simultanés (Rouge, Orange, Bleu)
- Déplacement aléatoire par dés selon l'état de la voiture
- **3 tours** à boucler pour gagner
- Gestion des crashs : dépasser la limite d'un virage endommage la voiture

### 🚗 États des voitures
| État | Description |
|------|-------------|
| `STOPPED` | Voiture à l'arrêt |
| `LOW` | Vitesse réduite (dés : 1–3) |
| `NORMAL` | Vitesse normale (dés : 1–6) |
| `BOOST` | Vitesse boostée (dés : 5–10) |
| `DAMAGED` | Immobilisée pendant `DAMAGE_DURATION` ticks |

### ⚙️ Options de voiture (Policies)
| Option | Comportement |
|--------|-------------|
| **Sound Booster** | Joue un son à chaque déplacement |
| **Drunk Driver** | Alterne avant/arrière à chaque tick |
| **Hybrid System** | Dispose d'une batterie rechargeable au freinage |

### ⏱️ Système de ticks
Chaque *tick* (100 ms par défaut) :
1. Consomme le carburant selon l'état
2. Lance les dés pour chaque voiture
3. Avance la voiture sur le circuit
4. Vérifie les crashs, les tours bouclés, et les conditions de fin
5. Enregistre un snapshot dans l'historique

### 🕹️ Contrôles joueur
- **Accélérer / Ralentir** : monte ou descend d'un cran dans les états
- **Pause / Reprendre** : contrôle du flux de jeu
- **Reculer / Avancer** : navigation dans l'historique (en mode FINISHED)

### 📜 Historique
À la fin de la partie, l'historique complet des snapshots est navigable tick par tick (avant/arrière).

---

## Modèle événementiel

Le système observateur est basé sur `AbstractObservableModel` et `ModelEvent` :

| Événement | Émetteur | Signification |
|-----------|----------|---------------|
| `TICK` | `Game` | Un tick s'est écoulé |
| `STATE_CHANGED` | `Car`, `Game` | Changement d'état |
| `FUEL_CHANGED` | `Car` | Carburant/batterie modifié |
| `POSITION_CHANGED` | `Car` | Voiture déplacée |
| `LAP_CHANGED` | `Car` | Tour bouclé |
| `FINISHED` | `Game` | Fin de partie |

---

## Configuration

Tous les paramètres sont centralisés dans `GameConfig.java` :

```java
GameConfig.Car.INITIAL_FUEL        // 60
GameConfig.Car.DAMAGE_DURATION     // 5 ticks
GameConfig.Battery.MAX             // 100%
GameConfig.Battery.CONSUMPTION     // -10% par tick
GameConfig.Battery.RECHARGE        // +5% au freinage
GameConfig.Game.LAPS_TO_WIN        // 3 tours
GameConfig.Game.TICK_MILLIS        // 100 ms
GameConfig.Track.ROWS / COLS       // 10 × 20
```

---

## Lancement

### Prérequis
- Java 17+
- VS Code avec l'extension **Java Extension Pack** (ou tout IDE Java)

### Compilation et exécution
```bash
# Depuis la racine du projet
javac -d out -sourcepath src src/F1/F1App.java
java -cp out F1.F1App
```

### Note VS Code (workspace multi-projets)
Si le projet est dans un dossier partagé avec d'autres projets non-Java, ajouter dans `.vscode/settings.json` :
```json
{
  "java.import.exclusions": [
    "**/node_modules/**",
    "**/.git/**",
    "**/NomDuProjetPHP/**",
    "**/NomDuProjetPython/**"
  ]
}
```
Cela évite les conflits d'indexation du Java Language Server.

---

## Circuit

Le circuit par défaut est généré par `TrackFactory.circuit()` sur une grille 10×20 :

```
                                                         ②   →   →   ⑤
 ③   →   →   →   →   →   →   →   →   →   →   ②           ↑           ↓
 ↑                                           ↓           ↑           ↓
 ↑                                           ↓           ↑           ↓
 ↑                                           ②   →   →   ②           ↓
 ↑                                                                   ↓
 ↑                                                                   ↓
 ⑤   ←   ←   ←   ←   ←   ←   ←   ←   ←   ←   ←   ←   ←   ←   ←   D   A
```

Les chiffres indiquent les virages avec leur limite de pas autorisés.

---

## Patterns de conception utilisés

- **MVC** — séparation stricte Modèle / Vue / Contrôleur
- **Observer** — `AbstractObservableModel` + `ModelListener`
- **Strategy** — interface `Policy` avec `Movement`, `SoundBooster`, `DrunkDriver`, `HybridSystem`
- **Factory** — `TrackFactory`, `Policy.build()`, `SoundPlayer.Factory`
- **Template Method** — `AbstractObservableModel.fire()`
- **Memento** — `History` avec snapshots par tick
