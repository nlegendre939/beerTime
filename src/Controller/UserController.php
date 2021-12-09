<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\UserType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[Route('', name: 'user_')]
class UserController extends AbstractController
{
    private $em;
    private $hasher;

    public function __construct(EntityManagerInterface $em, UserPasswordHasherInterface $hasher)
    {
        $this->em = $em;
        $this->hasher = $hasher;
    }

    #[Route('/register', name: 'register')]
    public function register(Request $request): Response /* injecter l'objet Request (injection de dépendance) $request : donnée dans la requête request */
    {
        if($this->getUser()){
            return $this->disallowAccess();
        }
        
        $user = new User(); /* Nouvel utilisateur */
        $form = $this->createForm(UserType::class, $user); /* createForm : Nouveau formulaire => UserType : type de formulaire et $user : les données qui y seront associées */

        $form->handleRequest($request); /* lire la requête et remplire le formulaire via $user (utilisateur remplit le formulaire : user email, user password, etc...) */
        if($form->isSubmitted() && $form->isValid()){
            $hashed = $this->hasher->hashPassword($user, $user->getPlainPassword()); /* $user->getPlainPassword() : pour accéder au mot de passe utilisateur */
            $user->setPassword($hashed);
            
            $this->em->persist($user); /* persist : pour un nouvel utilisateur */
            $this->em->flush(); /* flush : toutes les entités modfiées sont engeristrées en base de données */

            $this->addFlash('notice', 'Votre compte à bien été créé, vous pouvez dés à présent vous connecter'); /* addFlash disponible uniquement dans un controller */
            return $this->redirectToRoute('main_index');
        }

        return $this->render('user/register.html.twig', [
            'form' => $form->createView(), /* Créer la vue => 'form' : variable côté twig */
        ]);
    }
    #[Route('/login', name: 'login')]
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        if($this->getUser()){
            return $this->disallowAccess();
        }
        /* authenticationUtils : Remonter un message d'erreur*/
        $error = $authenticationUtils->getLastAuthenticationError();

        return $this->render('user/login.html.twig', [
            'error' => $error,
        ]);
    }

    #[Route('/logout', name: 'logout')]
    public function logout(){}

    private function disallowAccess(): Response
    {
        $this->addFlash('info', 'Vous êtes déjà connecté, déconnectez vous pour changer de compte');
        return $this->redirectToRoute('main_index');
    }
}