<?php

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

/*
|--------------------------------------------------------------------------
| Vérifier le mode maintenance
|--------------------------------------------------------------------------
|
| Si l'application est en maintenance, Laravel charge automatiquement
| la page maintenance au lieu de lancer toute l'application.
|
*/

if (file_exists($maintenance = __DIR__.'/../storage/framework/maintenance.php')) {
    require $maintenance;
}

/*
|--------------------------------------------------------------------------
| Charger l'autoloader Composer
|--------------------------------------------------------------------------
|
| Cette ligne permet de charger automatiquement toutes les classes
| Laravel et les dépendances installées avec Composer.
|
*/

require __DIR__.'/../vendor/autoload.php';

/*
|--------------------------------------------------------------------------
| Démarrer l'application Laravel
|--------------------------------------------------------------------------
|
| Le fichier bootstrap/app.php initialise :
| - les services Laravel
| - les configurations
| - les providers
| - le conteneur d'application
|
*/

$app = require_once __DIR__.'/../bootstrap/app.php';

/*
|--------------------------------------------------------------------------
| Traiter la requête HTTP
|--------------------------------------------------------------------------
|
| Laravel capture la requête du navigateur puis :
| 1. Passe par les middlewares
| 2. Vérifie les routes
| 3. Exécute le contrôleur
| 4. Retourne la réponse au navigateur
|
*/

$app->handleRequest(Request::capture());
