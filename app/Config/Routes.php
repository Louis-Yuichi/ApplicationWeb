<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */
$routes->get('/', 'Home::index');
$routes->get('register', 'Home::register');
//$routes->post('register', 'Home::register'); // si tu veux gérer le POST dans la même méthode
