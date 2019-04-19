<?php

namespace AppBundle\Controller\EnMarche\AdherentMessage;

use AppBundle\AdherentMessage\AdherentMessageDataObject;
use AppBundle\AdherentMessage\AdherentMessageFactory;
use AppBundle\AdherentMessage\AdherentMessageStatusEnum;
use AppBundle\AdherentMessage\AdherentMessageTypeEnum;
use AppBundle\AdherentMessage\Filter\FilterFormFactory;
use AppBundle\Entity\Adherent;
use AppBundle\Entity\AdherentMessage\AbstractAdherentMessage;
use AppBundle\Entity\AdherentMessage\Filter\CitizenProjectFilter;
use AppBundle\Entity\CitizenProject;
use AppBundle\Form\AdherentMessage\AdherentMessageType;
use AppBundle\Mailchimp\Manager;
use AppBundle\Repository\AdherentMessageRepository;
use Doctrine\Common\Persistence\ObjectManager;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * @Route(path="/espace-porteur-projet/{citizen_project_slug}/messagerie", name="app_message_citizen_project_")
 *
 * @ParamConverter("citizenProject", options={"mapping": {"citizen_project_slug": "slug"}})
 *
 * @Security("is_granted('ADMINISTRATE_CITIZEN_PROJECT', citizenProject)")
 */
class CitizenProjectMessageController extends AbstractMessageController
{
    /**
     * @Route(name="list", methods={"GET"})
     *
     * @param Adherent|UserInterface $adherent
     */
    public function messageListAction(
        Request $request,
        UserInterface $adherent,
        AdherentMessageRepository $repository,
        CitizenProject $citizenProject = null
    ): Response {
        $this->disableInProduction();

        $status = $request->query->get('status');

        if ($status && !AdherentMessageStatusEnum::isValid($status)) {
            throw new BadRequestHttpException('Invalid status');
        }

        return $this->renderTemplate('message/list.html.twig', [
            'messages' => $repository->findAllCitizenProjectMessage($adherent, $citizenProject, $status),
            'citizen_project' => $citizenProject,
            'route_params' => ['citizen_project_slug' => $citizenProject->getSlug()],
            'message_filter_status' => $status,
        ]);
    }

    /**
     * @Route("/creer", name="create", methods={"GET", "POST"})
     *
     * @param Adherent|UserInterface $adherent
     */
    public function createMessageAction(
        Request $request,
        UserInterface $adherent,
        ObjectManager $manager,
        MessageBusInterface $bus,
        CitizenProject $citizenProject = null
    ): Response {
        $this->disableInProduction();

        $form = $this
            ->createForm(AdherentMessageType::class)
            ->handleRequest($request)
        ;

        if ($form->isSubmitted() && $form->isValid()) {
            $message = AdherentMessageFactory::create($adherent, $form->getData(), $this->getMessageType());
            $message->setFilter(new CitizenProjectFilter($citizenProject));

            $manager->persist($message);

            $manager->flush();

            $this->addFlash('info', 'adherent_message.created_successfully');

            if ($form->get('next')->isClicked()) {
                return $this->redirectToMessageRoute('filter', [
                    'uuid' => $message->getUuid()->toString(),
                    'citizen_project_slug' => $citizenProject->getSlug(),
                ]);
            }

            return $this->redirectToMessageRoute('update', [
                'uuid' => $message->getUuid(),
                'citizen_project_slug' => $citizenProject->getSlug(),
            ]);
        }

        return $this->renderTemplate('message/create.html.twig', [
            'form' => $form->createView(),
            'citizen_project' => $citizenProject,
            'route_params' => ['citizen_project_slug' => $citizenProject->getSlug()],
        ]);
    }

    /**
     * @Route("/{uuid}/modifier", requirements={"uuid": "%pattern_uuid%"}, name="update", methods={"GET", "POST"})
     *
     * @Security("is_granted('IS_AUTHOR_OF', message)")
     */
    public function updateMessageAction(
        Request $request,
        AbstractAdherentMessage $message,
        ObjectManager $manager,
        CitizenProject $citizenProject = null
    ): Response {
        $this->disableInProduction();

        if ($message->isSent()) {
            throw new BadRequestHttpException('This message has already been sent.');
        }

        $form = $this
            ->createForm(AdherentMessageType::class, $dataObject = AdherentMessageDataObject::createFromEntity($message))
            ->handleRequest($request)
        ;

        if ($form->isSubmitted() && $form->isValid()) {
            $message->updateFromDataObject($dataObject);

            $manager->flush();

            $this->addFlash('info', 'adherent_message.updated_successfully');

            if ($form->get('next')->isClicked()) {
                return $this->redirectToMessageRoute('filter', [
                    'uuid' => $message->getUuid()->toString(),
                    'citizen_project_slug' => $citizenProject->getSlug(),
                ]);
            }

            return $this->redirectToMessageRoute('update', [
                'uuid' => $message->getUuid(),
                'citizen_project_slug' => $citizenProject->getSlug(),
            ]);
        }

        return $this->renderTemplate('message/update.html.twig', [
            'form' => $form->createView(),
            'citizen_project' => $citizenProject,
            'route_params' => ['citizen_project_slug' => $citizenProject->getSlug()],
        ]);
    }

    /**
     * @Route("/{uuid}/filtrer", name="filter", methods={"GET"})
     *
     * @Security("is_granted('IS_AUTHOR_OF', message)")
     */
    public function filterMessageAction(
        Request $request,
        AbstractAdherentMessage $message,
        FilterFormFactory $formFactory,
        ObjectManager $manager,
        CitizenProject $citizenProject = null
    ): Response {
        $this->disableInProduction();

        if ($message->isSent()) {
            throw new BadRequestHttpException('This message has already been sent.');
        }

        return $this->renderTemplate('message/filter/citizen_project.html.twig', [
            'message' => $message,
            'citizen_project' => $citizenProject,
            'route_params' => ['citizen_project_slug' => $citizenProject->getSlug()],
        ]);
    }

    /**
     * @Route("/{uuid}/visualiser", name="preview", methods={"GET"})
     *
     * @Security("is_granted('IS_AUTHOR_OF', message)")
     */
    public function previewMessageAction(
        AbstractAdherentMessage $message,
        CitizenProject $citizenProject = null
    ): Response {
        $this->disableInProduction();

        if (!$message->isSynchronized()) {
            throw new BadRequestHttpException('Message preview is not ready yet.');
        }

        return $this->renderTemplate('message/preview.html.twig', [
            'message' => $message,
            'citizen_project' => $citizenProject,
            'route_params' => ['citizen_project_slug' => $citizenProject->getSlug()],
        ]);
    }

    /**
     * @Route("/{uuid}/content", name="content", methods={"GET"})
     *
     * @Security("is_granted('IS_AUTHOR_OF', message)")
     */
    public function getMessageTemplateAction(
        AbstractAdherentMessage $message,
        Manager $manager,
        CitizenProject $citizenProject = null
    ): Response {
        $this->disableInProduction();

        return new Response($manager->getCampaignContent($message));
    }

    /**
     * @Route("/{uuid}/supprimer", name="delete", methods={"GET"})
     *
     * @Security("is_granted('IS_AUTHOR_OF', message)")
     */
    public function deleteMessageAction(
        AbstractAdherentMessage $message,
        ObjectManager $manager,
        CitizenProject $citizenProject = null
    ): Response {
        $this->disableInProduction();

        $manager->remove($message);
        $manager->flush();

        $this->addFlash('info', 'adherent_message.deleted_successfully');

        return $this->redirectToMessageRoute('list', ['citizen_project_slug' => $citizenProject->getSlug()]);
    }

    /**
     * @Route("/{uuid}/send", name="send", methods={"GET"})
     *
     * @Security("is_granted('IS_AUTHOR_OF', message)")
     */
    public function sendMessageAction(
        AbstractAdherentMessage $message,
        Manager $manager,
        ObjectManager $entityManager,
        CitizenProject $citizenProject = null
    ): Response {
        $this->disableInProduction();

        if (!$message->isSynchronized()) {
            throw new BadRequestHttpException('The message is not ready to send yet.');
        }

        if (!$message->getRecipientCount()) {
            throw new BadRequestHttpException('Your message should have a filter');
        }

        if ($message->isSent()) {
            throw new BadRequestHttpException('This message has already been sent.');
        }

        if ($manager->sendCampaign($message)) {
            $message->markAsSent();
            $entityManager->flush();

            $this->addFlash('info', 'adherent_message.campaign_sent_successfully');
        } else {
            $this->addFlash('info', 'adherent_message.campaign_sent_failure');
        }

        return $this->redirectToMessageRoute('list', ['citizen_project_slug' => $citizenProject->getSlug()]);
    }

    protected function getMessageType(): string
    {
        return AdherentMessageTypeEnum::CITIZEN_PROJECT;
    }
}
