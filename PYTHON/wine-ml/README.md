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
pip install -r requirements.txt
```

---

## Utilisation

```bash
python main.py scrape [n_workers]   # Partie 1 : scrape millesima.fr → data/vins.csv (défaut : 4 workers)
python main.py clean                # Partie 2 : nettoie vins.csv → data/vins_clean.csv
python main.py learn                # Partie 3 : entraîne les modèles et génère les figures
python main.py all [n_workers]      # Enchaîne les trois parties
```

> **Note :** le scraping prend environ 1h30 pour les ~3 400 fiches du catalogue avec les 4
> workers par défaut (le débit varie d'environ 1,5 fiche/s en début de run à ~0,6-0,7 fiche/s
> une fois la limitation côté serveur de Millesima atteinte). Une fois `vins.csv` obtenu,
> travailler exclusivement à partir de ce fichier local.

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

Modèles évalués :

| Modèle | Classe scikit-learn | Pré-traitement testé |
|---|---|---|
| Régression Linéaire (LR) | `LinearRegression` | brut, normalisation MinMax, standardisation |
| Arbre de Décision (AD) | `DecisionTreeRegressor` — profondeur optimisée par CV 5-fold | brut (invariant à l'échelle) |
| K plus proches voisins (KNN) | `KNeighborsRegressor` — k optimisé par CV 5-fold | brut, normalisation MinMax, standardisation |
| Forêt Aléatoire (RF) | `RandomForestRegressor` — 200 arbres | brut (invariant à l'échelle) |

Le meilleur r² de chaque modèle est comparé pour désigner le modèle gagnant, réutilisé pour les analyses PCA et top-5 ci-dessous.

Analyses complémentaires :
- Réduction de dimension PCA (5 composantes) appliquée au meilleur modèle
- Sélection des 5 attributs les plus corrélés au prix
- Matrice de corrélation exportée dans `figures/correlation.png`

---

## Résultats

Sur le catalogue complet (2 934 fiches, prix de 6,50 € à 36 600 €) :

| Modèle | r² |
|---|---|
| Régression Linéaire (LR) | 0.278 |
| Normalisation + LR | 0.278 |
| Standardisation + LR | 0.258 |
| Arbre de Décision (AD) | 0.281 |
| K plus proches voisins (KNN, normalisé, k=9) | 0.322 |
| **Forêt Aléatoire (RF)** | **0.330** |

La Forêt Aléatoire est le meilleur modèle, de justesse devant le KNN une fois k optimisé par CV
(k=9 contre k=4 testé initialement, +0.04 de r²). La réduire à une PCA (5 composantes, 98,85 % de
variance expliquée) ou aux 5 attributs les plus corrélés au prix (`Robert`, `Robinson`,
`App_Pauillac`, `Suckling`, `App_Haut-Médoc`) donne des r² proches (0.311 et 0.301) : l'essentiel
du signal vient des notes de critiques et de quelques appellations phares, pas de l'encodage
complet des appellations.

Les figures ci-dessous montrent les estimations vs prix réels pour la régression linéaire.
La dispersion autour de la diagonale illustre que le prix dépend de facteurs non capturés
par les seules notes de critiques et l'appellation (millésime, notoriété du château, etc.),
d'autant plus avec ce catalogue élargi qui inclut des flacons très haut de gamme (jusqu'à 36 600 €).
