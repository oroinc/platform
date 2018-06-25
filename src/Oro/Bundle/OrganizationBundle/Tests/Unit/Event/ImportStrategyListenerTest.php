<?php

namespace Oro\Bundle\OrganizationBundle\Tests\Unit\Event;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\ImportExportBundle\Context\ContextInterface;
use Oro\Bundle\ImportExportBundle\Event\StrategyEvent;
use Oro\Bundle\ImportExportBundle\Strategy\StrategyInterface;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\OrganizationBundle\Event\ImportStrategyListener;
use Oro\Bundle\OrganizationBundle\Tests\Unit\Fixture\Entity\Entity;
use Oro\Bundle\SecurityBundle\Authentication\TokenAccessorInterface;
use Oro\Bundle\SecurityBundle\Owner\Metadata\OwnershipMetadata;
use Oro\Bundle\SecurityBundle\Owner\Metadata\OwnershipMetadataProviderInterface;
use Oro\Component\DependencyInjection\ServiceLink;

class ImportStrategyListenerTest extends \PHPUnit\Framework\TestCase
{
    /** @var ImportStrategyListener */
    private $listener;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    private $doctrine;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    private $tokenAccessor;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    private $metadataProvider;

    protected function setUp()
    {
        $this->doctrine = $this->createMock(ManagerRegistry::class);
        $this->tokenAccessor = $this->createMock(TokenAccessorInterface::class);

        $this->metadataProvider = $this->createMock(OwnershipMetadataProviderInterface::class);
        $metadataProviderLink = $this->createMock(ServiceLink::class);
        $metadataProviderLink->expects($this->any())
            ->method('getService')
            ->willReturn($this->metadataProvider);

        $this->listener = new ImportStrategyListener($this->doctrine, $this->tokenAccessor, $metadataProviderLink);
    }

    public function testOnProcessAfterWithNonSupportedEntity()
    {
        $entity = new Entity();
        $event = new StrategyEvent(
            $this->createMock(StrategyInterface::class),
            $entity,
            $this->createMock(ContextInterface::class)
        );

        $metadata = new OwnershipMetadata();
        $this->metadataProvider->expects($this->once())
            ->method('getMetadata')
            ->with(Entity::class)
            ->willReturn($metadata);

        $this->listener->onProcessAfter($event);

        $this->assertEmpty($entity->getOrganization());
    }

    public function testOnProcessAfterWithOrganizationInEntityButWithoutOrganizationInData()
    {
        $entity = new Entity();
        $organization = new Organization();
        $entity->setOrganization($organization);

        $context = $this->createMock(ContextInterface::class);
        $context->expects($this->once())
            ->method('getValue')
            ->willReturn(['id' => 23]);
        $event = new StrategyEvent(
            $this->createMock(StrategyInterface::class),
            $entity,
            $context
        );

        $metadata = new OwnershipMetadata('USER', 'user', 'user', 'organization', 'organization');
        $this->metadataProvider->expects($this->once())
            ->method('getMetadata')
            ->with(Entity::class)
            ->willReturn($metadata);

        $this->listener->onProcessAfter($event);

        $this->assertSame($organization, $entity->getOrganization());
    }

    public function testOnProcessAfterWithEntityOrgAndDataOrgWithoutOrgInToken()
    {
        $entity = new Entity();
        $organization = new Organization();
        $entity->setOrganization($organization);

        $context = $this->createMock(ContextInterface::class);
        $context->expects($this->once())
            ->method('getValue')
            ->willReturn(['id' => 23, 'organization' => $organization]);
        $event = new StrategyEvent(
            $this->createMock(StrategyInterface::class),
            $entity,
            $context
        );

        $this->tokenAccessor->expects($this->once())
            ->method('getOrganization')
            ->willReturn(null);

        $metadata = new OwnershipMetadata('USER', 'user', 'user', 'organization', 'organization');
        $this->metadataProvider->expects($this->once())
            ->method('getMetadata')
            ->with(Entity::class)
            ->willReturn($metadata);

        $this->listener->onProcessAfter($event);

        $this->assertSame($organization, $entity->getOrganization());
    }

    public function testOnProcessAfterWithEntityOrgAndDataOrgWithSameOrgInToken()
    {
        $entity = new Entity();
        $organization = new Organization();
        $organization->setId(456);
        $entity->setOrganization($organization);

        $context = $this->createMock(ContextInterface::class);
        $context->expects($this->once())
            ->method('getValue')
            ->willReturn(['id' => 23, 'organization' => $organization]);
        $event = new StrategyEvent(
            $this->createMock(StrategyInterface::class),
            $entity,
            $context
        );

        $this->tokenAccessor->expects($this->once())
            ->method('getOrganization')
            ->willReturn($organization);
        $this->tokenAccessor->expects($this->once())
            ->method('getOrganizationId')
            ->willReturn(456);

        $metadata = new OwnershipMetadata('USER', 'user', 'user', 'organization', 'organization');
        $this->metadataProvider->expects($this->once())
            ->method('getMetadata')
            ->with(Entity::class)
            ->willReturn($metadata);

        $this->listener->onProcessAfter($event);

        $this->assertSame($organization, $entity->getOrganization());
    }

    public function testOnProcessAfterWithoutEntityOrgWithoutOrgInToken()
    {
        $entity = new Entity();

        $organization = new Organization();
        $organization->setId(456);

        $context = $this->createMock(ContextInterface::class);
        $event = new StrategyEvent(
            $this->createMock(StrategyInterface::class),
            $entity,
            $context
        );

        $this->tokenAccessor->expects($this->once())
            ->method('getOrganization')
            ->willReturn(null);

        $repo = $this->createMock(EntityRepository::class);
        $queryBuilder = $this->createMock(QueryBuilder::class);
        $query = $this->createMock(AbstractQuery::class);
        $this->doctrine->expects($this->once())->method('getRepository')->willReturn($repo);
        $repo->expects($this->once())->method('createQueryBuilder')->willReturn($queryBuilder);
        $queryBuilder->expects($this->once())->method('setMaxResults')->willReturn($queryBuilder);
        $queryBuilder->expects($this->once())->method('getQuery')->willReturn($query);
        $query->expects($this->once())->method('getResult')->willReturn([$organization]);

        $metadata = new OwnershipMetadata('USER', 'user', 'user', 'organization', 'organization');
        $this->metadataProvider->expects($this->once())
            ->method('getMetadata')
            ->with(Entity::class)
            ->willReturn($metadata);

        $this->listener->onProcessAfter($event);

        $this->assertSame($organization, $entity->getOrganization());
    }

    public function testOnProcessAfterWithoutEntityOrgWithOrgInToken()
    {
        $entity = new Entity();

        $organization = new Organization();
        $organization->setId(456);

        $context = $this->createMock(ContextInterface::class);
        $event = new StrategyEvent(
            $this->createMock(StrategyInterface::class),
            $entity,
            $context
        );

        $this->tokenAccessor->expects($this->once())
            ->method('getOrganization')
            ->willReturn($organization);

        $metadata = new OwnershipMetadata('USER', 'user', 'user', 'organization', 'organization');
        $this->metadataProvider->expects($this->once())
            ->method('getMetadata')
            ->with(Entity::class)
            ->willReturn($metadata);

        $this->listener->onProcessAfter($event);

        $this->assertSame($organization, $entity->getOrganization());
    }

    public function testOnProcessAfterWithSameOrgInEntityAndToken()
    {
        $entity = new Entity();

        $organization = new Organization();
        $organization->setId(456);
        $entity->setOrganization($organization);

        $context = $this->createMock(ContextInterface::class);
        $context->expects($this->once())
            ->method('getValue')
            ->willReturn(['id' => 23, 'organization' => $organization]);
        $event = new StrategyEvent(
            $this->createMock(StrategyInterface::class),
            $entity,
            $context
        );

        $this->tokenAccessor->expects($this->once())
            ->method('getOrganization')
            ->willReturn($organization);

        $metadata = new OwnershipMetadata('USER', 'user', 'user', 'organization', 'organization');
        $this->metadataProvider->expects($this->once())
            ->method('getMetadata')
            ->with(Entity::class)
            ->willReturn($metadata);

        $this->listener->onProcessAfter($event);

        $this->assertSame($organization, $entity->getOrganization());
    }

    public function testOnProcessAfterWithDifferentOrgInEntityAndToken()
    {
        $entity = new Entity();

        $organization = new Organization();
        $organization->setId(456);
        $entity->setOrganization($organization);

        $tokenOrg = new Organization();
        $tokenOrg->setId(789);

        $context = $this->createMock(ContextInterface::class);
        $context->expects($this->once())
            ->method('getValue')
            ->willReturn(['id' => 23, 'organization' => $organization]);
        $event = new StrategyEvent(
            $this->createMock(StrategyInterface::class),
            $entity,
            $context
        );

        $this->tokenAccessor->expects($this->once())
            ->method('getOrganization')
            ->willReturn($tokenOrg);
        $this->tokenAccessor->expects($this->once())
            ->method('getOrganizationId')
            ->willReturn($tokenOrg->getId());

        $metadata = new OwnershipMetadata('USER', 'user', 'user', 'organization', 'organization');
        $this->metadataProvider->expects($this->once())
            ->method('getMetadata')
            ->with(Entity::class)
            ->willReturn($metadata);

        $this->listener->onProcessAfter($event);

        $this->assertSame($tokenOrg, $entity->getOrganization());
    }
}
