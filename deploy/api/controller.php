<?php
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

	/*function optionsCatalogue (Request $request, Response $response, $args) {
	    
	    // Evite que le front demande une confirmation à chaque modification
	    $response = $response->withHeader("Access-Control-Max-Age", 600);
	    
	    return addHeaders ($response);
	}
*/
	function  getSearchCalatogue (Request $request, Response $response, $args) {
	    $filtre = $args['filtre'];
	    $flux = '[{"titre":"linux","ref":"001","prix":"20"},{"titre":"java","ref":"002","prix":"21"},{"titre":"windows","ref":"003","prix":"22"},{"titre":"angular","ref":"004","prix":"23"},{"titre":"unix","ref":"005","prix":"25"},{"titre":"javascript","ref":"006","prix":"19"},{"titre":"html","ref":"007","prix":"15"},{"titre":"css","ref":"008","prix":"10"}]';
	   
	    if ($filtre) {
	      $data = json_decode($flux, true); 
	    	
		$res = array_filter($data, function($obj) use ($filtre)
		{ 
		    return strpos($obj["titre"], $filtre) !== false;
		});
		$response->getBody()->write(json_encode(array_values($res)));
	    } else {
		 $response->getBody()->write($flux);
	    }

	    return addHeaders ($response);
	}

	// API Nécessitant un Jwt valide -------------------------Attention faut le remettre dans le middleware
	function getCatalogue(Request $request, Response $response, $args)
	{
	global $entityManager;

	try {
		$produitRepository = $entityManager->getRepository('Produit');
		$produits = $produitRepository->findAll();

		$data = [];
		foreach ($produits as $produit) {
			$data[] = [
				'id' => $produit->getId(),
				'name' => $produit->getName(),
				'description' => $produit->getDescription(),
				'price' => $produit->getPrice(),
			];
		}
		$response->getBody()->write(json_encode($data));
		$errorData = ['error' => 'Internal Server Error'];
		return $response->withHeader('Content-Type', 'application/json');

	} catch (Exception $e) {
			$errorData = ['error' => 'Internal Server Error', 'message' => $e->getMessage()];
		$response->getBody()->write(json_encode($errorData));
		return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
	}
	}
	/*function optionsUtilisateur (Request $request, Response $response, $args) {
	    
	    // Evite que le front demande une confirmation à chaque modification
	    $response = $response->withHeader("Access-Control-Max-Age", 600);
	    
	    return addHeaders ($response);
	}*/

	// API Nécessitant un Jwt valide
	function getUtilisateur (Request $request, Response $response, $args) {
	    global $entityManager;
	    
	    $payload = getJWTToken($request);
	    $login  = $payload->userid;
	    
	    $utilisateurRepository = $entityManager->getRepository('Utilisateurs');
	    $utilisateur = $utilisateurRepository->findOneBy(array('login' => $login));
	    if ($utilisateur) {
		$data = array('nom' => $utilisateur->getNom(), 'prenom' => $utilisateur->getPrenom());
		$response = addHeaders ($response);
		$response = createJwT ($response);
		$response->getBody()->write(json_encode($data));
	    } else {
		$response = $response->withStatus(404);
	    }

	    return addHeaders ($response);
	}

	// APi d'authentification générant un JWT
	function LoginUser(Request $request, Response $response, $args)
	{
		global $entityManager;
		$err = false;
		$body = json_decode($request->getBody(), true);
		$login = $body['login'] ?? "";
		$pass = $body['password'] ?? "";
	
		if (!preg_match("/[a-zA-Z0-9]{1,20}/", $login)) {
			$err = true;
		}
		if (!preg_match("/[a-zA-Z0-9]{1,20}/", $pass)) {
			$err = true;
		}
		if (!$err) {
			$utilisateurRepository = $entityManager->getRepository('Utilisateurs');
			$utilisateur = $utilisateurRepository->findOneBy(array('login' => $login, 'password' => $pass));

			if ($utilisateur && $login == $utilisateur->getLogin() && $pass == $utilisateur->getPassword()) {
				
				$response = createJwt($response, $utilisateur->getId(), $utilisateur->getEmail());
				return addHeaders($response)->withStatus(200);
			} else {
				// Authentification invalide
				$response = $response->withStatus(401);
				$errorData = ['error' => 'Authentication Failed', 'message' => 'Identifiants incorrects'];
				$response->getBody()->write(json_encode($errorData));
			}
		} else {
			// Saisie invalide
			$response = $response->withStatus(400);
			$errorData = ['error' => 'Invalid Input', 'message' => 'Votre saisie est invalide'];
			$response->getBody()->write(json_encode($errorData));
		}
	
		return addHeaders($response);
	}

	function CreateUser(Request $request, Response $response)
	{
		global $entityManager;
	
		$body = json_decode($request->getBody(), true);
	
		// Extraction et validation des données
		$nom = $body['nom'] ??"";
		$prenom = $body['prenom'] ??"";
		$adresse = $body['adresse'] ??"";
		$codepostal = $body['codepostal'] ??"";
		$ville = $body['ville'] ??"";
		$email = $body['email'] ??"";
		$sexe = $body['sexe'] ??"";
		$login = $body['login'] ??"";
		$password = $body['password'] ??"";
		$telephone = $body['telephone'] ??"";

		if (empty($nom) || empty($prenom) || empty($adresse) || empty($codepostal) ||
			empty($ville) || empty($email) || empty($sexe) || empty($login) || 
			empty($password) || empty($telephone)) {

			$errorData = ['error' => 'Validation Error', 'message' => 'Les champs obligatoires ne sont pas tous remplis'];
			$response->getBody()->write(json_encode($errorData));
			return $response->withHeader('Content-Type', 'application/json')
							->withStatus(400);
			}
		try {
			$user = new Utilisateurs;
			$user->setNom($nom);
			$user->setPrenom($prenom);
			$user->setAdresse($adresse);
			$user->setCodepostal($codepostal);
			$user->setVille($ville);
			$user->setEmail($email);
			$user->setSexe($sexe);
			$user->setLogin($login);
			$user->setPassword($password);
			$user->setTelephone($telephone);
			$entityManager->persist($user);
			$entityManager->flush();
	
			// Préparation des données de succès
			$successData = ['message' => 'Utilisateur créé avec succès'];
			// Écriture des données de succès dans le corps de la réponse
			$response->getBody()->write(json_encode($successData));
			// Ajout de l'en-tête 'Content-Type' et modification du statut HTTP
			return $response->withHeader('Content-Type', 'application/json')
							->withStatus(200);
		} catch (Exception $e) {
			// Gestion des exceptions
			$errorData = ['error' => 'Internal Server Error', 'message' => $e->getMessage()];
			// Écriture des données d'erreur dans le corps de la réponse
			$response->getBody()->write(json_encode($errorData));
			// Ajout de l'en-tête 'Content-Type' et modification du statut HTTP
			return $response->withHeader('Content-Type', 'application/json')
							->withStatus(500);
		}
	}