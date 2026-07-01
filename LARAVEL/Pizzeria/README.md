# Pizzeria — Application de commande de pizzas

Application web de commande de pizzas développée avec Laravel 12. Elle gère trois rôles distincts : administrateur, livreur et client.

## Fonctionnalités

### Client
- Inscription et connexion (avec option "Se souvenir de moi")
- Commande de pizzas avec sélection par boutons +/−
- Calcul du total en temps réel
- Page de confirmation avec récapitulatif de commande
- Historique des commandes avec statut et prix

### Administrateur
- Tableau de bord avec statistiques (pizzas, commandes en cours, livreurs)
- CRUD complet sur les pizzas (nom, prix, description) avec pagination
- Consultation des commandes en cours et livrées (séparées)
- Assignation d'un livreur à une commande
- Création de comptes livreurs (nom + identifiant de connexion)
- CRUD sur les livreurs

### Livreur
- Vue de ses commandes assignées à livrer
- Marquage d'une commande comme livrée
- Historique de ses livraisons effectuées

## Statuts de commande

| Statut | Déclencheur |
|--------|-------------|
| En préparation | Commande passée |
| En livraison | Admin assigne un livreur |
| Livrée | Livreur marque comme livrée |

## Installation

```bash
git clone <url-du-repo>
cd Pizzeria

cp .env.example .env

composer install

php artisan key:generate
```

Configurer la base de données dans `.env` :

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

L'application est accessible sur [http://127.0.0.1:8000](http://127.0.0.1:8000).

## Compte admin par défaut

| Identifiant | Mot de passe |
|-------------|--------------|
| admin | admin |

## Stack technique

- **Framework** : Laravel 12
- **Base de données** : MySQL
- **Auth** : Laravel Auth (session, remember me)
- **Rôles** : Middleware personnalisés (admin, driver, customer)
