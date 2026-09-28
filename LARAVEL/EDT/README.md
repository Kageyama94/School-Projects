# EDT — Emploi du temps

Application Laravel de gestion d'emploi du temps pour un établissement scolaire : les enseignants réservent leurs propres créneaux de cours, les administrateurs gèrent les comptes et les matières, et les étudiants consultent l'emploi du temps de leur groupe.

## Fonctionnalités

- **Enseignants** : grille hebdomadaire (lundi–samedi, créneaux d'une heure de 8h à 18h avec pause de 12h à 14h, samedi matin uniquement) affichant à la fois leurs propres cours et la disponibilité du groupe sélectionné (on choisit d'abord une licence, puis un groupe de cette licence). Ajout d'un cours via une fenêtre modale (matière, groupe, salle — seules les salles libres à ce jour et à cette heure sont proposées, celles trop petites pour le groupe étant grisées), suppression de ses propres cours. Un récapitulatif « Ma semaine » regroupe ses cours de tous les groupes.
  - Règles appliquées à la création d'un cours : la matière doit être assignée à l'enseignant, ni l'enseignant, ni le groupe, ni la salle ne peuvent être réservés deux fois sur le même créneau, et la salle doit avoir une capacité suffisante pour le groupe.
- **Administrateurs** : tableau de bord avec les effectifs (groupes, matières, enseignants, étudiants, salles) et le nombre d'étudiants sans groupe (en évidence dès qu'il dépasse 0), et gestion complète, chacune sur sa page :
  - **Enseignants** et **étudiants** : liste paginée (10 par page) avec recherche par prénom ou nom (enseignants) ou par identifiant (étudiants), création, suppression, modification du profil (prénom, nom, identifiant de connexion), changement de groupe ou retrait du groupe (étudiants), réinitialisation du mot de passe. Un enseignant est créé sans matière ; ses matières s'assignent ensuite depuis « Modifier » (page dédiée à la liaison enseignant ↔ matières, un enseignant peut n'en avoir aucune). Supprimer un enseignant supprime aussi son compte et ses cours ; supprimer un étudiant supprime son compte. Un groupe ne peut pas être créé sans licence, mais un étudiant peut se retrouver sans groupe (retrait manuel) : le tableau de bord admin le signale.
  - **Remplacement d'un enseignant** : depuis « Modifier », l'admin confie tous les cours d'un enseignant (ou ceux d'une matière) à un remplaçant, à condition qu'il enseigne la matière et soit libre sur chaque créneau. Une matière ne peut être retirée à un enseignant que s'il n'y a plus de cours (il faut d'abord les confier à un autre).
  - **Emplois du temps** : chaque groupe, salle et enseignant a une page de consultation (« Emploi du temps » dans les listes) où l'admin peut retirer un cours (mal placé, enseignant absent…).
  - **Licences et groupes** (une seule page : chaque licence, filière comme Informatique ou Mathématiques, affiche ses groupes classés par niveau, avec « + Groupe » qui préremplit la licence) et **salles** (type, capacité) : création, modification et suppression tant qu'ils ne sont pas utilisés (une licence par des groupes, un groupe par des étudiants ou des cours, une salle par des cours). Chaque groupe appartient à une licence et à un niveau (L1, L2, L3, M1, M2), et son nom est unique dans une même licence et un même niveau. Un étudiant appartient à un groupe : sa licence et son niveau en découlent, ils ne peuvent donc pas être incohérents. **Matières** (page Enseignants, liste paginée à 10 par page indépendamment de celle des enseignants) : création et suppression tant qu'aucun cours ne les utilise.
- **Impression et agenda** : les étudiants et les enseignants peuvent imprimer leur emploi du temps (mise en page dédiée, sans menus) ou l'exporter en `.ics` (cours hebdomadaires récurrents, à importer dans Google Agenda, Outlook, Apple Calendrier…). Les pages d'emploi du temps de l'admin s'impriment aussi.
- **Étudiants** : consultation en lecture seule de l'emploi du temps de leur groupe.
- **Comptes** : à la création, l'identifiant de connexion (numérique, 8 chiffres au plus, à partir de `94020000`, une seule suite pour enseignants et étudiants) et le mot de passe initial (`nom_prenom`) sont générés automatiquement et affichés une seule fois. Le changement de mot de passe est obligatoire à la première connexion ; le nouveau mot de passe fait au moins 8 caractères avec lettres et chiffres, et doit différer de l'actuel et du mot de passe initial. Aucun formulaire (première connexion ou profil) ne demande le mot de passe actuel. Réinitialiser un mot de passe ferme les sessions ouvertes de la personne. Le profil est en lecture seule pour tous les rôles, admin compris (sauf le mot de passe) : le nom et l'identifiant se modifient depuis les pages admin, et personne ne peut supprimer son propre compte.

## Stack

- Laravel 13 / PHP 8.3+, base de données SQLite.
- Blade (composants `<x-...>`), [Tailwind CSS](https://tailwindcss.com) et [Alpine.js](https://alpinejs.dev), servis **en local** (`public/css/app.css`, `public/js/alpine.min.js`) : aucune dépendance à un CDN ni à Internet, et ni Node ni build à lancer pour utiliser l'application (voir « Modifier l'interface » pour régénérer le CSS).
- [Laravel Boost](https://laravel.com/docs/ai) est installé pour l'assistance IA (voir `AGENTS.md`).

## Installation

```bash
composer install
cp .env.example .env
php artisan key:generate

php artisan migrate --seed   # accepte de créer le fichier SQLite s'il n'existe pas encore

php artisan serve
```

L'application est alors disponible sur `http://localhost:8000`. En développement, `php artisan serve` suffit : `composer dev` lance seulement le serveur (plus de worker de file d'attente, la file étant synchrone) mais affiche une erreur `npx` si Node n'est pas installé, sans empêcher le serveur de démarrer.

## Comptes de démonstration

Créés par le seeder (`database/seeders/DatabaseSeeder.php`), identifiant / mot de passe :

| Rôle | Identifiant | Mot de passe |
| --- | --- | --- |
| Admin | `1` | `admin` |
| Enseignant (Prof Demo) | `2` | `prof` |
| Étudiant (Eleve Demo) | `3` | `eleve` |

Les comptes réels créés depuis l'interface admin reçoivent un identifiant généré automatiquement : le premier numéro libre à partir de `94020000` (une seule suite pour enseignants et étudiants, 8 chiffres au maximum ; voir `User::generateIdentifiant()`). Le numéro d'un compte supprimé peut donc être réattribué.

Les licences, matières et groupes créés par le seeder reproduisent la structure officielle des maquettes de la Licence Informatique et de la Licence Mathématiques de l'**UPEC** (Université Paris-Est Créteil) ; seuls les intitulés des matières et l'organisation par semestre sont repris, à titre d'exemple pour peupler des données de démonstration réalistes.

## Modifier l'interface

Le CSS (`public/css/app.css`) est généré à partir des classes Tailwind utilisées dans `resources/views/` et `app/`, puis versionné : rien à compiler pour lancer ou déployer l'application. Après avoir ajouté ou changé des classes Tailwind dans les vues, il faut le régénérer (le test `AssetsTest` échoue si des classes essentielles manquent) :

1. Télécharger la [CLI autonome de Tailwind 3.4](https://github.com/tailwindlabs/tailwindcss/releases/tag/v3.4.17) (`tailwindcss-windows-x64.exe`, ou la version de votre système) dans le dossier `tools/` (ignoré par git), sans Node ni npm.
2. Lancer :

```bash
./tools/tailwindcss.exe -c tailwind.config.js -i resources/css/app.css -o public/css/app.css --minify
```

Alpine.js est fourni tel quel dans `public/js/alpine.min.js` (version 3.17.4) et le script de la grille de l'enseignant est dans `public/js/teacher-scheduler.js`. Si la CLI Tailwind est présente dans `tools/`, `AssetsTest` régénère le CSS et vérifie qu'il est identique à celui versionné (le test est ignoré sinon).

## Tests

```bash
php artisan test
```

Suite de tests Feature couvrant les règles métier de réservation de cours (`LessonTest`), la gestion des comptes, matières, licences, groupes et salles par l'admin (`AdminTeacherTest`, `AdminStudentTest`, `AdminAccountTest`, `AdminTeacherLessonsTest`, `AdminSubjectTest`, `AdminLicenceTest`, `AdminGroupTest`, `AdminRoomTest`, `AdminTimetableTest`), les règles de mot de passe et le blocage des connexions après 5 échecs (`Auth/`), les commandes d'administration (`Console/`), l'export d'agenda (`CalendarTest`), les assets locaux (`AssetsTest`), les pages d'erreur en français (`ErrorPagesTest`), les titres de page (`PageTitlesTest`), la préparation du tableau de bord enseignant (`BuildTeacherScheduleTest`) et l'affichage des tableaux de bord (`DashboardTest`) et les composants Blade partagés (`ComponentsTest`).

## Qualité de code

```bash
vendor/bin/pint
```

## Mise en production

1. **Configuration** (`.env`) : `APP_ENV=production`, **`APP_DEBUG=false`** (le `.env.example` est réglé pour le développement local avec `APP_DEBUG=true`, ce qui exposerait les détails des erreurs), `APP_URL=https://…` et, l'application étant servie en **HTTPS**, `SESSION_SECURE_COOKIE=true`. Pour les journaux, préférer `LOG_STACK=daily` (un fichier par jour, avec rotation) et `LOG_LEVEL=warning` : avec `single` et `debug` (réglages de développement), un seul fichier grossit sans fin.
2. **Base de données** : `php artisan migrate --force`, **sans `--seed`**. Les comptes de démonstration (`1`, `2`, `3`) ont des mots de passe connus et le seeder crée des données fictives : il ne sert qu'au développement.
3. **Premier administrateur** : `php artisan app:create-admin` (nom, identifiant, mot de passe demandés). Si l'admin oublie son mot de passe : `php artisan app:reset-admin-password <identifiant>`. Les autres comptes sont ensuite créés depuis l'interface.
4. **Optimisation** : `php artisan config:cache && php artisan route:cache && php artisan view:cache`.
5. **Sauvegardes** : toutes les données sont dans le fichier `database/database.sqlite` — le copier régulièrement (application arrêtée ou avec `sqlite3 database/database.sqlite ".backup sauvegarde.sqlite"`).

### Migrations

Tant qu'aucune vraie donnée n'est saisie, les migrations existantes peuvent être modifiées puis rejouées avec `php artisan migrate:fresh --seed` (base de développement recréée). **Dès qu'une base contient de vraies données, ne plus modifier les migrations existantes** : ajouter à chaque évolution du schéma une nouvelle migration (`php artisan make:migration …`), qui s'applique avec `php artisan migrate` sans rien perdre.
