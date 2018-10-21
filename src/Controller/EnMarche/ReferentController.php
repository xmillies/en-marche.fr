<?php

namespace AppBundle\Controller\EnMarche;

use AppBundle\Controller\CanaryControllerTrait;
use AppBundle\Entity\Jecoute\Survey;
use AppBundle\Entity\Projection\ReferentManagedUser;
use AppBundle\Entity\ReferentOrganizationalChart\PersonOrganizationalChartItem;
use AppBundle\Entity\ReferentOrganizationalChart\ReferentPersonLink;
use AppBundle\Event\EventCommand;
use AppBundle\Event\EventRegistrationCommand;
use AppBundle\Form\EventCommandType;
use AppBundle\Form\ReferentMessageType;
use AppBundle\Form\ReferentPersonLinkType;
use AppBundle\Form\Jecoute\SurveyFormType;
use AppBundle\Referent\ManagedCommitteesExporter;
use AppBundle\Referent\ManagedEventsExporter;
use AppBundle\Referent\ManagedUsersFilter;
use AppBundle\Referent\ReferentMessage;
use AppBundle\Referent\ReferentMessageNotifier;
use AppBundle\Referent\SurveyExporter;
use AppBundle\Repository\CommitteeRepository;
use AppBundle\Repository\EventRepository;
use AppBundle\Repository\ReferentOrganizationalChart\OrganizationalChartItemRepository;
use AppBundle\Repository\ReferentOrganizationalChart\ReferentPersonLinkRepository;
use AppBundle\Repository\ReferentRepository;
use AppBundle\Repository\SurveyRepository;
use Doctrine\Common\Persistence\ObjectManager;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * @Route("/espace-referent")
 * @Security("is_granted('ROLE_REFERENT')")
 */
class ReferentController extends Controller
{
    public const TOKEN_ID = 'referent_managed_users';

    use CanaryControllerTrait;

    /**
     * @Route("/utilisateurs", name="app_referent_users")
     * @Method("GET")
     */
    public function usersAction(Request $request): Response
    {
        $filter = new ManagedUsersFilter();
        $filter->handleRequest($request);

        if ($filter->hasToken() && !$this->isCsrfTokenValid(self::TOKEN_ID, $filter->getToken())) {
            return $this->redirectToRoute('app_referent_users');
        }

        $repository = $this->getDoctrine()->getRepository(ReferentManagedUser::class);
        $results = $repository->search($this->getUser(), $filter->hasToken() ? $filter : null);

        $filter->setToken($this->get('security.csrf.token_manager')->getToken(self::TOKEN_ID));

        return $this->render('referent/users_list.html.twig', [
            'filter' => $filter,
            'has_filter' => $request->query->has(ManagedUsersFilter::PARAMETER_TOKEN),
            'results_count' => $results->count(),
            'results' => $results->getQuery()->getResult(),
        ]);
    }

    /**
     * @Route("/utilisateurs/message", name="app_referent_users_message")
     * @Method("GET|POST")
     */
    public function usersSendMessageAction(Request $request): Response
    {
        $filter = new ManagedUsersFilter();
        $filter->handleRequest($request);

        if ($filter->hasToken() && !$this->isCsrfTokenValid(self::TOKEN_ID, $filter->getToken())) {
            return $this->redirectToRoute('app_referent_users');
        }

        $message = ReferentMessage::create($this->getUser(), $filter);

        $form = $this->createForm(ReferentMessageType::class, $message);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->get(ReferentMessageNotifier::class)->sendMessage($message);
            $this->addFlash('info', 'referent.message.success');

            return $this->redirect($this->generateUrl('app_referent_users').$filter);
        }

        $repository = $this->getDoctrine()->getRepository(ReferentManagedUser::class);
        $results = $repository->search($this->getUser(), $filter->hasToken() ? $filter : null);

        return $this->render('referent/users_message.html.twig', [
            'filter' => $filter,
            'results_count' => $results->count(),
            'message' => $message,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/evenements", name="app_referent_events")
     * @Method("GET")
     */
    public function eventsAction(EventRepository $eventRepository, ManagedEventsExporter $eventsExporter): Response
    {
        return $this->render('referent/events_list.html.twig', [
            'managedEventsJson' => $eventsExporter->exportAsJson($eventRepository->findManagedBy($this->getUser())),
        ]);
    }

    /**
     * @Route("/evenements/creer", name="app_referent_events_create")
     * @Method("GET|POST")
     */
    public function eventsCreateAction(Request $request): Response
    {
        $command = new EventCommand($this->getUser());
        $form = $this->createForm(EventCommandType::class, $command);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $event = $this->get('app.event.handler')->handle($command);

            $registrationCommand = new EventRegistrationCommand($event, $this->getUser());
            $this->get('app.event.registration_handler')->handle($registrationCommand);

            $this->addFlash('info', 'referent.event.creation.success');

            return $this->redirectToRoute('app_event_show', [
                'slug' => $event->getSlug(),
            ]);
        }

        return $this->render('referent/event_create.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/comites", name="app_referent_committees")
     * @Method("GET")
     */
    public function committeesAction(CommitteeRepository $committeeRepository, ManagedCommitteesExporter $committeesExporter): Response
    {
        return $this->render('referent/committees_list.html.twig', [
            'managedCommitteesJson' => $committeesExporter->exportAsJson($committeeRepository->findManagedBy($this->getUser())),
        ]);
    }

    /**
     * @Route("/questionnaires", name="app_referent_surveys")
     * @Method("GET")
     */
    public function jecouteSurveysListAction(
        SurveyRepository $surveyRepository,
        SurveyExporter $surveyExporter,
        UserInterface $user
    ): Response {
        $this->disableInProduction();

        return $this->render('referent/surveys/list.html.twig', [
            'surveysListJson' => $surveyExporter->exportAsJson(
                $surveyRepository->findAllPublishedByCreator($user)
            ),
        ]);
    }

    /**
     * @Route("/questionnaire/creer", name="app_referent_survey_create")
     * @Method("GET|POST")
     */
    public function jecouteSurveyCreateAction(
        Request $request,
        ObjectManager $manager,
        UserInterface $user
    ): Response {
        $this->disableInProduction();

        $form = $this
            ->createForm(SurveyFormType::class, new Survey($user))
            ->handleRequest($request)
        ;

        if ($form->isSubmitted() && $form->isValid()) {
            $manager->persist($form->getData());
            $manager->flush();

            $this->addFlash('info', 'survey.create.success');
        }

        return $this->render('referent/surveys/create.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route(
     *     path="/questionnaire/{uuid}/editer",
     *     name="app_referent_survey_edit",
     *     requirements={
     *         "uuid": "%pattern_uuid%",
     *     }
     * )
     * @Method("GET|POST")
     */
    public function jecouteSurveyEditAction(Request $request, Survey $survey, ObjectManager $manager): Response
    {
        $this->disableInProduction();

        $form = $this
            ->createForm(SurveyFormType::class, $survey)
            ->handleRequest($request)
        ;

        if ($form->isSubmitted() && $form->isValid()) {
            $manager->flush();

            $this->addFlash('info', 'survey.edit.success');
        }

        return $this->render('referent/surveys/create.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/organigramme", name="app_referent_organizational_chart")
     * @Security("is_granted('IS_ROOT_REFERENT')")
     */
    public function organizationalChartAction(OrganizationalChartItemRepository $organizationalChartItemRepository, ReferentRepository $referentRepository)
    {
        return $this->render('referent/organizational_chart.html.twig', [
            'organization_chart_items' => $organizationalChartItemRepository->getRootNodes(),
            'referent' => $referentRepository->findOneByEmailAndSelectPersonOrgaChart($this->getUser()->getEmailAddress()),
        ]);
    }

    /**
     * @Route("/organigramme/{id}", name="app_referent_referent_person_link_edit")
     * @Security("is_granted('IS_ROOT_REFERENT')")
     */
    public function editReferentPersonLink(Request $request, ReferentPersonLinkRepository $referentPersonLinkRepository, ReferentRepository $referentRepository, PersonOrganizationalChartItem $personOrganizationalChartItem)
    {
        $form = $this->createForm(
            ReferentPersonLinkType::class,
            $referentPersonLinkRepository->findOrCreateByOrgaItemAndReferent(
                $personOrganizationalChartItem,
                $referentRepository->findOneByEmail($this->getUser()->getEmailAddress())
            )
        );
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var ReferentPersonLink $referentPersonLink */
            $referentPersonLink = $form->getData();

            $em = $this->getDoctrine()->getManager();

            $em->persist($referentPersonLink);
            $em->flush();

            $this->addFlash('success', 'Organigramme mis à jour.');

            return $this->redirectToRoute('app_referent_organizational_chart');
        }

        return $this->render('referent/edit_referent_person_link.html.twig', [
            'form_referent_person_link' => $form->createView(),
            'person_organizational_chart_item' => $personOrganizationalChartItem,
        ]);
    }
}
