<?php
namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/event', name: 'event_')]
class EventController extends AbstractController
{

    #[Route('', name: 'list')]
    public function event(): Response
    {
        return $this->render('event/list.html.twig', [
            'controller_name' => 'EventController',
        ]);
    }

    public function list(): Response
    {
        return new Response("Page liste d'événements");
    }

    #[Route('/{id}', name: 'show', requirements: ['id' => '\d+'])]
    public function show($id): Response
    {
        return new Response("Page vu d'un événement : " . $id);
    }

    #[Route('/new', name: 'new')]
    public function new(): Response
    {
        return new Response("Page de création d'un événement");
    }
}
