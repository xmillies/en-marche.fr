<?php

namespace AppBundle\Repository;

use AppBundle\Entity\Adherent;
use AppBundle\Entity\ApplicationRequest\VolunteerRequest;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Query\Expr\Join;
use Symfony\Bridge\Doctrine\RegistryInterface;

class VolunteerRequestRepository extends ServiceEntityRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, VolunteerRequest::class);
    }

    public function findForReferent(Adherent $referent): array
    {
        if (!$referent->isReferent()) {
            return [];
        }

        $results = $this->createQueryBuilder('r')
            ->addSelect('CASE WHEN a.id IS NOT NULL THEN 1 ELSE 0 END AS isAdherent')
            ->leftJoin(Adherent::class, 'a', Join::WITH, 'a.emailAddress = r.emailAddress AND a.adherent = 1')
            ->innerJoin('r.referentTags', 'tag')
            ->andWhere('tag IN (:tags)')
            ->setParameter('tags', $referent->getManagedArea()->getTags())
            ->addOrderBy('r.lastName', 'ASC')
            ->addOrderBy('r.firstName', 'ASC')
            ->getQuery()
            ->getResult()
        ;

        $data = [];
        foreach ($results as $result) {
            /** @var VolunteerRequest $volunteer */
            $volunteer = $result[0];
            $volunteer->setIsAdherent($result['isAdherent']);
            $data[] = $volunteer;
        }

        return $data;
    }

    public function findForMunicipalChief(Adherent $municipalChief): array
    {
        if (!$municipalChief->isMunicipalChief()) {
            return [];
        }

        $qb = $this->createQueryBuilder('v')
            ->addSelect('CASE WHEN a.id IS NOT NULL THEN 1 ELSE 0 END AS isAdherent')
            ->leftJoin(Adherent::class, 'a', Join::WITH, 'a.emailAddress = v.emailAddress AND a.adherent = 1')
            ->addOrderBy('v.lastName', 'ASC')
            ->addOrderBy('v.firstName', 'ASC')
        ;

        foreach ($municipalChief->getMunicipalChiefManagedArea()->getCodes() as $key => $code) {
            $qb
                ->orWhere("FIND_IN_SET(:codes_$key, v.favoriteCities) > 0")
                ->setParameter("codes_$key", $code)
            ;
        }

        $data = [];
        foreach ($qb->getQuery()->getResult() as $result) {
            /** @var VolunteerRequest $volunteerRequest */
            $volunteerRequest = $result[0];
            $volunteerRequest->setIsAdherent($result['isAdherent']);
            $data[] = $volunteerRequest;
        }

        return $data;
    }
}
