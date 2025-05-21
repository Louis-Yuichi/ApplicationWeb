<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */
$routes->get('/', 'Home::index');
$routes->get('register', 'Home::register');
$routes->get('accueil', 'Home::accueil');

// ParcourSupController routes
$routes->get('parcoursup', 'ParcourSupController::menu');