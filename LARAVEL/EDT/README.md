# EDT — Emploi du temps

Application Laravel de gestion d'emploi du temps pour un établissement scolaire. Trois rôles : administrateur, enseignant, étudiant.

## Fonctionnalités

### Enseignant
- Grille hebdomadaire (lundi–samedi, créneaux d'1h de 8h à 18h, pause 12h–14h, samedi matin uniquement) : ses cours et la disponibilité du groupe choisi
- Ajout d'un cours (matière, groupe, salle — salles libres uniquement) / suppression de ses propres cours
- Récapitulatif "Ma semaine" tous groupes confondus
- Impression et export `.ics` de son emploi du temps

### Administrateur
- Tableau de bord : effectifs et étudiants sans groupe
- CRUD enseignants, étudiants, matières, licences/groupes, salles
- Attribution des matières et licences à un enseignant
- Remplacement d'un enseignant : transfert de ses cours à un remplaçant habilité et disponible
- Consultation et retrait de cours sur l'emploi du temps de chaque groupe, salle ou enseignant
- Réinitialisation de mot de passe

### Étudiant
- Consultation en lecture seule de l'emploi du temps de son groupe
- Impression et export `.ics`

### Comptes
- Identifiant généré automatiquement (8 chiffres max, à partir de `94020000`)
- Mot de passe initial `nom_prenom`, changement obligatoire à la première connexion
- Blocage après 5 échecs de connexion (par identifiant + IP)

## Installation

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
php artisan serve
```

La base SQLite fournie contient déjà des données de démonstration (ne pas relancer `--seed` dessus). Pour repartir d'une base neuve : `php artisan migrate:fresh --seed`.

L'application est accessible sur [http://localhost:8000](http://localhost:8000).

## Comptes de démonstration

| Rôle | Identifiant | Mot de passe |
|------|-------------|--------------|
| Admin | `1` | `admin` |
| Enseignant | `2` | `prof` |
| Étudiant | `3` | `eleve` |

## Stack technique

- **Framework** : Laravel 13 / PHP 8.3+
- **Base de données** : SQLite
- **Frontend** : Blade, Tailwind CSS, Alpine.js, Turbo — servis en local, aucun build requis pour lancer l'application
- **Rôles** : middleware personnalisé (admin)
- **Langue** : interface en français
