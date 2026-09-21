# mushroom-ml

Projet de machine learning visant à prédire si un champignon est **comestible (E)**, **non comestible (I)** ou **vénéneux (P)** à partir de sa couleur, sa forme et la texture de sa surface. Les données sont récupérées par scraping sur [ultimate-mushroom.com](https://ultimate-mushroom.com/).

> ⚠️ Ce modèle est un exercice pédagogique. Il ne doit **jamais** servir à décider si un champignon est consommable : seule une expertise humaine (mycologue, pharmacien) peut le faire.

---

## Prérequis

- Python 3.14
- Bibliothèques : `requests`, `beautifulsoup4`, `pandas`, `scikit-learn`, `matplotlib`, `joblib`, `flask`

Installation :

```
pip install -r requirements.txt
```

---

## Utilisation

**1. Générer les données et entraîner les modèles**

```
python main.py
```

Ce script scrape le site, construit le jeu de données, entraîne les modèles et sauvegarde les résultats dans `data/`, `figures/` et `modele/`.

**2. Lancer le service web de prédiction**

```
python serveur.py
```

Puis ouvrir [http://localhost:8080/form](http://localhost:8080/form) dans un navigateur, remplir les caractéristiques d'un champignon, choisir un modèle et cliquer sur « Prédire ».

---

## Structure

```
mushroom-ml/
├── data/              champignons.csv (jeu de données scrapé)
├── figures/           arbre_decision.png (visualisation de l'arbre)
├── modele/            modèles entraînés (.joblib), scaler inclus dans le pipeline SVM
├── main.py            scraping + traitement + entraînement
├── serveur.py         service web Flask
├── couleurs.py        palette de couleurs (RGB_MAP) partagée par main.py et serveur.py
├── templates/
│   └── formulaire.html  interface de saisie (template Jinja2, généré depuis les colonnes du modèle)
└── README.md
```

---

## Parties

### Partie 1 — Récupération des données (scraping)

Le script parcourt la liste alphabétique des champignons et extrait, pour chaque fiche, quatre caractéristiques au format CSV.

| Colonne | Description | Valeurs |
|---------|-------------|---------|
| Edible  | Comestibilité | E (comestible), I (non comestible), P (vénéneux) |
| Color   | Couleur(s), séparées par `-` | ex. `White-Yellow` |
| Shape   | Forme(s), séparées par `-` | ex. `Convex-Bellshaped` |
| Surface | Texture(s), séparées par `-` | ex. `Smooth-Fibrous` |

Pour des raisons de performance, chaque fiche n'est requêtée qu'une seule fois (un seul objet BeautifulSoup réutilisé), et les requêtes sont parallélisées via `ThreadPoolExecutor`.

### Partie 2 — Manipulation des données (pandas)

- La colonne `Edible` est convertie en valeurs numériques (E→0, I→1, P→2).
- Les colonnes `Shape` et `Surface` sont transformées en variables indicatrices (une colonne 0/1 par valeur possible).
- La colonne `Color` est convertie en composantes RGB ; les couleurs multiples sont moyennées canal par canal.

### Partie 3 — Apprentissage (scikit-learn)

- Séparation stratifiée des données en jeu d'entraînement (75 %) et de test (25 %).
- Entraînement d'un **SVM** (avec `StandardScaler`) et d'un **arbre de décision**, tous deux avec `class_weight="balanced"` et hyperparamètres réglés par `GridSearchCV` (validation croisée à 5 plis, `scoring="recall_macro"` pour équilibrer le rappel entre les 3 classes plutôt que l'accuracy brute).
- Évaluation via l'accuracy, la matrice de confusion et un rapport précision/rappel par classe.
- Visualisation de l'arbre dans `figures/arbre_decision.png`.
- Sauvegarde des modèles dans `modele/`.

### Partie 4 — Service web (Flask)

Un serveur Flask sert un formulaire (`/form`) et traite la prédiction (`/rep`) en chargeant le modèle choisi. Les cases à cocher Forme/Surface du formulaire (`templates/formulaire.html`) sont générées dynamiquement à partir des colonnes du modèle entraîné (`feature_names_in_`), pour rester automatiquement synchronisées si les données sont régénérées avec un vocabulaire différent. La couleur est choisie via des cases à cocher (une par couleur connue de `couleurs.py`) plutôt qu'en RGB brut ; la moyenne RGB des couleurs cochées est calculée avec la même fonction (`moyenne_rgb`) que celle utilisée à l'entraînement, et aucune couleur cochée retombe sur la valeur "couleur inconnue" (-255) plutôt que sur le noir.