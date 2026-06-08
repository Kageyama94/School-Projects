# Estimation du prix d'un bien immobilier par apprentissage automatique

Étude de l'impact des caractéristiques d'un logement (surface, nombre de pièces, localisation, DPE…) sur son prix de vente en Île-de-France, à partir des données du site [Immo entre Particuliers](https://www.immo-entre-particuliers.com/).

---

## Structure du projet

```
immo-ml/
├── main.py                    # Script principal (scraping, nettoyage, apprentissage)
├── data/
│   ├── annonces.csv           # Données brutes (généré par scrape, non versionné)
│   └── cities.csv             # Référentiel des communes (latitude / longitude)
├── figures/
│   ├── predictions_knn.png
│   └── matrice_correlation.png
├── .gitignore
└── README.md
```

---

## Prérequis

- Python 3.10+

Installation des dépendances :

```bash
pip install requests beautifulsoup4 lxml pandas scikit-learn matplotlib numpy
```

---

## Utilisation

Le fichier `main.py` enchaîne deux étapes via le bloc `__main__` :

```python
if __name__ == "__main__":
    scrape("https://www.immo-entre-particuliers.com/annonces/france-ile-de-france/vente/ta-offer")
    main()
```

- `scrape(url)` récupère les annonces et les sauvegarde dans `data/annonces.csv`
- `main()` charge les données, entraîne les modèles et génère les figures dans `figures/`

Une fois `annonces.csv` obtenu, commenter l'appel à `scrape(...)` pour travailler exclusivement à partir du fichier local.

---

## Parties

### Partie 1 — Récupération des données

Le scraping cible les annonces de vente d'Île-de-France. Pour chaque fiche, on extrait :

| Champ | Description |
|---|---|
| `Ville` | Commune du bien |
| `Type` | Maison ou Appartement |
| `Surface` | Surface habitable (m²) |
| `NbrPieces` | Nombre de pièces |
| `NbrChambres` | Nombre de chambres |
| `NbrSdb` | Nombre de salles de bains |
| `DPE` | Diagnostic de performance énergétique |
| `Prix` | Prix de vente (€) |

**Détails techniques :**
- Requêtes via `requests` + parsing `BeautifulSoup` (le site est en HTML classique, pas besoin de Selenium)
- Pages de listing traitées séquentiellement pour un affichage ordonné de la progression
- Fiches d'une même page récupérées en parallèle (`ThreadPoolExecutor`)
- Mécanisme de retry avec backoff exponentiel sur les erreurs réseau
- Sauvegarde au fil de l'eau : les données sont conservées même en cas d'interruption (`Ctrl+C`)

### Partie 2 — Nettoyage des données

- Suppression des doublons
- Complétion des valeurs numériques manquantes par la moyenne de la colonne
- Filtrage des valeurs aberrantes (surface < 10 m², plus de 10 pièces)
- Encodage one-hot des colonnes `Type` et `DPE`
- Enrichissement géographique : jointure avec `cities.csv` pour ajouter latitude / longitude, après normalisation des noms de communes (accents, casse, variantes)

### Partie 3 — Apprentissage

Split 75 % entraînement / 25 % test (`random_state=49`).

Modèles évalués, avec et sans pré-traitement (normalisation MinMax ou standardisation) :

| Modèle | Classe scikit-learn |
|---|---|
| Régression Linéaire (LR) | `LinearRegression` |
| Arbre de Décision (AD) | `DecisionTreeRegressor` — profondeur optimisée |
| K plus proches voisins (KNN) | `KNeighborsRegressor` — k optimisé (standardisé) |

Le KNN étant sensible aux échelles, c'est la version standardisée qui sert de référence.

Analyses complémentaires :
- Réduction de dimension PCA (2 composantes) appliquée au KNN
- Sélection des 5 attributs les plus corrélés au prix
- Matrice de corrélation exportée dans `figures/matrice_correlation.png`

---

## Résultats

La figure `predictions_knn.png` montre les estimations vs prix réels pour le KNN standardisé.

La dispersion autour de la diagonale illustre que le prix dépend de facteurs non capturés par les seules caractéristiques disponibles (état du bien, étage, prestations, négociation, etc.). La localisation (latitude / longitude) et la surface ressortent comme les attributs les plus corrélés au prix.
