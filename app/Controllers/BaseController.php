<?php

namespace App\Controllers;

use CodeIgniter\Controller;
use CodeIgniter\HTTP\CLIRequest;
use CodeIgniter\HTTP\IncomingRequest;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;

require_once __DIR__ . '/../../vendor/autoload.php';

/**
 * Class BaseController
 *
 * BaseController provides a convenient place for loading components
 * and performing functions that are needed by all your controllers.
 * Extend this class in any new controllers:
 *     class Home extends BaseController
 *
 * For security be sure to declare any new methods as protected or private.
 */
abstract class BaseController extends Controller
{
	/**
	 * Instance of the main Request object.
	 *
	 * @var CLIRequest|IncomingRequest
	 */
	protected $request;

	/**
	 * An array of helpers to be loaded automatically upon
	 * class instantiation. These helpers will be available
	 * to all other controllers that extend BaseController.
	 *
	 * @var list<string>
	 */
	protected $helpers = [];

	/**
	 * Be sure to declare properties for any property fetch you initialized.
	 * The creation of dynamic property is deprecated in PHP 8.2.
	 */
	protected $session;

	/**
	 * @return void
	 */
	public function initController(RequestInterface $request, ResponseInterface $response, LoggerInterface $logger)
	{
		// Do Not Edit This Line
		parent::initController($request, $response, $logger);

		// Preload any models, libraries, etc, here.
		$this->session = \Config\Services::session();
	}

	protected function view(string $viewName, array $data = []) {

		$viewsPath = realpath(__DIR__ . '/../Views');
		$loader = new \Twig\Loader\FilesystemLoader($viewsPath);
		$twig = new \Twig\Environment($loader, [
			'cache' => false, // Mettre un dossier ('cache/') en production pour améliorer les performances
		]);

// Rendu du template accueil.twig avec des variables
		$data['userName'] = session()->get('userName');
		$data['userPrenom'] = session()->get('userPrenom');
		$data['isLoggedIn'] = session()->get('isLoggedIn');

		echo $twig->render($viewName,$data);
	}

	protected function getCurrentUser()
	{
		$session = session();
		
		// Utiliser les données déjà disponibles en session
		$nom = $session->get('userName') ?? 'Nom Utilisateur';
		$prenom = $session->get('userPrenom') ?? 'Prénom Utilisateur';
		
		return [
			'nom' => $nom,
			'prenom' => $prenom
		];
	}
}
