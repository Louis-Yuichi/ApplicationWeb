Fichiers

<?php
require_once './app/controllers/CommandeController.php';

$controller = new CommandeController();
$controller->index();


Home

<?php

require_once './app/core/Controller.php';
require_once './app/repositories/ProduitRepository.php';
require_once './app/repositories/EvenementRepository.php';
require_once './app/repositories/ActualiteRepository.php';
require_once './app/services/AuthService.php';
require_once './app/trait/FormTrait.php';

class HomeController extends Controller
{
	use FormTrait;

	private $isAdmin;

	public function __construct()
	{
		$service = new AuthService();
		$this->isAdmin = $service->isAdmin();
	}

	public function index()
	{
		$evenementRepo = new EvenementRepository();

		$evenements = $evenementRepo->findAll();

		$actualiteRepo = new ActualiteRepository();

		$actualites = $actualiteRepo->findAll();

		$this->view('index.html.twig', ["evenements" => $evenements, "actualites" => $actualites,"isAdmin" => $this->isAdmin]);
	}

	public function vitrine()
	{
		$this->view('vitrine.html.twig',  ["isAdmin" => $this->isAdmin]);
	}

	public function boutique()
	{
		$produitRepo = new ProduitRepository();

		$produits = $produitRepo->findAll();

		$this->view('boutique.html.twig', ['produits' => $produits, "isAdmin" => $this->isAdmin]);
	}

	public function evenement()
	{
		$evenementRepo = new EvenementRepository();

		$evenements = $evenementRepo->findAll();

		$this->view('evenement.html.twig', ['evenements' => $evenements, "isAdmin" => $this->isAdmin]);
	}

	public function contact()
	{
		$this->view('contact.html.twig', []);
	}
}

User 

<?php

require_once './app/core/Controller.php';
require_once './app/repositories/UtilisateurRepository.php';
require_once './app/repositories/RoleRepository.php';
require_once './app/services/AuthService.php';
require_once './app/trait/FormTrait.php';
require_once './app/trait/AuthTrait.php';

class UtilisateurController extends Controller {

    use FormTrait;
    use AuthTrait;


	public function gestion()
	{
		$repository = new UtilisateurRepository();
		$utilisateurs = $repository->findAll();

		$service = new AuthService();
		$isAdmin = $service->isAdmin();

		// Ensuite, affiche la vue
		$this->view('/utilisateur/gestionUtilisateurs.html.twig',  ['utilisateurs' => $utilisateurs, 'isAdmin' => $isAdmin]);
	}

	public function traiter(){
		$netud = $this->getQueryParam('netud');
		$action = $this->getQueryParam('action');

		if ($netud === null) {
			throw new Exception('Numero étudiant nécéssaire.');
		}

		$repository = new UtilisateurRepository();
		$utilisateur = $repository->findById($netud);
		if ($utilisateur === null ) {
			throw new Exception('Utilisateur non trouvé');
		}

		
		if ($action == 'accepter' || $action == 'promouvoir') {
			$repository->upgradeRole($utilisateur);
		} elseif ($action == 'retrograder') {
			$repository->degradeRole($utilisateur);
		}

		$utilisateur = $repository->findById($netud); // Récupération de l'utilisateur après mise à jour
		$utilisateur->setDemande(false);
		$repository->update($utilisateur); // Mise à jour de l'utilisateur
	

		$this->redirectTo('gestionUtilisateur.php'); // Redirection après traitement
	}
	public function traiterCompte(){
		$utilisateur = (new AuthService())->getUtilisateur();
		$action = $this->getQueryParam('action');

		if ($utilisateur === null) {
			throw new Exception('l\'utilisateur est nul (impréssionant).');
		}

		$repository = new UtilisateurRepository();
			
		if ($action == 'supprimer') {
			$repository->deleteById($utilisateur->getNetud());
			(new AuthService())->logout();
			$this->redirectTo('login.php'); 

		} elseif ($action == 'adhesion') {
			$utilisateur->setDemande(true);
			$repository->update($utilisateur);
			(new AuthService())->majUtilisateur($repository->findById($utilisateur->getNetud())); // Mettre à jour l'utilisateur dans la session

		} elseif ($action == 'notif') {
			$utilisateur->setTypeNotification($this->getQueryParam('valeur'));
			$repository->update($utilisateur);
			(new AuthService())->majUtilisateur($repository->findById($utilisateur->getNetud())); // Mettre à jour l'utilisateur dans la session

		} elseif ($action == 'modifier') {
			$this->redirectTo('utilisateur_update.php'); 

		} elseif ($action == 'déconnexion') {
			(new AuthService())->logout();
			$this->redirectTo('login.php'); // Redirection après déconnexion
		}

		$this->redirectTo('compte.php'); // Redirection après traitement
	}

	public function delete() {
		$netud = $this->getQueryParam('netud');
		if ($netud === null) {
			throw new Exception('Numero étudiant nécéssaire.');
		}
		$repository = new UtilisateurRepository();
		if (!$repository->deleteById($netud)) {
			throw new Exception('Erreur lors de la suppression d\'utilisateur.');
		}
		$this->redirectTo('index.php'); // Redirection après suppression
	}
    public function create() {
        $data = $this->getAllPostParams(); // Récupération des données soumises
        $errors = [];

        if (!empty($data)) {
            try {
                $errors = [];

                // Validation des données
				if (empty($data['numero_etudiant'])) {
					$errors[] = 'Le numéro étudiant est requis.';
				}
                if (empty($data['nom'])) {
                    $errors[] = 'Le nom est requis.';
				}
                if (empty($data['prenom'])) {
                    $errors[] = 'Le prénom est requis.';
                }
                if (empty($data['tel']) || strlen($data['tel']) < 10) {
                    $errors[] = 'Le numéro de téléphone valide est réquis.';
                }
                if (empty($data['email']) || !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
                    $errors[] = 'Un email valide est requis.';
                }
                if (empty($data['password']) || strlen($data['password']) < 6) {
                    $errors[] = 'Le mot de passe doit contenir au moins 6 caractères.';
                }

                if (!empty($errors)) {
                    throw new Exception(implode(', ', $errors));
                }

                // Création de l'objet utilisateur
                $hashedPassword = $this->hash($data['password']);
                $utilisateur = new Utilisateur($data['numero_etudiant'], $data['nom'], $data['prenom'], $data['tel'], $data['email'], $hashedPassword, "Les deux", (new RoleRepository())->findByNom("membre"),false);

                // Sauvegarde dans la base de données
                $utilisateurRepo = new UtilisateurRepository();
                if (!$utilisateurRepo->create($utilisateur)) {
                    throw new Exception('Erreur lors de l\'enregistrement de l\'utilisateur.');
                }

				(new AuthService())->setUtilisateur($utilisateur); // Connexion de l'utilisateur après création

                $this->redirectTo('index.php'); // Redirection après création
            } catch (Exception $e) {
                $errors = explode(', ', $e->getMessage()); // Récupération des erreurs
            }
        }

        // Affichage du formulaire
        $this->view('/utilisateur/register.html.twig',  [
            'data' => $data,
            'errors' => $errors,
			'utilisateur' => null,
			'isLoggedIn' => false
        ]);
    }

    public function update()
    {
        $utilisateur = (new AuthService())->getUtilisateur();

		if ($utilisateur === null) {
            throw new Exception('Utilisateur non trouvé');
        }

        $repository = new UtilisateurRepository();

        $data = array_merge([
			'numero_etudiant'=>$utilisateur->getNetud(),
            'prenom'=>$utilisateur->getPrenom(),
            'nom'=>$utilisateur->getNom(),
			'tel'=>$utilisateur->getTel(),
            'email'=>$utilisateur->getEmail(),
			'type_notification'=>$utilisateur->getTypeNotification(),
			'role'=>$utilisateur->getRole()->getNomRole(),
			'demande'=>$utilisateur->getDemande(),
        ],$this->getAllPostParams()); //Get submitted data
        $errors = [];
		
        if (!empty($this->getAllPostParams())) {
            try {

                // Data validation
                if (empty($data['numero_etudiant'])) {
					$errors[] = 'Le numéro étudiant est requis.';
				}
                if (empty($data['nom'])) {
                    $errors[] = 'Le nom est requis.';
				}
                if (empty($data['prenom'])) {
                    $errors[] = 'Le prénom est requis.';
                }
				if (empty($data['tel']) || strlen($data['tel']) < 10) {
					$errors[] = 'Le numéro de téléphone valide est réquis.';
				}
                if (empty($data['email']) || !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
                    $errors[] = 'Un email valide est requis.';
                }
                if (empty($data['password']) || strlen($data['password']) < 6) {
                    $errors[] = 'Le mot de passe doit contenir au moins 6 caractères.';
                }
				if (empty($data['demande'])) {
					$data['demande'] = false;
				}
				if (empty($data['type_notification'])) {
					$errors[] = 'Le type de notification est requis.';
				}

                if (!empty($errors)) {
                    throw new Exception(implode(', ', $errors));
                }

                // Mise à jour de l'objet utilisateur
                $utilisateur->setPrenom($data['prenom']);
                $utilisateur->setNom($data['nom']);
				$utilisateur->setTel($data['tel']);
                $utilisateur->setEmail($data['email']);
				$utilisateur->setTypeNotification($data['type_notification']);
				$utilisateur->setRole((new RoleRepository())->findByNom($data['role']));
				$utilisateur->setDemande((bool)$data['demande']);

                // Si le mot de passe est fourni, le hacher et le mettre à jour
                if (!empty($data['password'])) {
                    $hashedPassword = $this->hash($data['password']);
                    $utilisateur->setMdp($hashedPassword);
                }

                // Sauvegarde dans la base de données
                if (!$repository->update($utilisateur)) {
                    throw new Exception('Erreur lors de la mise à jour d\'utilisateur.');
                }

				(new AuthService())->majUtilisateur($utilisateur); // Mettre à jour l'utilisateur dans la session

                $this->redirectTo('compte.php'); // Redirect after update

            } catch (Exception $e) {
                $errors = explode(', ', $e->getMessage()); // Error retrieval
            }
        }

        // Display update form
        $this->view('/utilisateur/register.html.twig',  ['data' => $data, 'errors' => $errors, 'utilisateur' => $utilisateur, 'isLoggedIn' => true]);
    }
}


Inscription

<?php

require_once './app/core/Controller.php';
require_once './app/repositories/InscriptionRepository.php';
require_once './app/entities/Utilisateur.php';
require_once './app/services/AuthService.php';
require_once './app/trait/FormTrait.php';
require_once './app/trait/AuthTrait.php';

class InscriptionController extends Controller
{

	use FormTrait;
	use AuthTrait;

	public function create()
	{
		$errors = [];

		$idEvent = $this->getQueryParam('idEvent');

		$data = array_merge([
			'idEvent'   => $idEvent,
			'prixEvent' => $this->getQueryParam('prixEvent')
		], $this->getAllPostParams()); //Get submitted data

		$utilisateur = (new AuthService())->getUtilisateur();

		if ($utilisateur == null)
		{
			$this->redirectTo('detailEvenement.php?idEvent='.$idEvent); // Redirection si non connecté
			throw new Exception('Utilisateur non connecté');
		}

		$data['idUtilisateur'] = $utilisateur->getNetud();

		if (!empty($data))
		{
			try
			{
				

				// Validation des données
				if (empty($data['idEvent']))
				{
					$errors[] = 'L\'idEvent est requis.';
				}
				if (empty($data['idUtilisateur']))
				{
					$errors[] = 'L\'idUtilisateur est requis.';
				}
				if (empty($data['prixEvent']))
				{
					$errors[] = 'Le prix de l\'évenement est requis.';
				}

				if (!empty($errors))
				{
					throw new Exception(implode(', ', $errors));
				}

				$idUtilisateur = $data['idUtilisateur'];

				// Création de l'objet inscription
				$event       = (new EvenementRepository())->findById($idEvent);
				$inscription = new Inscription($event, $utilisateur,  0, "");

				// Sauvegarde dans la base de données
				$inscriptionRepo = new InscriptionRepository();

				// Vérifie que l'utilisateur n'est pas déjà inscrit
				if ($inscriptionRepo->findByUtilisateurAndEvenement($idUtilisateur, $idEvent) !== null)
				{
					$this->redirectTo('detailEvenement.php?idEvent='.$idEvent); // Redirection si l'utilisateur est déjà inscrit
				}
				else
				{
					if (!$inscriptionRepo->create($inscription))
					{
						throw new Exception(message: 'Erreur lors de l\'enregistrement de l\'inscription.');
					}
				}

				$this->redirectTo('detailEvenement.php?idEvent='.$idEvent); // Redirection après création
			}
			catch (Exception $e)
			{
				$this->redirectTo('detailEvenement.php?idEvent='.$idEvent); // Redirection si formulaire incorrect
			}
		}
		else
		{
			throw new Exception("Données vides");
		}
	}

	public function update()
	{
		$idEvent = $this->getQueryParam('idEvent');

		$eventRepository = new EvenementRepository();
		$evenement       = $eventRepository->findById($idEvent);

		if ($evenement === null)
			throw new Exception('Evenement non trouvé');

		$errors = [];

		$data = array_merge([
			'idEvent'     => $this->getQueryParam('idEvent'),
			'note'        => $this->getQueryParam('note'),
			'commentaire' => $this->getQueryParam('commentaire')
		], $this->getAllPostParams()); //Get submitted data

		$utilisateur = (new AuthService())->getUtilisateur();

		if ($utilisateur == null)
		{
			$this->redirectTo('detailEvenement.php?idEvent='.$idEvent); // Redirection si non connecté
			throw new Exception('Utilisateur non connecté');
		}

		$data['idUtilisateur'] = $utilisateur->getNetud();

		if (!empty($data))
		{
			try
			{
				$errors = [];

				// Validation des données
				if (empty($data['idEvent']))
				{
					$errors[] = 'L\'idEvent est requis.';
				}
				if (empty($data['idUtilisateur']))
				{
					$errors[] = 'L\'idUtilisateur est requis.';
				}
				if (empty($data['note']))
				{
					$errors[] = 'La note de l\'avis sur l\'évenement est requis.';
				}

				$note = $data['note'];
				if (!is_numeric($note))
				{
					$errors[] = 'La note doit être un nombre.';
				}
				else
				{
					// Convertir la note en entier et vérifier si elle est dans la plage
					$note = (int) $note;
					if ($note < 1 || $note > 5)
					{
						$errors[] = 'La note doit être un nombre compris entre 1 et 5';
					}
				}
				if (empty($data['commentaire']))
				{
					$errors[] = 'Le commentaire de l\'avis sur l\'évenement est requis.';
				}

				if (!empty($errors))
				{
					$this->redirectTo('detailEvenement.php?idEvent='.$idEvent); // Redirection si formulaire incorrect
					throw new Exception(implode(', ', $errors));
				}

				$idUtilisateur = $data['idUtilisateur'];


				// Mise à jour de l'objet inscription
				$inscriptionRepo = new InscriptionRepository();
				$inscription = $inscriptionRepo->findByUtilisateurAndEvenement($idUtilisateur, $idEvent);

				if ($inscription == null)
					throw new Exception('Inscription non trouvé');


				$inscription->setNote($note);
				$inscription->setCommentaire($data['commentaire']);

				// Sauvegarde dans la base de données
				if (!$inscriptionRepo->update($inscription))
				{
					throw new Exception(message: 'Erreur lors de l\'enregistrement de l\'avis.');
				}

				$this->redirectTo('detailEvenement.php?idEvent='.$idEvent); // Redirection après mise à jour
			}
			catch (Exception $e)
			{
				$errors = explode(', ', $e->getMessage()); // Récupération des erreurs
			}
		}
		else
		{
			throw new Exception("Données vide");
		}
	}

	public function deleteAvis()
	{
		$idEvent = $this->getQueryParam('idEvent');

		$utilisateur = (new AuthService())->getUtilisateur();

		if ($utilisateur == null)
		{
			$this->redirectTo('detailEvenement.php?idEvent='.$idEvent); // Redirection si non connecté
			throw new Exception('Utilisateur non connecté');
		}

		$data = array_merge([
			'idEvent'       => $this->getQueryParam('idEvent'),
			'idUtilisateur' => $this->getQueryParam('idUtilisateur')
		], $this->getAllPostParams()); //Get submitted data

		$idUtilisateur = $data['idUtilisateur'];

		$eventRepository = new EvenementRepository();
		$evenement       = $eventRepository->findById($idEvent);

		if ($evenement === null)
			throw new Exception('Evenement non trouvé');
		
		$inscriptionRepo = new InscriptionRepository();
		$inscription = $inscriptionRepo->findByUtilisateurAndEvenement($idUtilisateur, $idEvent);

		if ($inscription == null)
			throw new Exception('Inscription non trouvéé');

		// Mise à jour en base de données
		if (!$inscriptionRepo->deleteAvisByUtilisateurAndEvenement($idUtilisateur, $idEvent))
		{
			throw new Exception('Erreur lors de la suppression de l\'inscription');
		}

		$this->redirectTo('detailEvenement.php?idEvent='.$idEvent); // Redirection après suppression
	}
}

<?php

require_once './app/core/Controller.php';
require_once './app/services/AuthService.php';
require_once './app/repositories/UtilisateurRepository.php';
require_once './app/trait/FormTrait.php';
require_once './app/trait/AuthTrait.php';

class AuthController extends Controller {
	use FormTrait;
	use AuthTrait;

	public function login() {
		$authService = new AuthService();

		// Vérifier si l'utilisateur est déjà connecté
		if ($authService->isLoggedIn()) {
			// Appeler la méthode compte() si l'utilisateur est connecté
			$this->compte();
			return;
		}

		// Récupérer les données POST nettoyées
		$postData = $this->getAllPostParams();
		$data = [];

		// Si des données sont envoyées en POST
		if (!empty($postData)) {
			$utilisateurRepository = new UtilisateurRepository();

			$utilisateur = $utilisateurRepository->findById($this->getPostParam('numero_etudiant'));

			// Vérifier si l'utilisateur existe et si le mot de passe est correct
			if ($utilisateur !== null && $this->verify($this->getPostParam('password'), $utilisateur->getMdp())) {
				$authService->setUtilisateur($utilisateur);
				$this->compte(); // Rediriger vers la méthode compte()
				return;
			}

			// Si les informations sont invalides
			$data = ['error' => 'Numéro étudiant ou mot de passe invalide'];
		}

		// Afficher la vue de connexion avec les éventuelles erreurs
		$this->view('utilisateur/login.html.twig', $data);
	}

	public function compte() {
		$authService = new AuthService();

		// Vérifier si l'utilisateur est connecté
		if (!$authService->isLoggedIn()) {
			// Rediriger vers la page de connexion si non connecté
			$this->redirectTo('login.php');
			return;
		}

		// Récupérer les informations de l'utilisateur connecté
		$utilisateur = $authService->getUtilisateur();
		$isAdmin = $authService->isAdmin();

		// Passer les données de l'utilisateur à la vue compte.html.twig
		$this->view('utilisateur/compte.html.twig', [
			'utilisateur' => $utilisateur,
			'isAdmin' => $isAdmin
		]);
	}
}

Categorie

<?php

require_once './app/core/Controller.php';
require_once './app/repositories/EvenementRepository.php';
require_once './app/repositories/RoleRepository.php';
require_once './app/repositories/InscriptionRepository.php';
require_once './app/entities/Evenement.php';
require_once './app/entities/Role.php';
require_once './app/trait/FormTrait.php';
require_once './app/trait/AuthTrait.php';
require_once './app/services/AuthService.php';

class EvenementController extends Controller
{

	use FormTrait;
	use AuthTrait;

	public function index()
	{
		$repository = new EvenementRepository();
		$evenements = $repository->findAll();

		$service = new AuthService();
		$isAdmin = $service->isAdmin();

		// Ensuite, affiche la vue
		$this->view('/evenement/gestionEvenement.html.twig', ['evenements' => $evenements, 'isAdmin' => $isAdmin]);
	}

	public function detail()
	{
		$idEvent = $this->getQueryParam('idEvent');
		$eventRepository = new EvenementRepository();

		$inscritRepository = new InscriptionRepository();
		$inscriptions = $inscritRepository->findByEvenement($idEvent);

		$evenement = $eventRepository->findById($idEvent);
		if ($evenement === null) {
			throw new Exception('Évènement non trouvé');
		}

		$moyenneAvis = $inscritRepository->moyenneAvis($idEvent);

		$service = new AuthService();
		$isAdmin = $service->isAdmin();

		$utilisateur = (new AuthService())->getUtilisateur();
		if ($utilisateur != null) {
			$this->view('/evenement/detailEvenement.html.twig', ['evenement' => $evenement, 'idEvent' => $idEvent, 'inscrits' => $inscriptions, 'utilisateur' => $utilisateur, 'isAdmin' => $isAdmin, 'moyenneAvis' => $moyenneAvis]);
		} else {
			$this->view('/evenement/detailEvenement.html.twig', ['evenement' => $evenement, 'idEvent' => $idEvent, 'inscrits' => $inscriptions, 'isAdmin' => $isAdmin, 'moyenneAvis' => $moyenneAvis]);
		}
		
	}

	public function create()
	{
		$errors = [];

		$data = $this->getAllPostParams();

		if (!empty($data)) {
			try {
				$errors = [];

				// Validation des données
				if (empty($data['nomEvent'])) {
					$errors[] = 'Le nom de l\'évènement est requis.';
				}
				if (empty($data['descEvent'])) {
					$errors[] = 'La description de l\'évènement est requis.';
				}
				if (empty($data['dateEvent'])) {
					$errors[] = 'La date de l\'évènement est requis.';
				}
				if (empty($data['lieuEvent'])) {
					$errors[] = 'Le lieu de l\'évènement est requis.';
				}
				if (empty($data['prixEvent']) || !is_numeric($data['prixEvent'])) {
					$errors[] = 'Le prix de l\'évènement est requis.';
				}
				if (empty($data['roleAutoriseMin'])) {
					$errors[] = 'Le rôle autorisé est requis.';
				}
				if (!empty($_FILES['imgEvent']['name'])) {
					$targetDir = "assets/images/evenements/";
					$targetFile = $targetDir . basename($_FILES['imgEvent']['name']);
					$imageFileType = strtolower(pathinfo($targetFile, PATHINFO_EXTENSION));

					// Vérification du type de fichier
					$allowedTypes = ['jpg', 'jpeg', 'png', 'gif'];
					if (!in_array($imageFileType, $allowedTypes)) {
						$errors[] = 'Le fichier image doit être au format JPG, JPEG, PNG ou GIF.';
					}

					// Déplacement du fichier uploadé
					if (empty($errors) && move_uploaded_file($_FILES['imgEvent']['tmp_name'], $targetFile)) {
						$imgEvent = basename($_FILES['imgEvent']['name']); // Nouveau nom de fichier
					} else {
						$errors[] = 'Erreur lors de l\'upload de l\'image.';
					}
				}
				$data['imgEvent'] = $imgEvent;

				if (!empty($errors)) {
					throw new Exception(implode(', ', $errors));
				}

				// Création de l'objet evenement
				$evenement = new Evenement(0, $data['nomEvent'], $data['descEvent'], new DateTime($data['dateEvent']), $data['lieuEvent'], (float) $data['prixEvent'], (new RoleRepository())->findByNom($data['roleAutoriseMin']), $data['imgEvent']);

				// Sauvegarde dans la base de données
				$evenementRepo = new EvenementRepository();
				if (!$evenementRepo->create($evenement)) {
					throw new Exception(message: 'Erreur lors de l\'enregistrement de l\'évènement.');
				}

				$this->redirectTo('gestionEvenement.php'); // Redirection après création
			} catch (Exception $e) {
				$errors = explode(', ', $e->getMessage()); // Récupération des erreurs
			}
		}

		// Affichage du formulaire
		$this->view('/evenement/form.html.twig', [
			'data' => $data,
			'errors' => $errors,
		]);
	}

	public function update()
	{
		$idEvent = $this->getQueryParam('idEvent');

		$repository = new EvenementRepository();
		$evenement = $repository->findById($idEvent);

		if ($evenement === null) {
			throw new Exception('Évènement non trouvé');
		}

		$data = array_merge([
			'idEvent' => $evenement->getIdEvent(),
			'nomEvent' => $evenement->getNomEvent(),
			'descEvent' => $evenement->getDescEvent(),
			'dateEvent' => $evenement->getDateEvent()->format('Y-m-d H:i:s'),
			'lieuEvent' => $evenement->getLieuEvent(),
			'prixEvent' => $evenement->getPrixEvent(),
			'roleAutoriseMin' => $evenement->getRoleAutorise()->getNomRole(),
			'imgEvent' => $evenement->getImgEvent()
		], $this->getAllPostParams()); //Get submitted data
		$errors = [];



		if ($_SERVER['REQUEST_METHOD'] === 'POST') {
			try {
				// Validation des données
				if (empty($data['nomEvent'])) {
					$errors[] = 'Le nom de l\'évènement est requis.';
				}
				if (empty($data['descEvent'])) {
					$errors[] = 'La description de l\'évènement est requis.';
				}
				if (empty($data['dateEvent'])) {
					$errors[] = 'La date de l\'évènement est requis.';
				}
				if (empty($data['lieuEvent'])) {
					$errors[] = 'Le lieu de l\'évènement est requis.';
				}
				if (empty($data['prixEvent']) || !is_numeric($data['prixEvent'])) {
					$errors[] = 'Le prix de l\'évènement est requis.';
				}
				if (empty($data['roleAutoriseMin'])) {
					$errors[] = 'Le rôle autorisé est requis.';
				}
				
				$imgEvent = $evenement->getImgEvent(); // Image existante par défaut
				if (!empty($_FILES['imgEvent']['name'])) {
					$targetDir = "assets/images/evenements/";
					$targetFile = $targetDir . basename($_FILES['imgEvent']['name']);
					$imageFileType = strtolower(pathinfo($targetFile, PATHINFO_EXTENSION));

					// Vérification du type de fichier
					$allowedTypes = ['jpg', 'jpeg', 'png', 'gif'];
					if (!in_array($imageFileType, $allowedTypes)) {
						$errors[] = 'Le fichier image doit être au format JPG, JPEG, PNG ou GIF.';
					}

					// Déplacement du fichier uploadé
					if (empty($errors) && move_uploaded_file($_FILES['imgEvent']['tmp_name'], $targetFile)) {
						$imgEvent = basename($_FILES['imgEvent']['name']); // Nouveau nom de fichier
					} else {
						$errors[] = 'Erreur lors de l\'upload de l\'image.';
					}
				}


				if (!empty($errors)) {
					throw new Exception(implode(', ', $errors));
				}

				// Mise à jour de l'objet evenement
				$evenement->setIdEvent($idEvent);
				$evenement->setNomEvent($data['nomEvent']);
				$evenement->setDescEvent($data['descEvent']);
				$evenement->setDateEvent(new DateTime($data['dateEvent']));
				$evenement->setLieuEvent($data['lieuEvent']);
				$evenement->setPrixEvent((float) $data['prixEvent']);
				$evenement->setRoleAutorise((new RoleRepository())->findByNom($data['roleAutoriseMin']));
				$evenement->setImgEvent($imgEvent);


				// Sauvegarde dans la base de données
				if (!$repository->update($evenement)) {
					throw new Exception('Erreur lors de la mise à jour du évènement.');
				}

				$this->redirectTo('gestionEvenement.php'); // Redirection après mise à jour
			} catch (Exception $e) {
				$errors = explode(', ', $e->getMessage()); // Récupération des erreurs
			}
		}

		// Affichage du formulaire de mise à jour
		$this->view('/evenement/form.html.twig', ['data' => $data, 'errors' => $errors, 'idEvent' => $idEvent]);
	}

	public function delete()
	{
		$idEvent = $this->getQueryParam('idEvent');

		$repository = new EvenementRepository();
		$evenement = $repository->findById($idEvent);

		if ($evenement === null) {
			throw new Exception('Évènement non trouvé');
		}

		try {
			if (!$repository->deleteById($evenement->getIdEvent())) {
				throw new Exception('Erreur lors de la suppression du évènement.');
			}

			$this->redirectTo('gestionEvenement.php'); // Redirection après suppression
		} catch (Exception $e) {
			$this->view('/evenement/gestionEvenement.html.twig', [
				'errors' => [$e->getMessage()]
			]);
		}
	}
}
