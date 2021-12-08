<?php
namespace App\Controller;

use App\Entity\User;
use App\Entity\Event;
use App\Form\EventType;
use App\Repository\EventRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

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
    #[Route('/{id}/edit', name: 'edit', requirements: ['id' => '\d+'])]
    #[IsGranted('ROLE_ORGANIZER')]
    #[IsGranted('EVENT_FORM', subject: 'event')]
    public function form(Request $request, Event $event = null): Response
    {
        if($event){
            $isNew = false;
        }else{
            $event = new Event();
            $isNew = true;
        }

        $form = $this->createForm(EventType::class, $event); 
        /* EventType::class : donne tout le chemin vers la classe EventType (créer un formulaire de type EventType) 
        et $event pour connecter le formulaire au modèle de données) */
        
        $form->handleRequest($request); // handleRequest : récupère les données / équivalent php : $username = $_POST['username]
        
        if(($form->isSubmitted()) && $form->isValid()) 
        {
            // TODO - Remplacer par l'utilisateur connecté
            $event->setOwner($this->getUser());

            $this->em->persist($event); // persist : pour suivre le nouvel objet $event
            $this->em->flush(); // flush : appliquer les modifications en base de données

            $message = sprintf('Votre événement à bien été %s', $isNew ? 'créé' : 'modifié');
            $this->addFlash('notice', 'Votre événement à bien été créé'); // notification
            return $this->redirectToRoute('event_show', [
                'id' => $event->getId(),
            ]);
        }
        return $this->render('event/form.html.twig', [
            'form' => $form->createView(), /* createView() : prépare l'affichage du formulaire */
            'isNew' => $isNew
        ]);
    }
}
