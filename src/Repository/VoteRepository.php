<?php

namespace App\Repository;

use ApiPlatform\Core\DataProvider\PaginatorInterface;
use App\Entity\Adherent;
use App\Entity\IdeasWorkshop\Vote;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;

class VoteRepository extends ServiceEntityRepository
{
    use AuthorTrait;
    use PaginatorTrait;

    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, Vote::class);
    }

    public function getIdeasVotesFromAdherent(Adherent $adherent, int $page = 1, int $limit = 5): PaginatorInterface
    {
        $queryBuilder = $this
            ->createQueryBuilder('vote')
            ->addSelect('group_concat(vote.type) AS votes_types')
            ->innerJoin('vote.idea', 'idea')
            ->andWhere('vote.author = :author')
            ->setParameter('author', $adherent)
            ->groupBy('idea.id')
        ;

        return $this->configurePaginator($queryBuilder, $page, $limit);
    }
}
