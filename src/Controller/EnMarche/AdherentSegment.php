<?php

namespace AppBundle\Controller\EnMarche;

use AppBundle\Entity\Adherent;
use AppBundle\Repository\AdherentSegmentRepository;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * @Security("is_granted('ROLE_MESSAGE_REDACTOR'")
 */
class AdherentSegment extends Controller
{
    /**
     * @param Adherent $adherent
     */
    public function listAction(Request $request, UserInterface $adherent, AdherentSegmentRepository $repository): Response
    {
        return $this->render('adherent_segment/list.html.twig', [
            'lists' => $paginator = $repository->findAllByAuthor($adherent, $request->query->getInt('page', 1)),
        ]);
    }
}
