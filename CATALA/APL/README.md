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

Les barèmes par zone (articles 7, 8 et 16 1°) sont extraits dans des fonctions dédiées (`plafond_base_personne_seule`, `plafond_chambre_general`, `plafond_colocation_isole`, etc.) plutôt que répétés inline à chaque usage : chaque montant légal n'est écrit qu'une seule fois, et les règles se lisent comme des formules qui les combinent.

Les deux champs d'application d'entrée (`CalculMontantForfaitaireCharges`, `CalculPlafondLoyer`) portent une assertion `nombre_personnes_à_charge >= 0` en garde d'entrée.

## Cas de conflit (question 4)

`Test23` modélise une personne vivant **à la fois** en chambre et en colocation. Les exceptions de l'article 8 et de l'article 16 1° s'activent alors simultanément, sans priorité légale définie entre elles : Catala lève une **erreur de conflit**. C'est le comportement attendu — le texte réglementaire ne tranche pas ce cas. Ce scope ne doit donc **pas** être exécuté en intégration continue.

## Environnement de développement

- **OS** : Windows + Visual Studio Code
- **Catala** : compilateur Catala installé via OCaml / opam (`opam install catala`), qui installe aussi `clerk`, l'outil de build/test officiel

> **Windows : PATH manquant après `opam install`** — `opam env` (ou `opam env --shell=pwsh`) n'ajoute pas toujours tout ce qu'il faut à la session courante. Si `catala --version` renvoie `DLL_NOT_FOUND`, ou si `clerk` échoue avec `ninja: command not found`, lance une fois (installe `ninja` via `winget install Ninja-build.Ninja` avant si besoin) :
> ```powershell
> .\setup-path-windows.ps1
> ```
> Le script ([`setup-path-windows.ps1`](setup-path-windows.ps1)) ajoute au PATH utilisateur ce qu'il faut (bin opam, DLL runtime MinGW, ninja), sans rien coder en dur — il fonctionne pour n'importe quel compte Windows. Ferme et rouvre ensuite ton terminal / VS Code.

## Lancer les tests

Exécuter chaque scope de test avec `clerk run` (construit automatiquement la bibliothèque standard et les dépendances au premier lancement) :

```bash
clerk run -s Test11 APL.catala_fr
clerk run -s Test12 APL.catala_fr
clerk run -s Test21 APL.catala_fr
clerk run -s Test22 APL.catala_fr
```

> `Test23` n'est pas lancé (voir "Cas de conflit" ci-dessus).

## Intégration continue

Une pipeline **GitHub Actions** ([`.github/workflows/apl-catala.yml`](../../.github/workflows/apl-catala.yml) à la racine du dépôt) exécute automatiquement `Test11`, `Test12`, `Test21` et `Test22` à chaque push ou pull request touchant ce dossier (`Test23` exclu, même raison).

Le workflow installe OCaml via [`ocaml/setup-ocaml`](https://github.com/ocaml/setup-ocaml), puis `ninja` et `catala` (qui fournit aussi `clerk`), sur un runner Ubuntu standard — exactement la même chaîne d'outils qu'en local (voir "Environnement de développement" ci-dessus).

> Le workflow doit vivre dans `.github/workflows/` à la **racine du dépôt Git** (`School-Projects/`, pas `CATALA/APL/`) : GitHub Actions ignore tout `.github/workflows/` situé dans un sous-dossier.

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
