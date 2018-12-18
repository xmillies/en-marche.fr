<?php

namespace AppBundle\Controller\EnMarche;

use AppBundle\Entity\Message;
use AppBundle\Form\MessageType;
use AppBundle\Mailchimp\Synchronisation\Manager;
use AppBundle\Repository\MessageRepository;
use Doctrine\Common\Persistence\ObjectManager;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * @Route("/messages")
 * @Security("is_granted('ROLE_MESSAGE_COM')")
 */
class MessageController extends AbstractController
{
    /**
     * @Route(name="app_message_list", methods={"GET"})
     */
    public function messageListAction(Request $request, UserInterface $adherent, MessageRepository $repository): Response
    {
        dump($adherent);

        return $this->render('message/list.html.twig', ['messages' => $repository->findAllForAuthor($adherent)]);
    }

    /**
     * @Route("/creer", name="app_message_create", methods={"GET", "POST"})
     */
    public function createMessageAction(Request $request, UserInterface $adherent, ObjectManager $manager): Response
    {
        $message = Message::createFromAdherent($adherent);

        $form = $this
            ->createForm(MessageType::class, $message)
            ->handleRequest($request)
        ;

        if ($form->isSubmitted() && $form->isValid()) {
            $manager->persist($message);
            $manager->flush();

            $this->addFlash('success', 'message.created_successfully');

            return $this->redirectToRoute('app_message_update', ['uuid' => $message->getUuid()]);
        }

        return $this->render('message/create.html.twig', ['form' => $form->createView()]);
    }

    /**
     * @Route("/{uuid}/modifier", name="app_message_update", methods={"GET", "POST"})
     * @Security("is_granted('IS_AUTHOR_OF', message)")
     */
    public function updateMessageAction(Request $request, Message $message, ObjectManager $manager): Response
    {
        $form = $this
            ->createForm(MessageType::class, $message)
            ->handleRequest($request)
        ;

        if ($form->isSubmitted() && $form->isValid()) {
            $manager->flush();

            $this->addFlash('success', 'message.updated_successfully');

            return $this->redirectToRoute('app_message_update', ['uuid' => $message->getUuid()]);
        }

        return $this->render('message/update.html.twig', ['form' => $form->createView()]);
    }

    /**
     * @Route("/{uuid}/visualiser", name="app_message_preview", methods={"GET"})
     * @Security("is_granted('IS_AUTHOR_OF', message)")
     */
    public function previewMessageAction(Message $message): Response
    {
        return $this->render('message/preview.html.twig', ['message' => $message]);
    }

    /**
     * @Route("/{uuid}/content", name="app_message_content", methods={"GET"})
     * @Security("is_granted('IS_AUTHOR_OF', message)")
     */
    public function getMessageTemplateAction(Message $message, Manager $manager): Response
    {
        return new Response($manager->getContent($message));
    }
}
