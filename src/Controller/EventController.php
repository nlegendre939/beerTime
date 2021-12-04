<?php
namespace App\Controller;

use App\Repository\EventRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/event', name: 'event_')]
class EventController extends AbstractController
{
    private $eventRepository;

    public function __construct(EventRepository $eventRepository)
    {
        $this->eventRepository = $eventRepository;
    }

    #[Route('', name: 'list')]
    public function list(): Response
    {
        $events = $this->eventRepository->findAll(); /* findAll() : pour tout réclamer (récupérer les différents events) */

        return $this->render('event/list.html.twig', [ /* render : accès au template event/list */
            'events' => $events /* option : associe template (twig events) à $events (variable qui contient les données) */
        ]);
    }

    #[Route('/{id}', name: 'show', requirements: ['id' => '\d+'])] /* 'd+' : entier positif */
    public function show($id): Response
    {   /* Afficher un événement */
        $event = $this->eventRepository->find($id);

        return $this->render('event/show.html.twig', [
            'event' => $event
        ]);
    }

    #[Route('/new', name: 'new')]
    public function new(): Response
    {
        return new Response("Page de création d'un événement");
    }
}
