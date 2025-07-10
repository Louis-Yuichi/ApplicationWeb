# Application Web de Gestion

Application web développée avec CodeIgniter 4 et Twig pour la gestion des données étudiantes et Parcoursup.

## Prérequis

- PHP 8.1 ou supérieur
- PostgreSQL
- Composer

## Installation

### 1. Cloner/Télécharger le projet

```bash
# Si vous clonez depuis un dépôt
git clone [URL_DU_DEPOT] ApplicationWeb
cd ApplicationWeb

# Ou si vous avez téléchargé l'archive
cd ApplicationWeb
```

### 2. Installer les dépendances avec Composer

```bash
# Installer Composer si pas déjà fait
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer

# Installer les dépendances du projet
composer install
```

### 3. Configuration de l'environnement

Copiez le fichier d'exemple et configurez vos paramètres :

```bash
cp env .env
```

Modifiez le fichier `.env` avec vos paramètres :

```properties
#--------------------------------------------------------------------
# ENVIRONMENT
#--------------------------------------------------------------------

# CI_ENVIRONMENT = development

#--------------------------------------------------------------------
# APP
#--------------------------------------------------------------------

# app.baseURL = 'http://localhost:8080/'
# app.indexPage = ''

#--------------------------------------------------------------------
# DATABASE
#--------------------------------------------------------------------

# Configuration PostgreSQL
database.default.hostname = localhost
database.default.database = VOTRE_NOM_BASE
database.default.username = VOTRE_USERNAME
database.default.password = VOTRE_PASSWORD
database.default.DBDriver = Postgre
database.default.port = 5432

# Exemple pour Woody :
# database.default.hostname = woody
# database.default.database = dt231159
# database.default.username = dt231159
# database.default.password = MotDePasse
```

### 4. Configuration de la base de données

#### Créer la base de données PostgreSQL

```sql
-- Connectez-vous à PostgreSQL
psql -U postgres

-- Créez votre base de données
CREATE DATABASE votre_nom_base;

-- Créez un utilisateur (si nécessaire)
CREATE USER votre_username WITH PASSWORD 'votre_password';

-- Accordez les privilèges
GRANT ALL PRIVILEGES ON DATABASE votre_nom_base TO votre_username;

-- Quittez PostgreSQL
\q
```

#### Exécuter les migrations

Les migrations vont créer automatiquement toutes les tables nécessaires :

```bash
# Exécuter toutes les migrations
php spark migrate

# Vérifier le statut des migrations
php spark migrate:status

# Si vous devez revenir en arrière (rollback)
php spark migrate:rollback

# Pour réinitialiser complètement la base
php spark migrate:refresh
```

#### Tables créées par les migrations

Les migrations vont créer les tables suivantes :
- **Utilisateur** : Gestion des utilisateurs et authentification
- **Promotion** : Gestion des promotions étudiantes
- **Etudiant** : Informations des étudiants
- **Evaluation** : Système d'évaluation
- **Competence** : Gestion des compétences
- **Parcoursup_Candidat** : Données Parcoursup
- **Export_Log** : Historique des exports

### 5. Configuration du serveur web

#### Serveur de développement

```bash
php spark serve
```

Votre application sera accessible sur : `http://localhost:8080/`

## Structure du projet

```
ApplicationWeb/
├── app/
│   ├── Controllers/        # Contrôleurs de l'application
│   ├── Models/            # Modèles pour la base de données
│   ├── Views/             # Templates Twig
│   ├── Config/            # Configuration de l'application
│   ├── Database/
│   │   ├── Migrations/    # Fichiers de migration
│   │   └── Seeds/         # Fichiers de peuplement
│   └── ...
├── public/
│   ├── assets/            # CSS, JS, images
│   ├── index.php          # Point d'entrée principal
│   └── .htaccess          # Configuration Apache
├── vendor/                # Dépendances Composer
├── writable/              # Logs et cache
├── .env                   # Configuration environnement
└── composer.json          # Dépendances du projet
```

## Utilisation

### Première connexion

1. **Démarrez votre serveur** : `php spark serve`
2. **Accédez à** : `http://localhost:8080/`
3. **Créez un compte**

### Fonctionnalités principales

- **Authentification** : Inscription et connexion des utilisateurs
- **Gestion ScoDoc** : Import et gestion des données étudiantes
- **Gestion Parcoursup** : Traitement des données Parcoursup

## Développement

### Commandes utiles

```bash
# Démarrer le serveur de développement
php spark serve

# Gestion de la base de données
php spark migrate                    # Exécuter les migrations
php spark migrate:status            # Statut des migrations
php spark migrate:rollback          # Annuler la dernière migration
php spark migrate:refresh           # Réinitialiser toutes les migrations
php spark db:seed DatabaseSeeder    # Insérer des données de test

# Autres commandes
php spark cache:clear               # Vider le cache
php spark routes                    # Lister les routes
```

## Dépendances principales

- **CodeIgniter 4** : Framework PHP
- **Twig** : Moteur de templates
- **PhpSpreadsheet** : Traitement des fichiers Excel
- **TCPDF** : Génération de PDF

## Structure de la base de données

### Tables principales

- **utilisateur** : Gestion des comptes utilisateurs
- **promotion** : Promotions étudiantes
- **etudiant** : Données des étudiants
- **evaluation** : Système d'évaluation
- **competence** : Référentiel de compétences
- **parcoursup_candidat** : Données d'import Parcoursup

### Relations

- Un utilisateur peut gérer plusieurs promotions
- Une promotion contient plusieurs étudiants
- Les étudiants ont des évaluations liées aux compétences

## Support

Pour toute question ou problème :
1. Vérifiez ce README
2. Consultez la documentation CodeIgniter 4
3. Vérifiez les logs dans `writable/logs/`
4. Contactez l'équipe de développement

## Changelog

### Version 1.0
- Authentification utilisateur
- Système de migrations complet
- Gestion des données ScoDoc
- Gestion Parcoursup
- Export PDF
- Interface responsive
