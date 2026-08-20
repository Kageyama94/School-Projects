# Pizzeria — Application de commande de pizzas

Application web de commande de pizzas développée avec Laravel 13. Elle gère trois rôles distincts : administrateur, livreur et client.

## Fonctionnalités

### Client
- Inscription et connexion (avec option "Se souvenir de moi")
- Commande de pizzas avec sélection par boutons +/− et calcul du total en temps réel
- Page de confirmation avec récapitulatif de commande
- Historique des commandes avec statut, détail et prix
- Annulation d'une commande tant qu'elle n'a pas été acceptée
- Gestion de son profil (nom, email, téléphone)

### Administrateur
- Tableau de bord avec statistiques (pizzas, commandes en cours, livrées, livreurs)
- CRUD complet sur les pizzas (nom, prix, description) avec pagination
- Suivi des commandes séparé par statut : nouvelles, en cours, livrées (paginées)
- Filtrage des commandes par livreur et par date
- Acceptation d'une commande puis assignation d'un livreur
- CRUD sur les comptes livreurs (nom + identifiant de connexion)

### Livreur
- Vue de ses commandes assignées à livrer
- Marquage d'une commande comme livrée
- Historique paginé de ses livraisons effectuées

## Statuts de commande

| Statut | Déclencheur |
|--------|-------------|
| En attente | Commande passée par le client |
| En préparation | Admin accepte la commande |
| En livraison | Admin assigne un livreur |
| Livrée | Livreur marque la commande comme livrée |

## Installation

```bash
git clone <url-du-repo>
cd Pizzeria

cp .env.example .env

composer install

php artisan key:generate
```

Par défaut, l'application utilise SQLite (aucune configuration requise) :

```bash
touch database/database.sqlite
```

Pour utiliser MySQL à la place, modifier `.env` :

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=pizzeria
DB_USERNAME=root
DB_PASSWORD=
```

Puis lancer les migrations et le seeder :

```bash
php artisan migrate:fresh --seed
```

Démarrer le serveur :

```bash
php artisan serve
```

L'application est accessible sur [http://127.0.0.1:8000](http://127.0.0.1:8000) (redirige vers `/pizzeria`).

## Compte admin par défaut

| Identifiant | Mot de passe |
|-------------|--------------|
| admin | admin |

## Stack technique

- **Framework** : Laravel 13
- **Base de données** : SQLite par défaut (MySQL supporté)
- **Auth** : Laravel Auth (session, remember me)
- **Rôles** : Middleware personnalisés (admin, driver, customer)
- **Langue** : Interface et validations en français (`lang/fr`)
