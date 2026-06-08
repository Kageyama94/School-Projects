# immo-ml

Prédiction du prix de biens immobiliers en Île-de-France à partir d'annonces de particuliers. Le projet scrape les annonces de [immo-entre-particuliers.com](https://www.immo-entre-particuliers.com), nettoie les données, puis entraîne et compare plusieurs modèles de machine learning.

## Fonctionnalités

- **Scraping** des annonces de vente d'Île-de-France (Maison / Appartement) avec récupération parallèle des fiches
- **Nettoyage** : suppression des doublons, gestion des valeurs manquantes, filtrage des valeurs aberrantes
- **Enrichissement géographique** : ajout des coordonnées (latitude / longitude) par jointure avec un référentiel des communes
- **Modélisation** : Régression linéaire, Arbre de décision et KNN, chacun testé brut, avec normalisation (MinMax) et avec standardisation (StandardScaler)
- **Analyse** : réduction de dimension par PCA et matrice de corrélation des attributs

## Structure du projet

```
immo-ml/
├── data/
│   ├── annonces.csv          # données scrapées (générées)
│   └── cities.csv            # référentiel des communes (source)
├── figures/
│   ├── predictions_knn.png   # prédictions vs valeurs réelles
│   └── matrice_correlation.png
├── main.py
├── .gitignore
└── README.md
```

## Installation

```bash
pip install requests beautifulsoup4 lxml pandas numpy scikit-learn matplotlib
```

## Utilisation

Le fichier `main.py` enchaîne deux étapes :

```python
if __name__ == "__main__":
    scrape("https://www.immo-entre-particuliers.com/annonces/france-ile-de-france/vente/ta-offer")
    main()
```

- `scrape(url)` récupère les annonces et les sauvegarde dans `data/annonces.csv`
- `main()` charge les données, entraîne les modèles et génère les figures dans `figures/`

Pour relancer uniquement l'analyse sans re-scraper, commenter l'appel à `scrape(...)`.

## Modèles évalués

| Modèle | Variantes testées |
|--------|-------------------|
| Régression linéaire | brut, normalisé, standardisé |
| Arbre de décision | recherche du meilleur `max_depth` |
| KNN | recherche du meilleur `n_neighbors` (standardisé) |

Le KNN étant sensible aux échelles, c'est la version standardisée qui sert de référence.

## Détails techniques

- Le scraping traite les pages séquentiellement (affichage ordonné de la progression) tout en récupérant les fiches d'une même page en parallèle via `ThreadPoolExecutor`.
- Les requêtes réseau bénéficient d'un mécanisme de retry avec backoff exponentiel.
- Le CSV est sauvegardé au fil de l'eau, ce qui permet de conserver les données même en cas d'interruption (`Ctrl+C`).
- Les noms de communes sont normalisés (suppression des accents, casse, variantes) avant la jointure géographique.

## Technologies

Python · Selenium-free scraping (requests + BeautifulSoup) · pandas · scikit-learn · matplotlib
