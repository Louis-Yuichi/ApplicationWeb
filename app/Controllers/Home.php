<?php

namespace App\Controllers;

class Home extends BaseController
{
    public function index()
    {
        if (session()->get('isLoggedIn')) {
            return redirect()->to('accueil');
        }
        return $this->view('account/login.html.twig');
    }

    public function login()
    {
        $model = new \App\Models\UtilisateurModel();
        $email = $this->request->getPost('email');
        $password = $this->request->getPost('password');

        log_message('debug', 'Tentative de connexion - Email: ' . $email);
        
        $user = $model->where('mailUtilisateur', $email)->first();
        
        if (!$user) {
            log_message('debug', 'Utilisateur non trouvé - Email: ' . $email);
            return redirect()->to('/')
                            ->with('error', 'Email ou mot de passe incorrect');
        }

        if (!password_verify($password, $user['mdpUtilisateur'])) {
            log_message('debug', 'Mot de passe incorrect pour - Email: ' . $email);
            return redirect()->to('/')
                            ->with('error', 'Email ou mot de passe incorrect');
        }

        // Si on arrive ici, l'authentification est réussie
        $session = session();
        $session->set([
            'isLoggedIn' => true,
            'userId' => $user['idUtilisateur'],
            'userName' => $user['nomUtilisateur'],
            'userPrenom' => $user['prenomUtilisateur']
        ]);

        log_message('info', 'Connexion réussie - Email: ' . $email);
        return redirect()->to('/accueil');
    }

    public function register()
    {
        log_message('debug', '-------- DEBUT REGISTER --------');
        log_message('debug', 'Méthode HTTP : ' . $this->request->getMethod());
        
        if (strtoupper($this->request->getMethod()) === 'POST') {  // Uppercase comparison
            log_message('debug', 'POST data : ' . json_encode($this->request->getPost()));
            
            if ($this->request->getPost('password') !== $this->request->getPost('password_confirm')) {
                log_message('warning', 'Mots de passe différents');
                return redirect()->back()
                    ->with('error', 'Les mots de passe ne correspondent pas')
                    ->withInput();
            }

            $model = new \App\Models\UtilisateurModel();
            $data = [
                'nomUtilisateur' => $this->request->getPost('nom'),
                'prenomUtilisateur' => $this->request->getPost('prenom'),
                'mailUtilisateur' => $this->request->getPost('email'),
                'mdpUtilisateur' => password_hash($this->request->getPost('password'), PASSWORD_DEFAULT)
            ];

            log_message('debug', 'Données à insérer : ' . json_encode($data));

            try {
                $result = $model->insert($data);
                log_message('debug', 'Résultat insertion : ' . var_export($result, true));
                
                if ($result) {
                    log_message('info', 'Utilisateur créé : ' . $data['mailUtilisateur']);
                    return redirect()->to('/')
                        ->with('success', 'Compte créé avec succès');
                }
                
                log_message('error', 'Erreurs validation : ' . json_encode($model->errors()));
                return redirect()->back()
                    ->with('error', implode(', ', $model->errors()))
                    ->withInput();

            } catch (\Exception $e) {
                log_message('error', 'Exception : ' . $e->getMessage());
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
        // Vérification que l'utilisateur est connecté
        if (!session()->get('isLoggedIn')) {
            return redirect()->to('/');
        }
        
        return $this->view('accueil.html.twig', [
            'userName' => session()->get('userName')
        ]);
    }
}
