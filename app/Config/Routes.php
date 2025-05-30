<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */
$routes->get('/', 'Home::index');
$routes->get('register', 'Home::register');
$routes->post('register', 'Home::register');
$routes->get('accueil', 'Home::accueil');

// ParcourSupController routes
$routes->get('parcoursup', 'ParcourSupController::menu');
$routes->get('gestionParcourSup', 'ParcourSupController::gestion');
$routes->post('importer', 'ParcourSupController::importer');

// ScodocController routes
$routes->get('scodoc', 'ScodocController::menu');