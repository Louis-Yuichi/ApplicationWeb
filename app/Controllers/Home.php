<?php

namespace App\Controllers;

class Home extends BaseController
{
	public function index()
	{
		if (session()->get('isLoggedIn'))
		{
			return redirect()->to('accueil');
		}

		return $this->view('account/login.html.twig');
	}

	public function login()
	{
		$model = new \App\Models\UtilisateurModel();
		$email = $this->request->getPost('email');
		$password = $this->request->getPost('password');
		
		$user = $model->where('mailUtilisateur', $email)->first();
		
		if (!$user || !password_verify($password, $user['mdpUtilisateur']))
		{
			return redirect()->to('/') ->with('error', 'Email ou mot de passe incorrect');
		}

		$session = session();
		$session->set
		([
			'isLoggedIn' => true,
			'userId'     => $user['idUtilisateur'],
			'userName'   => $user['nomUtilisateur'],
			'userPrenom' => $user['prenomUtilisateur']
		]);

		return redirect()->to('/accueil');
	}

	public function register()
	{
		if ($this->request->getMethod() === 'POST')
		{
			$postData = $this->request->getPost();

			// 1. Vérification des mots de passe
			if ($postData['password'] !== $postData['password_confirm'])
			{
				return redirect()->back()
					->with('error', 'Les mots de passe ne correspondent pas')
					->withInput();
			}

			// 2. Préparation des données
			$data =
			[
				'nomUtilisateur'    => $postData['nom'],
				'prenomUtilisateur' => $postData['prenom'],
				'mailUtilisateur'   => $postData['email'],
				'mdpUtilisateur'    => password_hash($postData['password'], PASSWORD_DEFAULT)
			];

			try
			{
				// 3. Test direct avec la base de données
				$db = \Config\Database::connect();
				$builder = $db->table('UtilisateurWeb');
				
				// 4. Tentative d'insertion directe
				$result = $builder->insert($data);
				
				if ($result)
				{
					return redirect()->to('/')
						->with('success', 'Compte créé avec succès');
				}
				
				return redirect()->back()
					->with('error', 'Erreur lors de la création du compte')
					->withInput();

			}
			catch (\Exception $e)
			{
				return redirect()->back()
					->with('error', 'Une erreur est survenue')
					->withInput();
			}
		}

		return $this->view('account/register.html.twig');
	}

	public function logout()
	{
		session()->destroy();
		return redirect()->to('/');
	}
	
	public function accueil()
	{
		if (!session()->get('isLoggedIn'))
		{
			return redirect()->to('/');
		}
		
		return $this->view('accueil.html.twig', ['userName' => session()->get('userName')]);
	}
}
