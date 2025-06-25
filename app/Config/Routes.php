<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */

$routes->get  ('/'                        , 'Home::index'   );
$routes->match(['GET', 'POST'], 'login'   , 'Home::login'   );
$routes->match(['GET', 'POST'], 'register', 'Home::register');
$routes->get  ('logout'                   , 'Home::logout'  );
$routes->get  ('accueil'                  , 'Home::accueil' );

// ParcourSupController routes
$routes->get ('parcoursup'                         , 'ParcourSupController::menu'                               );
$routes->get ('gestionParcourSup'                  , 'ParcourSupController::gestion'                            );
$routes->get ('evaluation'                         , 'ParcourSupController::evaluation'                         );
$routes->post('importer'                           , 'ParcourSupController::importer'                           );
$routes->post('modifierCandidat'                   , 'ParcourSupController::modifierCandidat'                   );
$routes->post('calculerNotesAjax'                  , 'ParcourSupController::calculerNotesAjax'                  );
$routes->post('exporterEvaluationAvecModifications', 'ParcourSupController::exporterEvaluationAvecModifications');

// Filtres routes
$routes->get ('filtres'                , 'ParcourSupController::filtres'           );
$routes->post('creerFiltre'            , 'ParcourSupController::creerFiltre'       );
$routes->get ('toggleFiltre/(:num)'    , 'ParcourSupController::toggleFiltre/$1'   );
$routes->get ('supprimerFiltre/(:num)' , 'ParcourSupController::supprimerFiltre/$1');
$routes->get ('calculerNotes'          , 'ParcourSupController::calculerNotes'     );

// ScodocController routes
$routes->match(['GET', 'POST'], 'scodoc' , 'ScodocController::index'               );
$routes->get  ('api/etudiants/(:num)'    , 'ScodocController::etudiantsParAnnee/$1'       );
$routes->get  ('api/absences/(:num)'     , 'ScodocController::absencesParEtudiant/$1'     );
$routes->get  ('api/apprentissage/(:num)', 'ScodocController::apprentissageParEtudiant/$1');
$routes->get  ('api/competences/(:num)'  , 'ScodocController::competencesParEtudiant/$1'  );
