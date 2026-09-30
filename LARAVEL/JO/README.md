# Jeux Olympiques — Laravel

Site des Jeux Olympiques : calendrier des épreuves, tableau des médailles, billetterie et administration.

## Prérequis

- PHP 8.3 ou plus, avec l'extension `pdo_sqlite` (les autres extensions demandées par Laravel, comme `openssl`
  ou `fileinfo`, sont présentes dans une installation PHP standard)
- Composer

Pas de Node ni de Vite : le CSS est dans `public/css/app.css`. Le site fonctionne sans internet
(police Inter et drapeaux copiés dans `public/fonts` et `public/img/flags`).

## Installation

```bash
composer run setup   # dépendances, fichier .env et sa clé, puis base de démonstration recréée
php artisan serve
```

`composer run setup` **recrée** la base `database/database.sqlite` avec des données de démonstration neuves :
les épreuves sont placées autour du jour de l'installation, il y en a donc toujours à réserver. Les comptes
et billets créés entre-temps sont effacés. Pour seulement recaler les dates plus tard :
`php artisan migrate:fresh --seed`.

## Comptes de démonstration

| Rôle       | E-mail               | Mot de passe |
|------------|----------------------|--------------|
| Admin      | `admin@jo.test`      | `password`   |
| Spectateur | `spectateur@jo.test` | `password`   |

## Fonctionnalités

**Public**
- **Accueil** : compte à rebours vers la prochaine épreuve, podium des nations, derniers champions.
- **Épreuves** (`/epreuves`) : calendrier jour par jour, filtres par sport, date et statut.
- **Sports** (`/sports`) : fiches sport avec épreuves et athlètes engagés.
- **Médailles** (`/medailles`) : classement or → argent → bronze, avec ex æquo ; fiche par pays (`/pays/FRA`).
- **Sites** (`/sites`) : lieux de compétition et leurs épreuves.

**Spectateur**
- **Billetterie** : 6 places maximum par personne et par épreuve, contrôle des places restantes, annulation.
  Le prix payé est enregistré sur le billet : un changement de tarif ne modifie pas les billets déjà achetés.
- **Mes billets** : billets à venir, remboursés (annulés par le spectateur ou épreuve annulée) et passés.
  Un billet n'est jamais effacé : annulé, il reste en base avec son statut, pour que l'historique des ventes reste juste.
  Si un compte est supprimé, ses billets passés sont conservés (sans propriétaire) et ses billets à venir sont annulés.
- **Compte** : inscription, profil (`/profil`), changement et oubli du mot de passe, suppression du compte.
  Changer d'adresse e-mail demande le mot de passe ; changer de mot de passe déconnecte les autres appareils.
  En local, les e-mails ne partent pas : le lien de réinitialisation est écrit dans `storage/logs/laravel.log`.

**Administration** (`/admin`)
- **Tableau de bord** : recettes, billets vendus et remboursés, remplissage des épreuves à venir, résultats à saisir, meilleures ventes.
- Gestion des épreuves, athlètes, sports, sites, pays et **utilisateurs** (nommer un administrateur, supprimer un compte).
- **Annulation d'une épreuve** : définitive, les billets passent en « remboursés » côté spectateurs.
- **Résultats**, une fois l'épreuve commencée : athlètes (épreuve individuelle) ou pays (par équipes), dans l'ordre
  or → argent → bronze. Les sports de combat (judo, boxe) décernent **deux médailles de bronze**.
- Garde-fous : impossible de supprimer une épreuve avec des billets vendus ou un sport/site/pays/athlète encore utilisé,
  de descendre sous le nombre de billets vendus ou de dépasser la jauge du site, ni de changer le sport, la catégorie
  ou le type d'une épreuve qui a déjà des médailles (ni le sport, le genre ou le pays d'un athlète médaillé).
  Un administrateur ne peut ni changer son propre rôle ni supprimer son compte s'il est le seul administrateur.

**Robustesse**
- Pages d'erreur en français (404, 403, 419, 429, 500, maintenance…).
- Limite anti-abus : à la connexion, 5 **échecs** par minute pour une même adresse e-mail depuis une même IP (les
  connexions réussies ne comptent pas, un compte de démo peut donc être partagé par toute une classe) ; à l'inscription
  et pour le mot de passe oublié, 5 envois par minute pour une même adresse e-mail depuis une même IP. Plafond de 60
  par minute pour une adresse IP. Le message s'affiche sur le formulaire.
- Adresses e-mail sans distinction de majuscules : `Camille@JO.test` et `camille@jo.test` désignent le même compte.
- Sessions et cache en fichiers (`storage/`), pas de file d'attente : `database.sqlite` ne contient que les données
  du site et ne change qu'avec elles.

## Données

Le seeder génère des données **fictives** : les pays sont réels, les athlètes et résultats sont inventés.
Les épreuves sont placées de J-8 à J+10 par rapport à la date du seed, pour avoir à la fois des épreuves
terminées avec médailles et des épreuves à venir réservables. Relancez `php artisan migrate:fresh --seed`
pour recaler les dates. Les heures sont celles de Paris (`Europe/Paris`).

- `database/database.sqlite` : la base utilisée par le site.
- `database/jo.sql` : export SQL de la même base (structure + données), lisible et réimportable avec
  `sqlite3 database/database.sqlite < database/jo.sql`. Après un nouveau seed, régénérez-le avec
  `php artisan jo:export-sql`.

## Tests et style du code

```bash
php artisan test         # lance les tests
vendor/bin/pint          # met le code PHP en forme (style Laravel)
vendor/bin/pint --test   # vérifie la mise en forme sans rien modifier
```

En dehors de la production, Laravel est en mode strict : une requête N+1 ou un attribut ignoré par `$fillable`
lèvent une exception au lieu de passer inaperçus, ce qui fait échouer les tests.

## Organisation du code

- `app/Enums` : médailles (`Medal`), rôles (`Role`) et catégories/genres (`Gender`), avec leurs libellés.
- `app/Http/Middleware` : `IsAdmin` (espace d'administration) et `NormalizeEmail` (adresses e-mail en minuscules,
  sur toutes les requêtes).
- `app/Http/Requests` : une Form Request par formulaire d'administration (épreuve, résultats, athlète, sport, site, pays,
  rôle), avec les règles liées à la base (jauge, billets vendus, médaillés…). Toutes héritent d'`AdminRequest`.
- `app/Support/Format.php` : nombres, prix et pourcentages à la française, via les directives Blade `@number`, `@euros` et `@percent`.
- `resources/views/components` : `<x-admin-form>` (formulaire d'administration), `<x-field>` (libellé, champ et erreur)
  et `<x-error>`.
