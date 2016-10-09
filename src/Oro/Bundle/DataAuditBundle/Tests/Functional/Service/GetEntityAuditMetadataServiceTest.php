<?php
namespace Oro\Bundle\DataAuditBundle\Tests\Functional\Service;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Proxy\Proxy;
use Metadata\ClassMetadata;
use Metadata\PropertyMetadata;
use Oro\Bundle\DataAuditBundle\Service\GetEntityAuditMetadataService;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\UserBundle\Entity\User;

class GetEntityAuditMetadataServiceTest extends WebTestCase
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
        /** @var GetEntityAuditMetadataService $service */
        $service = $this->getContainer()->get('oro_dataaudit.get_entity_audit_metadata');

        $this->assertInstanceOf(GetEntityAuditMetadataService::class, $service);
    }

    public function testShouldReturnAuditMetaForAuditableEntity()
    {
        /** @var GetEntityAuditMetadataService $service */
        $service = $this->getContainer()->get('oro_dataaudit.get_entity_audit_metadata');

        $metadata = $service->getMetadata(User::class);

        $this->assertInstanceOf(ClassMetadata::class, $metadata);

        $this->assertSame(User::class, $metadata->name);
        
        $this->assertInternalType('array', $metadata->propertyMetadata);
        $this->assertNotEmpty($metadata->propertyMetadata);
        $this->assertContainsOnly(PropertyMetadata::class, $metadata->propertyMetadata);
    }

    public function testShouldReturnAuditMetaForAuditableProxyEntity()
    {
        /** @var EntityManagerInterface $entityManager */
        $entityManager = $this->getContainer()->get('doctrine.orm.entity_manager');

        $userProxy = $entityManager->getReference(User::class, 123);

        //guard
        $this->assertInstanceOf(Proxy::class, $userProxy);

        /** @var GetEntityAuditMetadataService $service */
        $service = $this->getContainer()->get('oro_dataaudit.get_entity_audit_metadata');

        $metadata = $service->getMetadata(get_class($userProxy));

        $this->assertInstanceOf(ClassMetadata::class, $metadata);
        $this->assertSame(User::class, $metadata->name);
    }

    public function testShouldReturnNullIfEntityNotAuditable()
    {
        /** @var GetEntityAuditMetadataService $service */
        $service = $this->getContainer()->get('oro_dataaudit.get_entity_audit_metadata');

        $this->assertNull($service->getMetadata(\stdClass::class));
    }
}
