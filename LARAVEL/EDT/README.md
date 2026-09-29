# EDT — Emploi du temps

Application Laravel de gestion d'emploi du temps pour un établissement scolaire : les enseignants réservent leurs propres créneaux de cours, les administrateurs gèrent les comptes et les matières, et les étudiants consultent l'emploi du temps de leur groupe.

## Fonctionnalités

- **Enseignants** : grille hebdomadaire (lundi–samedi, créneaux d'une heure de 8h à 18h avec pause de 12h à 14h, samedi matin uniquement) affichant à la fois leurs propres cours et la disponibilité du groupe sélectionné (on choisit d'abord une licence, puis un groupe de cette licence ; seules les licences assignées à l'enseignant sont proposées). Ajout d'un cours via une fenêtre modale (matière, groupe, salle — seules les salles libres à ce jour et à cette heure sont proposées), suppression de ses propres cours ; la page garde sa position de défilement après un ajout ou un retrait. Un récapitulatif « Ma semaine » regroupe ses cours de tous les groupes.
  - Règles appliquées à la création d'un cours : le cours est toujours créé au nom de l'enseignant connecté, la matière doit lui être assignée et le groupe doit appartenir à l'une de ses licences ; ni l'enseignant, ni le groupe, ni la salle ne peuvent être réservés deux fois sur le même créneau.
- **Administrateurs** : tableau de bord avec les effectifs (groupes, matières, enseignants, étudiants, salles) et le nombre d'étudiants sans groupe (en évidence dès qu'il dépasse 0), et gestion complète, chacune sur sa page :
  - **Enseignants** et **étudiants** : liste paginée (10 par page) avec recherche par prénom ou nom (enseignants), par nom ou par début d'identifiant (étudiants), création, suppression, modification du profil (prénom et nom ; l'identifiant de connexion n'est ni affiché ni modifiable dans le formulaire), changement de groupe ou retrait du groupe (étudiants), réinitialisation du mot de passe. Les **matières** et les **licences** d'un enseignant se cochent dès sa création ou plus tard depuis « Modifier » (il peut n'en avoir aucune) : il n'enseigne que ses matières, et seulement dans les groupes de ses licences. Supprimer un enseignant supprime aussi son compte et ses cours ; supprimer un étudiant supprime son compte. Un groupe ne peut pas être créé sans licence, mais un étudiant peut se retrouver sans groupe (retrait manuel) : le tableau de bord admin le signale.
  - **Remplacement d'un enseignant** : depuis « Modifier », l'admin confie tous les cours d'un enseignant (ou ceux d'une matière) à un remplaçant. Seuls sont proposés les enseignants qui ont la matière et toutes les licences concernées (les autres options sont grisées avec la raison) ; le remplaçant doit aussi être libre sur chaque créneau. Une matière ou une licence ne peut être retirée à un enseignant que s'il n'y a plus de cours (il faut d'abord les confier à un autre).
  - **Emplois du temps** : chaque groupe, salle et enseignant a une page de consultation (« Emploi du temps » dans les listes) où l'admin peut retirer un cours (mal placé, enseignant absent…).
  - **Licences et groupes** (une seule page : chaque licence, filière comme Informatique ou Mathématiques, affiche ses groupes classés par niveau, avec « + Groupe » qui préremplit la licence) et **salles** (nom et type) : création, modification et suppression tant qu'ils ne sont pas utilisés (une licence par des groupes, un groupe par des étudiants ou des cours, une salle par des cours). Chaque groupe appartient à une licence et à un niveau (L1, L2, L3, M1, M2), et son nom est unique dans une même licence et un même niveau. Un étudiant appartient à un groupe : sa licence et son niveau en découlent, ils ne peuvent donc pas être incohérents. Si un groupe change de licence, les enseignants qui y ont cours reçoivent automatiquement la nouvelle licence (le formulaire prévient avant d'enregistrer, avec le nombre d'étudiants et d'enseignants concernés). **Matières** (page Enseignants, liste paginée à 10 par page indépendamment de celle des enseignants) : création et suppression tant qu'aucun cours ne les utilise.
- **Impression et agenda** : les étudiants et les enseignants peuvent imprimer leur emploi du temps (mise en page dédiée, sans menus) ou l'exporter en `.ics` (cours hebdomadaires récurrents, à importer dans Google Agenda, Outlook, Apple Calendrier…). Les pages d'emploi du temps de l'admin s'impriment aussi.
- **Étudiants** : consultation en lecture seule de l'emploi du temps de leur groupe.
- **Comptes** : à la création, l'identifiant de connexion (numérique, 8 chiffres au plus, à partir de `94020000`, une seule suite pour enseignants et étudiants) et le mot de passe initial (`nom_prenom`) sont générés automatiquement et affichés une seule fois. Le changement de mot de passe est obligatoire à la première connexion ; le nouveau mot de passe fait au moins 8 caractères avec lettres et chiffres, et doit différer de l'actuel et du mot de passe initial. Aucun formulaire (première connexion ou profil) ne demande le mot de passe actuel. Quand l'admin réinitialise un mot de passe, la personne est déconnectée partout ; quand quelqu'un change son propre mot de passe, ses autres appareils sont déconnectés (sessions ouvertes et « Se souvenir de moi ») mais l'appareil en cours reste connecté. Le profil est en lecture seule pour tous les rôles, admin compris (sauf le mot de passe) : le nom se modifie depuis les pages admin, l'identifiant n'est pas modifiable, et personne ne peut supprimer son propre compte.

## Stack

- Laravel 13 / PHP 8.3+, base de données SQLite.
- Blade (composants `<x-...>`), [Tailwind CSS](https://tailwindcss.com), [Alpine.js](https://alpinejs.dev) et [Turbo](https://turbo.hotwired.dev) (navigation et formulaires sans rechargement complet de la page), servis **en local** (`public/css/app.css`, `public/js/alpine.min.js`, `public/js/turbo.min.js`) : aucune dépendance à un CDN ni à Internet, et ni Node ni build à lancer pour utiliser l'application (voir « Modifier l'interface » pour régénérer le CSS).

## Installation

```bash
composer install
cp .env.example .env
php artisan key:generate

php artisan migrate   # la base de démonstration est fournie : applique seulement les migrations manquantes

php artisan serve
```

La base SQLite `database/database.sqlite` est **fournie avec le projet**, déjà remplie avec les données de démonstration (voir ci-dessous) : ne pas lancer `--seed` dessus, le seeder échouerait sur les comptes déjà présents. Pour repartir d'une base de démonstration neuve : `php artisan migrate:fresh --seed` (efface tout le contenu actuel).

L'application est alors disponible sur `http://localhost:8000`. En développement, `php artisan serve` suffit : `composer dev` lance seulement le serveur (plus de worker de file d'attente, la file étant synchrone) mais affiche une erreur `npx` si Node n'est pas installé, sans empêcher le serveur de démarrer.

## Comptes de démonstration

Présents dans la base fournie et recréés par le seeder (`database/seeders/DatabaseSeeder.php`), identifiant / mot de passe :

| Rôle | Identifiant | Mot de passe |
| --- | --- | --- |
| Admin | `1` | `admin` |
| Enseignant (Prof Demo) | `2` | `prof` |
| Étudiant (Eleve Demo) | `3` | `eleve` |

Tous les autres enseignants et étudiants de la démonstration ont aussi un compte, créé comme depuis l'interface admin : identifiants à partir de `94020000`, attribués dans un ordre aléatoire entre enseignants et étudiants (le numéro ne révèle pas le rôle ; ils sont affichés dans les listes Enseignants et Étudiants de l'admin), mot de passe initial `nom_prenom` (en minuscules, sans accents ni espaces, par exemple `dupont_jean`), à changer à la première connexion.

Les comptes réels créés depuis l'interface admin reçoivent un identifiant généré automatiquement : le premier numéro libre à partir de `94020000` (une seule suite pour enseignants et étudiants, 8 chiffres au maximum ; voir `User::generateIdentifiant()`). Le numéro d'un compte supprimé peut donc être réattribué.

Les licences, matières et groupes créés par le seeder reproduisent la structure officielle des maquettes de la Licence Informatique et de la Licence Mathématiques de l'**UPEC** (Université Paris-Est Créteil) ; seuls les intitulés des matières et l'organisation par semestre sont repris, à titre d'exemple pour peupler des données de démonstration réalistes.

## Modifier l'interface

Le CSS (`public/css/app.css`) est généré à partir des classes Tailwind utilisées dans `resources/views/` et `app/`, puis versionné : rien à compiler pour lancer ou déployer l'application. Après avoir ajouté ou changé des classes Tailwind dans les vues, il faut le régénérer (le test `AssetsTest` échoue si des classes essentielles manquent) :

1. Télécharger la [CLI autonome de Tailwind 3.4](https://github.com/tailwindlabs/tailwindcss/releases/tag/v3.4.17) (`tailwindcss-windows-x64.exe`, ou la version de votre système) dans le dossier `tools/` (ignoré par git), sans Node ni npm.
2. Lancer :

```bash
./tools/tailwindcss.exe -c tailwind.config.js -i resources/css/app.css -o public/css/app.css --minify
```

Alpine.js est fourni tel quel dans `public/js/alpine.min.js` (version 3.17.4), Turbo dans `public/js/turbo.min.js` (version 8.0.23) et le script de la grille de l'enseignant est dans `public/js/teacher-scheduler.js` ; les trois sont chargés dans `resources/views/layouts/head.blade.php`, commun à toutes les pages. Si la CLI Tailwind est présente dans `tools/`, `AssetsTest` régénère le CSS et vérifie qu'il est identique à celui versionné (le test est ignoré sinon).

## Tests

```bash
php artisan test
```

Suite de tests Feature couvrant les règles métier de réservation de cours (`LessonTest`), la gestion des comptes, matières, licences, groupes et salles par l'admin (`AdminTeacherTest`, `AdminStudentTest`, `AdminAccountTest`, `AdminTeacherLessonsTest`, `AdminSubjectTest`, `AdminLicenceTest`, `AdminGroupTest`, `AdminRoomTest`, `AdminTimetableTest`), les règles de mot de passe et le blocage des connexions après 5 échecs (`Auth/`), les commandes d'administration et la configuration de `composer dev` (`Console/`), la page de profil (`ProfileTest`), l'export d'agenda (`CalendarTest`), les assets locaux (`AssetsTest`), les pages d'erreur en français (`ErrorPagesTest`), les titres de page (`PageTitlesTest`), les données de démonstration (`DatabaseSeederTest`), la préparation du tableau de bord enseignant (`BuildTeacherScheduleTest`) et l'affichage des tableaux de bord (`DashboardTest`) et les composants Blade partagés (`ComponentsTest`).

## Qualité de code

```bash
vendor/bin/pint
```

## Mise en production

1. **Configuration** (`.env`) : `APP_ENV=production`, **`APP_DEBUG=false`** (le `.env.example` est réglé pour le développement local avec `APP_DEBUG=true`, ce qui exposerait les détails des erreurs), `APP_URL=https://…` et, l'application étant servie en **HTTPS**, `SESSION_SECURE_COOKIE=true`. Pour les journaux, préférer `LOG_STACK=daily` (un fichier par jour, avec rotation) et `LOG_LEVEL=warning` : avec `single` et `debug` (réglages de développement), un seul fichier grossit sans fin.
2. **Base de données** : ne pas utiliser la base fournie, qui contient les comptes de démonstration (`1`, `2`, `3`, mots de passe connus) et des données fictives. Remplacer `database/database.sqlite` par un fichier vide, puis `php artisan migrate --force`, **sans `--seed`** (le seeder ne sert qu'au développement).
3. **Premier administrateur** : `php artisan app:create-admin` (nom, identifiant, mot de passe demandés). Si l'admin oublie son mot de passe : `php artisan app:reset-admin-password <identifiant>`. Les autres comptes sont ensuite créés depuis l'interface.
4. **Optimisation** : `php artisan config:cache && php artisan route:cache && php artisan view:cache`.
5. **Sauvegardes** : toutes les données sont dans le fichier `database/database.sqlite` — le copier régulièrement (application arrêtée ou avec `sqlite3 database/database.sqlite ".backup sauvegarde.sqlite"`).

### Migrations

Tant qu'aucune vraie donnée n'est saisie, les migrations existantes peuvent être modifiées puis rejouées avec `php artisan migrate:fresh --seed` (base de développement recréée). **Dès qu'une base contient de vraies données, ne plus modifier les migrations existantes** : ajouter à chaque évolution du schéma une nouvelle migration (`php artisan make:migration …`), qui s'applique avec `php artisan migrate` sans rien perdre.
