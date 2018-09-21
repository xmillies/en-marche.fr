<?php

namespace Tests\AppBundle\Controller\Admin;

use AppBundle\DataFixtures\ORM\LoadAdherentData;
use AppBundle\DataFixtures\ORM\LoadAdminData;
use AppBundle\DataFixtures\ORM\LoadCitizenProjectData;
use AppBundle\DataFixtures\ORM\LoadTurnkeyProjectData;
use AppBundle\Entity\CitizenProject;
use AppBundle\Mailer\Message\CitizenProjectApprovalConfirmationMessage;
use AppBundle\Mailer\Message\TurnkeyProjectApprovalConfirmationMessage;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Tests\AppBundle\Controller\ControllerTestTrait;
use Liip\FunctionalTestBundle\Test\WebTestCase;

/**
 * @group functional
 * @group admin
 */
class AdminCitizenProjectControllerTest extends WebTestCase
{
    use ControllerTestTrait;

    private $citizenProjectRepository;

    public function testApproveCitizenProjectAction(): void
    {
        /** @var CitizenProject $citizenProject */
        $citizenProject = $this->citizenProjectRepository->findOneByUuid(LoadCitizenProjectData::CITIZEN_PROJECT_2_UUID);

        $this->assertTrue($citizenProject->isPreApproved());

        $this->authenticateAsAdmin($this->client);

        $this->client->request(Request::METHOD_GET, sprintf('/admin/projets-citoyens/%s/approve', $citizenProject->getId()));
        $this->assertResponseStatusCode(Response::HTTP_FOUND, $this->client->getResponse());

        $this->get('doctrine.orm.entity_manager')->clear();

        $citizenProject = $this->citizenProjectRepository->findOneByUuid(LoadCitizenProjectData::CITIZEN_PROJECT_2_UUID);

        $this->assertTrue($citizenProject->isApproved());
        $this->assertCountMails(1, CitizenProjectApprovalConfirmationMessage::class, 'benjyd@aol.com');
        $this->assertCountMails(0, TurnkeyProjectApprovalConfirmationMessage::class);
    }

    public function testApproveTurnkeyProjectAction(): void
    {
        /** @var CitizenProject $citizenProject */
        $citizenProject = $this->citizenProjectRepository->findOneByUuid(LoadCitizenProjectData::CITIZEN_PROJECT_13_UUID);

        $this->assertTrue($citizenProject->isPending());

        $this->authenticateAsAdmin($this->client);

        $this->client->request(Request::METHOD_GET, sprintf('/admin/projets-citoyens/%s/approve', $citizenProject->getId()));
        $this->assertResponseStatusCode(Response::HTTP_FOUND, $this->client->getResponse());

        $this->get('doctrine.orm.entity_manager')->clear();

        $citizenProject = $this->citizenProjectRepository->findOneByUuid(LoadCitizenProjectData::CITIZEN_PROJECT_13_UUID);

        $this->assertTrue($citizenProject->isApproved());
        $this->assertCountMails(0, CitizenProjectApprovalConfirmationMessage::class);
        $this->assertCountMails(1, TurnkeyProjectApprovalConfirmationMessage::class, 'laura@deloche.com');
    }

    protected function setUp()
    {
        parent::setUp();

        $this->init([
            LoadAdminData::class,
            LoadAdherentData::class,
            LoadCitizenProjectData::class,
            LoadTurnkeyProjectData::class,
        ]);

        $this->citizenProjectRepository = $this->getCitizenProjectRepository();
    }

    protected function tearDown()
    {
        $this->kill();

        $this->citizenProjectRepository = null;

        parent::tearDown();
    }
}
