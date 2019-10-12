<?php

namespace AppBundle\Repository;

use ApiPlatform\Core\Bridge\Doctrine\Orm\Paginator as ApiPaginator;
use ApiPlatform\Core\DataProvider\PaginatorInterface;
use AppBundle\Entity\Adherent;
use AppBundle\Entity\AdherentSegment;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\Tools\Pagination\Paginator as DoctrinePaginator;

class AdherentSegmentRepository extends ServiceEntityRepository
{
    use AuthorTrait;
    use UuidEntityRepositoryTrait;

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AdherentSegment::class);
    }

    /**
     * @return AdherentSegment[]|PaginatorInterface
     */
    public function findAllByAuthor(Adherent $author, int $page = 1): PaginatorInterface
    {
        if ($page < 1) {
            $page = 1;
        }

        return new ApiPaginator(new DoctrinePaginator($this
            ->createQueryBuilder('list')
            ->where('list.author = :author')
            ->setParameter('author', $author)
            ->setMaxResults(($page - 1) * 30)
            ->getQuery()
        ));
    }
}
