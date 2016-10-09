<?php
namespace Oro\Bundle\DataAuditBundle\Tests\Functional\Service;

use Doctrine\ORM\EntityManagerInterface;
use Oro\Bundle\DataAuditBundle\Entity\AbstractAudit;
use Oro\Bundle\DataAuditBundle\Entity\Audit;
use Oro\Bundle\DataAuditBundle\Service\FindOrCreateAuditService;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\UserBundle\Entity\User;

class FindOrCreateAuditServiceTest extends WebTestCase
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
        self::$loadedFixtures = [];
    }

    public function testCouldBeGetFromContainerAsService()
    {
        /** @var FindOrCreateAuditService $service */
        $service = $this->getContainer()->get('oro_dataaudit.find_or_create_audit');

        $this->assertInstanceOf(FindOrCreateAuditService::class, $service);
    }

    public function testShouldReturnNewAuditIfNoneInDatabase()
    {
        /** @var FindOrCreateAuditService $service */
        $service = $this->getContainer()->get('oro_dataaudit.find_or_create_audit');

        $user = $this->findAdmin();

        $objectId = 123;
        $objectClass = 'aClass';
        $transactionId = 'aTransaction';

        $audit = $service->findOrCreate($user, $objectId, $objectClass, $transactionId);

        $this->assertInstanceOf(AbstractAudit::class, $audit);
        $this->assertGreaterThan(0, $audit->getId());
        $this->assertSame($user, $audit->getUser());
        $this->assertSame($objectId, $audit->getObjectId());
        $this->assertSame($objectClass, $audit->getObjectClass());
    }

    public function testShouldReturnAlreadyExistAudit()
    {
        /** @var FindOrCreateAuditService $service */
        $service = $this->getContainer()->get('oro_dataaudit.find_or_create_audit');

        $objectId = 123;
        $objectClass = 'aClass';
        $transactionId = 'aTransaction';
        $user = new User();

        $audit = new Audit();
        $audit->setObjectId($objectId);
        $audit->setObjectClass($objectClass);
        $audit->setObjectName('anObjectName');
        $audit->setVersion(123);
        $audit->setLoggedAt();
        $audit->setTransactionId($transactionId);
        $audit->setAction('anAction');
        $this->getEntityManager()->persist($audit);
        $this->getEntityManager()->flush();

        $foundAudit = $service->findOrCreate($user, $objectId, $objectClass, $transactionId);

        $this->assertSame($audit, $foundAudit);
    }

    public function testShouldAcceptNullAsUserAndCreateNewAudit()
    {
        /** @var FindOrCreateAuditService $service */
        $service = $this->getContainer()->get('oro_dataaudit.find_or_create_audit');

        $objectId = 123;
        $objectClass = 'aClass';
        $transactionId = 'aTransaction';

        $foundAudit = $service->findOrCreate(null, $objectId, $objectClass, $transactionId);

        $this->assertInstanceOf(Audit::class, $foundAudit);
        $this->assertNull($foundAudit->getUser());
    }

    /**
     * @return EntityManagerInterface
     */
    private function getEntityManager()
    {
        return $this->getContainer()->get('doctrine.orm.entity_manager');
    }

    /**
     * @return User
     */
    private function findAdmin()
    {
        return $this->getEntityManager()->getRepository(User::class)->findOneBy([
            'username' => 'admin'
        ]);
    }
}
