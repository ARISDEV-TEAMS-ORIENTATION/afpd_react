# Récupérer les dernier s modificiations dans github
git pull

# Installer les dépendances
composer install

# Créer un fichier .env à la racine de ton projet

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=adpd_db
DB_USERNAME=root
DB_PASSWORD

# Migration des tables

php artisan migrate:seed --force

# Créer un link pour les fichier

php artisan storage:link




# Extension pour visualiser les donneée s de la bd
Database Client JDBC