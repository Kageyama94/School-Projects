# Calcul de l'APL en Catala

Implémentation en [Catala](https://catala-lang.org/) des règles de calcul de l'**Aide Personnalisée au Logement (APL)**, dans le cadre du TD du 03/06/2025 (UPEC, parcours Droit & Informatique).

Le projet encode en code exécutable une partie de l'**arrêté du 27 septembre 2019**, en s'appuyant sur les articles D. 823-16 et D. 823-18 du Code de la construction et de l'habitation (CCH).

## Contenu

Le fichier `APL.catala_fr` couvre deux calculs.

**Exercice 1 — Montant forfaitaire des charges** (articles 9 et 16 2°)

Calcule le montant forfaitaire de charges locatives, qui dépend de la composition du foyer et du fait que le bénéficiaire soit ou non en colocation.

**Exercice 2 — Plafond de loyer** (articles 7, 8 et 16 1°)

Calcule le plafond de loyer pris en compte dans l'APL, selon la zone géographique, la composition du foyer, et trois cas particuliers : logement-chambre (article 8), personnes âgées ou handicapées hébergées à titre onéreux, et colocation (article 16 1°).

## Structure du code

Chaque calcul est un champ d'application Catala :

| Champ d'application | Rôle |
|---|---|
| `CalculMontantForfaitaireCharges` | Montant forfaitaire des charges |
| `CalculPlafondLoyer` | Plafond de loyer |
| `Test11`, `Test12` | Tests de l'exercice 1 |
| `Test21`, `Test22` | Tests de l'exercice 2 |
| `Test23` | Cas de conflit volontaire (question 4) |

L'architecture repose sur le mécanisme **défaut / exception** de Catala. Pour le plafond de loyer, une règle de base unique étiquetée `base` (article 7) couvre l'ensemble des compositions de foyer, et les cas particuliers des articles 8 et 16 sont déclarés comme `exception base`. Cette structure garantit qu'une seule règle s'applique pour chaque situation, sans conflit involontaire.

## Cas de conflit (question 4)

`Test23` modélise une personne vivant **à la fois** en chambre et en colocation. Les exceptions de l'article 8 et de l'article 16 1° s'activent alors simultanément, sans priorité légale définie entre elles : Catala lève une **erreur de conflit**. C'est le comportement attendu — le texte réglementaire ne tranche pas ce cas. Ce scope ne doit donc **pas** être exécuté en intégration continue.

## Environnement de développement

- **OS** : Windows + Visual Studio Code
- **Catala** : compilateur Catala installé via OCaml / opam

## Lancer les tests

Exécuter chaque scope de test :

```bash
catala test-scope Test11 APL.catala_fr
catala test-scope Test12 APL.catala_fr
catala test-scope Test21 APL.catala_fr
catala test-scope Test22 APL.catala_fr
```

> `Test23` n'est pas lancé : il produit volontairement une erreur de conflit (voir ci-dessus).

## Intégration continue

Une pipeline **GitHub Actions** exécute automatiquement `Test11`, `Test12`, `Test21` et `Test22` à chaque push. `Test23` n'est pas exécuté en CI car il produit volontairement une erreur de conflit.

## Résultats attendus

| Test | Situation | Résultat |
|---|---|---|
| Test11 | Personne seule, 0 enfant, colocation | 29,03 € |
| Test12 | Couple, 3 enfants, hors colocation | 97,59 € |
| Test21 | Personne seule, 0 enfant, Zone I, colocation | 239,48 € |
| Test22 | Couple, 3 enfants, Zone II, cas standard | 494,86 € |
| Test23 | Chambre **et** colocation | erreur de conflit (attendue) |

## Références juridiques

- Arrêté du 27 septembre 2019, articles 7, 8, 9 et 16
- Code de la construction et de l'habitation, articles D. 823-16 et D. 823-18
