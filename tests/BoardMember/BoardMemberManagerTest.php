<?php

namespace Tests\AppBundle\Repository;

use AppBundle\BoardMember\BoardMemberFilter;
use AppBundle\BoardMember\BoardMemberManager;
use AppBundle\Collection\AdherentCollection;
use AppBundle\DataFixtures\ORM\LoadAdherentData;
use AppBundle\Entity\Adherent;
use AppBundle\Entity\BoardMember\Role;
use AppBundle\Repository\AdherentRepository;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Liip\FunctionalTestBundle\Test\WebTestCase;
use Tests\AppBundle\Controller\ControllerTestTrait;

/**
 * @group functional
 * @group boardMember
 */
class BoardMemberManagerTest extends WebTestCase
{
    use ControllerTestTrait;

    /**
     * @var AdherentRepository
     */
    private $adherentRepository;

    /**
     * @var BoardMemberManager
     */
    private $boardMemberManager;

    protected function setUp(): void
    {
        parent::setUp();

        $this->init();

        $this->adherentRepository = $this->getAdherentRepository();
        $this->boardMemberManager = $this->container->get('app.board_member.manager');
    }

    protected function tearDown(): void
    {
        $this->kill();

        $this->boardMemberManager = null;
        $this->adherentRepository = null;

        parent::tearDown();
    }

    public function testSearchMembers()
    {
        $filter = BoardMemberFilter::createFromArray([]);
        $excludedMember = $this->adherentRepository->findOneByEmail('kiroule.p@blabla.tld');

        $members = $this->boardMemberManager->paginateMembers($filter, $excludedMember);

        $this->assertCount(7, $members);
        $this->assertContainsOnlyInstancesOf(Adherent::class, $members);
        $this->assertNotContains($excludedMember, $members);
    }

    public function testPaginateMembers()
    {
        $filter = BoardMemberFilter::createFromArray([]);
        $excludedMember = $this->adherentRepository->findOneByEmail('kiroule.p@blabla.tld');

        $paginator = $this->boardMemberManager->paginateMembers($filter, $excludedMember);

        $this->assertInstanceOf(Paginator::class, $paginator);
        $this->assertCount(7, $paginator);
        $this->assertContainsOnlyInstancesOf(Adherent::class, $paginator);
        $this->assertNotContains($excludedMember, $paginator);
    }

    public function testFindSavedMembers()
    {
        $adherent = $this->adherentRepository->findByUuid(LoadAdherentData::ADHERENT_12_UUID);

        $savedMembers = $this->boardMemberManager->findSavedMembers($adherent);

        $this->assertInstanceOf(AdherentCollection::class, $savedMembers);
        $this->assertCount(4, $savedMembers);
        $this->assertContainsOnlyInstancesOf(Adherent::class, $savedMembers);

        $expectedSavedMembers = [
            $this->adherentRepository->findByUuid(LoadAdherentData::ADHERENT_2_UUID),
            $this->adherentRepository->findByUuid(LoadAdherentData::ADHERENT_9_UUID),
            $this->adherentRepository->findByUuid(LoadAdherentData::ADHERENT_10_UUID),
            $this->adherentRepository->findByUuid(LoadAdherentData::ADHERENT_11_UUID),
        ];

        foreach ($expectedSavedMembers as $expectedSavedMember) {
            $this->assertContains($expectedSavedMember, $savedMembers);
        }
    }

    public function testFindRoles()
    {
        $roles = $this->boardMemberManager->findRoles();

        $this->assertCount(15, $roles);
        $this->assertContainsOnlyInstancesOf(Role::class, $roles);
    }
}
