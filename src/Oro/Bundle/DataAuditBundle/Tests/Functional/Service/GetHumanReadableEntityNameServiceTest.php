<?php
namespace Oro\Bundle\DataAuditBundle\Tests\Functional\Service;

use Doctrine\ORM\EntityManagerInterface;
use Oro\Bundle\DataAuditBundle\Entity\Audit;
use Oro\Bundle\DataAuditBundle\Service\GetHumanReadableEntityNameService;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\TestFrameworkBundle\Entity\TestAuditDataOwner;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class GetHumanReadableEntityNameServiceTest extends WebTestCase
{
    public function setUp()
    {
        parent::setUp();

        $this->initClient([], [], true);
        $this->startTransaction();
    }

    public function tearDown()
    {
        parent::tearDown();

        $this->rollbackTransaction();
    }

    public function testCouldBeGetFromContainerAsService()
    {
        /** @var GetHumanReadableEntityNameService $service */
        $service = $this->getContainer()->get('oro_dataaudit.get_human_readable_entity_name');

        $this->assertInstanceOf(GetHumanReadableEntityNameService::class, $service);
    }

    public function testShouldReturnShortClassNamePlusIdIfEntityNotExistInDb()
    {
        /** @var GetHumanReadableEntityNameService $service */
        $service = $this->getContainer()->get('oro_dataaudit.get_human_readable_entity_name');

        $this->assertEquals('Organization::12345', $service->getName(Organization::class, 12345));
    }

    public function testShouldReturnUserNameFormedByEntityNameResolver()
    {
        $organization = new Organization();
        $organization->setName('theOrganizationName');
        $organization->setEnabled(true);
        $this->getEntityManager()->persist($organization);
        $this->getEntityManager()->flush();

        /** @var GetHumanReadableEntityNameService $service */
        $service = $this->getContainer()->get('oro_dataaudit.get_human_readable_entity_name');

        $this->assertEquals('theOrganizationName', $service->getName(Organization::class, $organization->getId()));
    }

    public function testShouldTryToFindPreviousAuditForRemovedEntityAndUseNameFromIt()
    {
        $audit = new Audit();
        $audit->setObjectName('theExpectedEntityName');
        $audit->setObjectClass(TestAuditDataOwner::class);
        $audit->setObjectId(123);
        $audit->setTransactionId('aTransactionId');
        

        // guard make sure the entity is not stored in db
        $this->assertNull($this->getEntityManager()->find(TestAuditDataOwner::class, 123));

        $this->getEntityManager()->persist($audit);
        $this->getEntityManager()->flush();
        
        /** @var GetHumanReadableEntityNameService $service */
        $service = $this->getContainer()->get('oro_dataaudit.get_human_readable_entity_name');

        $this->assertEquals('theExpectedEntityName', $service->getName(TestAuditDataOwner::class, 123));
    }

    /**
     * @return EntityManagerInterface
     */
    protected function getEntityManager()
    {
        return $this->getClient()->getContainer()->get('doctrine.orm.entity_manager');
    }
}
