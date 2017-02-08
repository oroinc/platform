<?php
namespace Oro\Bundle\DataAuditBundle\Tests\Functional\Service;

use Doctrine\ORM\EntityManagerInterface;
use Oro\Bundle\DataAuditBundle\Entity\Audit;
use Oro\Bundle\DataAuditBundle\Service\SetNewAuditVersionService;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

/**
 * @dbIsolationPerTest
 */
class SetNewAuditVersionServiceTest extends WebTestCase
{
    public function setUp()
    {
        parent::setUp();

        $this->initClient();
    }

    public function testCouldBeGetFromContainerAsService()
    {
        /** @var SetNewAuditVersionService $service */
        $service = $this->getContainer()->get('oro_dataaudit.set_new_audit_version');

        $this->assertInstanceOf(SetNewAuditVersionService::class, $service);
    }

    public function testThrowIfAuditWasNotStoredBefore()
    {
        /** @var SetNewAuditVersionService $service */
        $service = $this->getContainer()->get('oro_dataaudit.set_new_audit_version');

        $newAudit = new Audit();
        $newAudit->setObjectId(123);
        $newAudit->setObjectClass('anObjectClass');

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The audit must be already stored');

        $service->setVersion($newAudit);
    }

    public function testThrowIfAuditVersionAlreadySet()
    {
        /** @var SetNewAuditVersionService $service */
        $service = $this->getContainer()->get('oro_dataaudit.set_new_audit_version');

        $newAudit = new Audit();
        $newAudit->setObjectId(123);
        $newAudit->setObjectClass('anObjectClass');
        $newAudit->setObjectName('anObjectName');
        $newAudit->setVersion(10);
        $newAudit->setLoggedAt();
        $newAudit->setTransactionId('aTransactionId');
        $newAudit->setAction('anAction');

        $this->getEntityManager()->persist($newAudit);
        $this->getEntityManager()->flush();

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Audit version already set.');
        $service->setVersion($newAudit);
    }

    public function testShouldStoreFirstAuditForGivenObject()
    {
        /** @var SetNewAuditVersionService $service */
        $service = $this->getContainer()->get('oro_dataaudit.set_new_audit_version');

        $newAudit = new Audit();
        $newAudit->setObjectId(123);
        $newAudit->setObjectClass('anObjectClass');
        $newAudit->setObjectName('anObjectName');
        $newAudit->setVersion(null);
        $newAudit->setLoggedAt();
        $newAudit->setTransactionId('aTransactionId');
        $newAudit->setAction('anAction');
        $this->getEntityManager()->persist($newAudit);
        $this->getEntityManager()->flush();

        $service->setVersion($newAudit);

        $this->assertSame(1, $newAudit->getVersion());
    }

    public function testShouldStoreAuditForObjectAlreadyAudited()
    {
        /** @var SetNewAuditVersionService $service */
        $service = $this->getContainer()->get('oro_dataaudit.set_new_audit_version');

        $audit = new Audit();
        $audit->setObjectId(123);
        $audit->setObjectClass('anObjectClass');
        $audit->setObjectName('anObjectName');
        $audit->setVersion(10);
        $audit->setLoggedAt();
        $audit->setTransactionId('aTransactionId');
        $audit->setAction('anAction');

        $this->getEntityManager()->persist($audit);
        $this->getEntityManager()->flush();

        $newAudit = new Audit();
        $newAudit->setObjectId(123);
        $newAudit->setObjectClass('anObjectClass');
        $newAudit->setObjectName('anObjectName');
        $newAudit->setVersion(null);
        $newAudit->setLoggedAt();
        $newAudit->setTransactionId('aTransactionId');
        $newAudit->setAction('anAction');
        $this->getEntityManager()->persist($newAudit);
        $this->getEntityManager()->flush();

        $service->setVersion($newAudit);

        $this->assertSame(11, $newAudit->getVersion());
    }

    /**
     * @return EntityManagerInterface
     */
    private function getEntityManager()
    {
        return $this->getContainer()->get('doctrine.orm.entity_manager');
    }
}
