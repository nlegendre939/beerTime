<?php
namespace App\Controller;

use App\Entity\Event;
use App\Entity\User;
use App\Form\EventType;
use App\Repository\EventRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/event', name: 'event_')]
class EventController extends AbstractController
{
    private $em;
    private $eventRepository;

    public function __construct(EntityManagerInterface $em, EventRepository $eventRepository) /*plusieurs services dans le constructeur : injecter un service dans un service */
    {
        $this->em = $em;
        $this->eventRepository = $eventRepository;
    }

    #[Route('', name: 'list')]
    public function list(): Response
    {
        $events = $this->eventRepository->findAll(); /* findAll() : pour tout réclamer (récupérer les différents events) */

        return $this->render('event/list.html.twig', [ /* render : accès au template event/list */
            'events' => $events, /* option : associe template (twig events) à $events (variable qui contient les données) */
        ]);
    }

    #[Route('/{id}', name: 'show', requirements: ['id' => '\d+'])] /* 'd+' : entier positif */
    public function show($id): Response
    {   /* Afficher un événement */
        $event = $this->eventRepository->find($id);

        return $this->render('event/show.html.twig', [
            'event' => $event,
        ]);
    }

    #[Route('/new', name: 'new')]
    public function new(Request $request): Response
    {
        $event = new Event();
        $form = $this->createForm(EventType::class, $event); 
        /* EventType::class : donne tout le chemin vers la classe EventType (créer un formulaire de type EventType) 
        et $event pour connecter le formulaire au modèle de données) */
        
        $form->handleRequest($request); // handleRequest : récupère les données / équivalent php : $username = $_POST['username]
        
        if(($form->isSubmitted()) && $form->isValid()) 
        {
            // TODO - Remplacer par l'utilisateur connecté
            $this->em->getRepository(User::class)->find(1); // récupérer l'utilisateur avec l'id 1 et le définir
            $event->SetOwner($user); // pour définir le propriétaire avec l'utilisateur qu'on vient de récupérer

            $this->em->persist($event); // persist : pour suivre le nouvel objet $event
            $this->em->flush(); // flush : appliquer les modifications en base de données
        }
        return $this->render('event/form.html.twig', [
            'form' => $form->createView(), /* createView() : prépare l'affichage du formulaire */
        ]);
    }
}
