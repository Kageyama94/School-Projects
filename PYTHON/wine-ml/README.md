# Estimation du prix d'un vin par apprentissage automatique

Étude de l'impact des notes de critiques et de l'appellation sur le prix d'un vin de Bordeaux, à partir des données du site [Millesima](https://www.millesima.fr/).

---

## Structure du projet

```
wine-ml/
├── main.py            # Script principal (scraping, nettoyage, apprentissage)
├── data/
│   ├── vins.csv       # Données brutes (généré par scrape, non versionné)
│   └── vins_clean.csv # Données nettoyées (généré par clean,  non versionné)
├── figures/
│   ├── correlation.png
│   ├── vis_LR.png
│   ├── vis_Norm_LR.png
│   └── vis_Std_LR.png
├── .gitignore
└── README.md
```

---

## Prérequis

- Python 3.10+
- Google Chrome installé
- ChromeDriver correspondant à votre version de Chrome ([téléchargement](https://chromedriver.chromium.org/downloads))

Installation des dépendances :

```bash
pip install selenium beautifulsoup4 lxml pandas scikit-learn matplotlib seaborn numpy
```

---

## Utilisation

```bash
python main.py scrape   # Partie 1 : scrape millesima.fr → data/vins.csv
python main.py clean    # Partie 2 : nettoie vins.csv → data/vins_clean.csv
python main.py learn    # Partie 3 : entraîne les modèles et génère les figures
python main.py all      # Enchaîne les trois parties
```

> **Note :** le scraping prend environ 2 h pour ~2 000 fiches en raison de la limitation
> côté serveur de Millesima (~0,4 fiche/s). Une fois `vins.csv` obtenu, travailler
> exclusivement à partir de ce fichier local.

---

## Parties

### Partie 1 — Récupération des données

Le scraping cible les vins de Bordeaux listés sur `millesima.fr/bordeaux.html`.
Pour chaque fiche produit, on extrait :

| Champ | Description |
|---|---|
| `Appellation` | Appellation géographique (ex. Saint-Julien) |
| `Robert` | Note Robert Parker (/100) |
| `Robinson` | Note Jancis Robinson (/20) |
| `Suckling` | Note James Suckling (/100) |
| `Prix` | Prix unitaire à la bouteille (€) |

**Détails techniques :**
- Selenium avec Chrome headless (`page_load_strategy = eager`, images désactivées)
- Extraction du JSON embarqué (`__NEXT_DATA__` / `initialReduxState`) pour les notes et le prix
- Redémarrage automatique du driver toutes les 50 fiches pour éviter les crashs Chrome
- Architecture en deux phases : collecte des URLs de fiches, puis scraping en parallèle (`ThreadPoolExecutor`)

### Partie 2 — Nettoyage des données

- Suppression des lignes sans prix valide
- Nettoyage des caractères unicode non-ASCII dans la colonne `Prix`
- Complétion des notes manquantes par la moyenne de l'appellation (ou 0 si aucune note disponible)
- Encodage one-hot de la colonne `Appellation` (`App_Pauillac`, `App_Margaux`, etc.)

Le DataFrame final contient ~2 000–3 000 lignes entièrement numériques.

### Partie 3 — Apprentissage

Split 75 % entraînement / 25 % test (`random_state=49`).  
Les prix sont transformés en `ln(prix)` lorsque le rapport max/min dépasse 100.

Modèles évalués, avec et sans pré-traitement (normalisation MinMax ou standardisation) :

| Modèle | Classe scikit-learn |
|---|---|
| Régression Linéaire (LR) | `LinearRegression` |
| Arbre de Décision (AD) | `DecisionTreeRegressor` — profondeur optimisée par CV 5-fold |
| K plus proches voisins (KNN) | `KNeighborsRegressor` — k = 4 |
| Forêt Aléatoire (RF) | `RandomForestRegressor` — 200 arbres |

Analyses complémentaires :
- Réduction de dimension PCA (5 composantes) appliquée au meilleur modèle
- Sélection des 5 attributs les plus corrélés au prix
- Matrice de corrélation exportée dans `figures/correlation.png`

---

## Résultats

Les figures ci-dessous montrent les estimations vs prix réels pour la régression linéaire.

| Méthode | r² |
|---|---|
| LR | 0.357 |
| Normalisation + LR | 0.357 |
| Standardisation + LR | 0.357 |

Le r² obtenu (~0,36) est identique avec ou sans pré-traitement pour ce modèle linéaire.
La dispersion autour de la diagonale illustre que le prix dépend de facteurs non capturés
par les seules notes de critiques et l'appellation (millésime, notoriété du château, etc.).
