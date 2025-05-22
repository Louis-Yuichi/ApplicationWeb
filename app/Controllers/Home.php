<?php

namespace App\Controllers;

use App\Models\UserApp;

class Home extends BaseController
{
	public function index()
	{
		$this->view('account/login.html.twig');
	}

	public function register()
	{
		$userModel = model('UserApp');

		if ($this->request->getMethod() === 'post') {
			$data = [
				'prenom'   => $this->request->getPost('prenom'),
				'nom'      => $this->request->getPost('nom'),
				'email'    => $this->request->getPost('email'),
				'password' => password_hash($this->request->getPost('password'), PASSWORD_DEFAULT),
			];

			// Validation simple (à améliorer)
			$errors = [];
			foreach (['prenom', 'nom', 'email', 'password'] as $field) {
				if (empty($data[$field])) {
					$errors[] = "Le champ $field est obligatoire.";
				}
			}

			if (empty($errors)) {
				$userModel->insert($data);
				return redirect()->to('/'); // Redirige vers la page de connexion
			} else {
				return $this->view('account/register.html.twig', ['errors' => $errors]);
			}
		}

		// GET : affiche le formulaire
		$this->view('account/register.html.twig');
	}

	public function accueil()
	{
		$this->view('accueil.html.twig');
	}
}