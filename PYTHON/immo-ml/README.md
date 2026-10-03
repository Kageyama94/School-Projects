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
│   ├── predictions_knn.png     # Généré par main(), non versionné
│   └── matrice_correlation.png # Généré par main(), non versionné
├── .gitignore
└── README.md
```

---

## Prérequis

- Python 3.11+ (testé avec 3.14)

Installation des dépendances :

```bash
pip install -r requirements.txt
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
- Filtrage des valeurs aberrantes : surface < 10 m², plus de 10 pièces, ou prix au m² hors de la fourchette plausible en Île-de-France (1 000 à 20 000 €/m², au moins 5 000 €/m² à Paris), qui écarte viagers, parkings et erreurs de saisie
- Encodage one-hot des colonnes `Type` et `DPE` (`drop_first=True`)
- Enrichissement géographique : jointure avec `cities.csv` pour ajouter latitude / longitude, après normalisation des noms de communes (accents, casse, variantes, communes renommées comme Herblay → Herblay-sur-Seine). Le référentiel n'ayant ni coordonnées « centre » pour Paris ni lignes par arrondissement, toutes les annonces parisiennes reçoivent les coordonnées de la mairie de Paris

### Partie 3 — Apprentissage

Split 75 % entraînement / 25 % test (`random_state=49`).

Complétion des valeurs numériques manquantes par la moyenne de la colonne, calculée sur le train uniquement puis appliquée au train et au test (évite toute fuite de données).

Modèles évalués, avec et sans pré-traitement (normalisation MinMax ou standardisation) :

| Modèle | Classe scikit-learn |
|---|---|
| Régression Linéaire (LR) | `LinearRegression` |
| Arbre de Décision (AD) | `DecisionTreeRegressor` — profondeur optimisée par validation croisée (5-fold) sur le train |
| K plus proches voisins (KNN) | `KNeighborsRegressor` — k optimisé par validation croisée (5-fold) sur le train (standardisé) |

Le KNN étant sensible aux échelles, c'est la version standardisée qui sert de référence.

Analyses complémentaires :
- Réduction de dimension PCA (2 composantes) appliquée au KNN
- Sélection des 5 attributs les plus corrélés au prix
- Matrice de corrélation exportée dans `figures/matrice_correlation.png`

---

## Résultats

**Données.** 578 annonces scrapées (367 appartements, 211 maisons, 269 communes, prix médian 240 000 €) :

| Étape | Annonces restantes |
|---|:-:|
| Scraping | 578 |
| Suppression des doublons | 560 |
| Filtrage des valeurs aberrantes (dont 50 des 106 annonces parisiennes, 45 d'entre elles sous 5 000 €/m²) | 490 |
| Rattachement géographique | **490** |

Le DPE est absent de deux annonces sur trois (387 sur 578).

**Modèles (r² sur le jeu de test).** Hyperparamètres choisis par validation croisée : `max_depth=6` pour l'arbre, `k=4` pour le KNN.

| Modèle | Brut | Normalisation | Standardisation |
|---|:-:|:-:|:-:|
| Régression Linéaire (LR) | 0.120 | 0.120 | 0.120 |
| **Arbre de Décision (AD)** | **0.261** | **0.261** | **0.261** |
| KNN | -0.015 | 0.063 | 0.150 |

**Analyses complémentaires.**
- PCA à 2 composantes : le r² du KNN standardisé passe de 0.150 à -0.025.
- Attributs les plus corrélés au prix : `Surface` (0.48), `NbrPieces` (0.42), `NbrChambres` (0.35), `DPE_D` (0.24), `DPE_Vierge` (0.17). Un KNN standardisé restreint à ces 5 attributs obtient un r² de 0.125.

L'arbre de décision est le meilleur modèle sur ce découpage : en coupant sur la latitude et la longitude, il isole les annonces parisiennes, toutes placées au même point et nettement plus chères, ce que la régression linéaire ne sait pas faire. Ces scores restent toutefois fragiles. Avec moins de 500 annonces, ils varient fortement d'un découpage train/test à l'autre : sur 50 découpages aléatoires, le r² médian est de 0.19 pour la LR, 0.14 pour l'AD et 0.20 pour le KNN standardisé, sans modèle nettement supérieur aux autres.

Les modèles n'expliquent donc qu'environ 20 % de la variance du prix. La figure `predictions_knn.png` (estimations vs prix réels pour le KNN standardisé) montre une forte dispersion autour de la diagonale : le prix dépend de facteurs absents des annonces (état du bien, étage, prestations, quartier, négociation). Le faible volume de données, le DPE souvent manquant et une localisation réduite aux coordonnées du centre de la commune (un seul point pour tout Paris) limitent aussi les performances.
