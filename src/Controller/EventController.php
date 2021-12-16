<?php
namespace App\Controller;

use App\Entity\User;
use App\Entity\Event;
use App\Entity\Booking;
use App\Form\EventType;
use App\Form\SearchEventType;
use App\Service\MediaService;
use App\Service\PaymentService;
use Symfony\Component\Uid\Uuid;
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
    private $mediaService;
    private $paymentService;

    public function __construct(
        EntityManagerInterface $em, 
        EventRepository $eventRepository,
        MediaService $mediaService,
        PaymentService $paymentService /*plusieurs services dans le constructeur : injecter un service dans un service */
    ){
        $this->em = $em;
        $this->eventRepository = $eventRepository;
        $this->mediaService = $mediaService;
        $this->paymentService = $paymentService;
    }

    #[Route('', name: 'list')]
    public function list(Request $request): Response
    {
        $searchForm = $this->createForm(SearchEventType::class);
        $searchForm->handleRequest($request);
        $searchCriteria = $searchForm->getData();

        $events = $this->eventRepository->search($searchCriteria);

        return $this->render('event/list.html.twig', [ /* render : accès au template event/list */
            'events' => $events, /* option : associe template (twig events) à $events (variable qui contient les données) */
            'searchForm' => $searchForm->createView(),
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
    #[IsGranted('EVENT_FORM', subject: 'event')]
    public function form(Request $request, Event $event = null): Response
    {
        if($event){
            $isNew = false;
        }else{
            $event = new Event();
            $event->setOwner($this->getUser());
            $isNew = true;
        }

        $form = $this->createForm(EventType::class, $event); 
        /* EventType::class : donne tout le chemin vers la classe EventType (créer un formulaire de type EventType) 
        et $event pour connecter le formulaire au modèle de données) */
        
        $form->handleRequest($request); // handleRequest : récupère les données / équivalent php : $username = $_POST['username]
        
        if(($form->isSubmitted()) && $form->isValid()) 
        {
            $this->mediaService->handleEvent($event);
            $this->em->persist($event); // persist : pour suivre le nouvel objet $event
            $this->em->flush(); // flush : appliquer les modifications en base de données

            $message = sprintf('Votre événement à bien été %s', $isNew ? 'créé' : 'modifié');
            $this->addFlash('notice', $message); // notification
            return $this->redirectToRoute('event_show', [
                'id' => $event->getId(),
            ]);
        }
        return $this->render('event/form.html.twig', [
            'form' => $form->createView(), /* createView() : prépare l'affichage du formulaire */
            'isNew' => $isNew
        ]);
    }

    #[Route('/{id}/booking', name: 'booking', requirements: ['id' => '\d+'])]
    #[IsGranted('BOOK_EVENT', subject: 'event')]
    public function booking(Request $request, Event $event): Response
    {
        // isset($_GET['payment_intent'])
        if($request->query->has('payment_intent')){
            $paymentIntentId = $request->query->get('payment_intent');

            if(!$event->getPrice() || $this->paymentService->checkPaymentIntent($paymentIntentId)){
                $booking = new Booking();
                $booking->setEvent($event);
                $booking->setUser($this->getUser());
                $booking->setReference(Uuid::v4());

                $this->em->persist($booking);
                $this->em->flush();

                return $this->redirectToRoute('booking_confirmation', [
                    'reference' => $booking->getReference(),
                ]);
            }
        }

        if($event->getPrice()){
            $paymentIntent = $this->paymentService->createPaymentIntent($event->getPrice());
        }

        return $this->render('event/booking.html.twig', [
            'event' => $event,
            'paymentPublicKey' => $this->paymentService->getPublicKey(),
            'paymentIntentSecret' => isset($paymentIntent) ? $paymentIntent->client_secret : '',
        ]);
    }
}