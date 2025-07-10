# Application Web de Gestion - Stage DT231159

Application web développée avec CodeIgniter 4 et Twig pour la gestion des données étudiantes et Parcoursup.

## Prérequis

- PHP 8.1 ou supérieur
- PostgreSQL
- Composer
- Serveur web (Apache/Nginx) ou utilisation de `php spark serve`

Extensions PHP requises :
- intl
- mbstring
- pgsql
- json
- libcurl

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

CI_ENVIRONMENT = development

#--------------------------------------------------------------------
# APP
#--------------------------------------------------------------------

# Pour php spark serve (développement local)
app.baseURL = 'http://localhost:8080/'

# Pour un serveur web classique (remplacez par votre domaine)
# app.baseURL = 'http://woody/~dt231159/STAGE/ApplicationWeb/public/'

app.indexPage = ''

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
# database.default.password = VotreMotDePasse
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

#### Initialiser les données de base (optionnel)

```bash
# Exécuter les seeders pour insérer des données de test
php spark db:seed DatabaseSeeder

# Ou des seeders spécifiques
php spark db:seed UtilisateurSeeder
php spark db:seed PromotionSeeder
```

### 5. Configuration du serveur web

#### Option A : Serveur de développement (Recommandé pour le développement)

```bash
php spark serve
```

Votre application sera accessible sur : `http://localhost:8080/`

#### Option B : Serveur Apache/Nginx

Configurez votre serveur web pour pointer vers le dossier `public/` du projet.

**Apache - Fichier .htaccess (déjà inclus) :**
```apache
RewriteEngine On
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^(.*)$ index.php/$1 [QSA,L]
```

**Nginx - Configuration :**
```nginx
server {
    listen 80;
    server_name votre-domaine.com;
    root /chemin/vers/votre/projet/public;
    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.1-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }
}
```

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
3. **Créez un compte** ou utilisez les identifiants par défaut (si seeders exécutés)

### Fonctionnalités principales

- **Authentification** : Inscription et connexion des utilisateurs
- **Gestion ScoDoc** : Import et gestion des données étudiantes
- **Gestion Parcoursup** : Traitement des données Parcoursup
- **Export PDF** : Génération de rapports en PDF

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

# Création de fichiers
php spark make:migration CreateTableNom    # Créer une migration
php spark make:seeder NomSeeder            # Créer un seeder
php spark make:controller MonController    # Créer un contrôleur
php spark make:model MonModel              # Créer un modèle

# Autres commandes
php spark cache:clear               # Vider le cache
php spark routes                    # Lister les routes
```

### Ajout de nouvelles fonctionnalités

1. **Contrôleurs** : Ajoutez vos contrôleurs dans `app/Controllers/`
2. **Modèles** : Créez vos modèles dans `app/Models/`
3. **Vues** : Ajoutez vos templates Twig dans `app/Views/`
4. **Routes** : Configurez vos routes dans `app/Config/Routes.php`
5. **Migrations** : Créez des migrations pour les modifications de base de données

### Créer une nouvelle migration

```bash
# Créer une migration
php spark make:migration CreateTableExample

# Éditer le fichier créé dans app/Database/Migrations/
# Puis exécuter la migration
php spark migrate
```

## Dépendances principales

- **CodeIgniter 4** : Framework PHP
- **Twig** : Moteur de templates
- **PhpSpreadsheet** : Traitement des fichiers Excel
- **TCPDF** : Génération de PDF

## Dépannage

### Erreur de connexion à la base de données

1. **Vérifiez que PostgreSQL est démarré** :
   ```bash
   sudo systemctl status postgresql
   ```

2. **Testez la connexion** :
   ```bash
   psql -h localhost -U votre_username -d votre_base
   ```

3. **Vérifiez les paramètres dans `.env`**

### Erreur lors des migrations

1. **Vérifiez le statut des migrations** :
   ```bash
   php spark migrate:status
   ```

2. **En cas d'erreur, réinitialisez** :
   ```bash
   php spark migrate:refresh
   ```

3. **Vérifiez que la base de données existe et est accessible**

### Erreur 404 sur les routes

1. Vérifiez que le fichier `.htaccess` est présent dans `public/`
2. Assurez-vous que `mod_rewrite` est activé sur Apache
3. Vérifiez que `app.baseURL` est correct dans `.env`

### Problèmes de permissions

```bash
# Donner les bonnes permissions
chmod -R 755 writable/
chmod -R 644 .env
```

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

---

**Note importante** : 
1. Configurez votre fichier `.env` avec vos propres paramètres
2. Exécutez `php spark migrate` pour créer les tables
3. Optionnel : Exécutez `php spark db:seed DatabaseSeeder` pour les données de test
