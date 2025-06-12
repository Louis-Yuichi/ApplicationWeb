<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */
$routes->get  ('/'       , 'Home::index'   );
$routes->post ('login'   , 'Home::login'   );
$routes->get  ('register', 'Home::register');
$routes->match(['GET'    , 'POST'], 'register', 'Home::register');
$routes->get  ('logout'  , 'Home::logout'  );
$routes->get  ('accueil' , 'Home::accueil' );

// ParcourSupController routes
$routes->get ('parcoursup'       , 'ParcourSupController::menu'    );
$routes->get ('gestionParcourSup', 'ParcourSupController::gestion' );
$routes->post('importer'         , 'ParcourSupController::importer');

// ScodocController routes
$routes->get ('scodoc'  , 'ScodocController::menu'    );
$routes->post('importer', 'ScodocController::importer');