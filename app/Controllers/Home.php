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
		if ($this->request->getMethod() === 'post')
		{
			if ($this->request->getPost('password') !== $this->request->getPost('password_confirm'))
			{
				return redirect()->back() ->with('error', 'Les mots de passe ne correspondent pas') ->withInput();
			}

			$model = new \App\Models\UtilisateurModel();
			$data =
			[
				'nomUtilisateur'    => $this->request->getPost('nom'),
				'prenomUtilisateur' => $this->request->getPost('prenom'),
				'mailUtilisateur'   => $this->request->getPost('email'),
				'mdpUtilisateur'    => password_hash($this->request->getPost('password'), PASSWORD_DEFAULT)
			];

			try
			{
				if ($model->insert($data))
				{
					return redirect()->to('/') ->with('success', 'Compte créé avec succès');
				}

				return redirect()->back() ->with('error', implode(', ', $model->errors())) ->withInput();

			}
			catch (\Exception $e)
			{
				return redirect()->back() ->with('error', 'Une erreur est survenue') ->withInput();
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