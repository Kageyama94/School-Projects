# Jeux Olympiques — Laravel

Site des Jeux Olympiques : calendrier des épreuves, tableau des médailles, billetterie et administration.

## Fonctionnalités

### Public
- Accueil : compte à rebours vers la prochaine épreuve, podium des nations, derniers champions
- Épreuves : calendrier filtrable par sport, date et statut
- Sports et sites : fiches détaillées
- Médailles : classement or/argent/bronze par pays, fiche par pays

### Spectateur
- Réservation de billets (6 places max par épreuve), annulation possible tant que l'épreuve n'a pas eu lieu
- Suivi de ses billets (à venir, remboursés, passés)
- Gestion de compte (profil, mot de passe, suppression)

### Administrateur
- Tableau de bord : recettes, billets vendus/remboursés, remplissage des épreuves à venir
- CRUD épreuves, athlètes, sports, sites, pays, utilisateurs
- Annulation d'une épreuve (billets remboursés automatiquement)
- Saisie des résultats une fois l'épreuve commencée

### Robustesse
- Limite anti-abus : 5 échecs de connexion par e-mail + IP par minute, plafond de 60 requêtes/minute par IP (connexion et formulaires)
- Adresses e-mail insensibles à la casse
- Pages d'erreur en français

## Installation

```bash
composer run setup   # dépendances, .env, clé, base de démonstration
php artisan serve
```

`composer run setup` **recrée** la base avec des données de démonstration neuves (épreuves replacées autour du jour de l'installation). Pour seulement recaler les dates plus tard : `php artisan migrate:fresh --seed`.

## Comptes de démonstration

| Rôle | E-mail | Mot de passe |
|------|--------|--------------|
| Admin | `admin@jo.test` | `password` |
| Spectateur | `spectateur@jo.test` | `password` |

## Stack technique

- **Framework** : Laravel 13 / PHP 8.3+
- **Base de données** : SQLite
- **Frontend** : CSS local (`public/css/app.css`), pas de Node ni de Vite
- **Langue** : interface en français
