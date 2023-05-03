<?php

namespace Oro\Bundle\OrganizationBundle\Tests\Unit\Ownership;

use Oro\Bundle\EntityExtendBundle\PropertyAccess;
use Oro\Bundle\OrganizationBundle\Entity\BusinessUnit;
use Oro\Bundle\OrganizationBundle\Ownership\EntityOwnershipAssociationsSetter;
use Oro\Bundle\OrganizationBundle\Tests\Unit\Fixture\Entity\Entity;
use Oro\Bundle\OrganizationBundle\Tests\Unit\Fixture\Entity\Organization;
use Oro\Bundle\OrganizationBundle\Tests\Unit\Fixture\Entity\User;
use Oro\Bundle\SecurityBundle\Authentication\TokenAccessorInterface;
use Oro\Bundle\SecurityBundle\Owner\Metadata\OwnershipMetadata;
use Oro\Bundle\SecurityBundle\Owner\Metadata\OwnershipMetadataProviderInterface;

class EntityOwnershipAssociationsSetterTest extends \PHPUnit\Framework\TestCase
{
    /** @var TokenAccessorInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $tokenAccessor;

    /** @var OwnershipMetadataProviderInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $ownershipMetadataProvider;

    /** @var EntityOwnershipAssociationsSetter */
    private $setter;

    protected function setUp(): void
    {
        $this->tokenAccessor = $this->createMock(TokenAccessorInterface::class);
        $this->ownershipMetadataProvider = $this->createMock(OwnershipMetadataProviderInterface::class);

        $this->setter = new EntityOwnershipAssociationsSetter(
            PropertyAccess::createPropertyAccessor(),
            $this->tokenAccessor,
            $this->ownershipMetadataProvider
        );
    }

    public function testSetOwnershipAssociationsForUserOwnedEntity()
    {
        $entity = new Entity();
        $ownershipMetadata = new OwnershipMetadata(
            'USER',
            'owner',
            'owner_id',
            'organization',
            'organization_id'
        );
        $organization = new Organization();
        $user = new User();

        $this->ownershipMetadataProvider->expects($this->once())
            ->method('getMetadata')
            ->with(get_class($entity))
            ->willReturn($ownershipMetadata);

        $this->tokenAccessor->expects($this->once())
            ->method('hasUser')
            ->willReturn(true);
        $this->tokenAccessor->expects($this->once())
            ->method('getUser')
            ->willReturn($user);
        $this->tokenAccessor->expects($this->once())
            ->method('getOrganization')
            ->willReturn($organization);

        self::assertEquals(['owner', 'organization'], $this->setter->setOwnershipAssociations($entity));
        $this->assertSame($organization, $entity->getOrganization());
        $this->assertSame($user, $entity->getOwner());
    }

    public function testSetOwnershipAssociationsForUserOwnedEntityWhenAssociationsAlreadySet()
    {
        $entity = new Entity();
        $ownershipMetadata = new OwnershipMetadata(
            'USER',
            'owner',
            'owner_id',
            'organization',
            'organization_id'
        );
        $organization = new Organization();
        $user = new User();
        $entity->setOrganization($organization);
        $entity->setOwner($user);

        $this->ownershipMetadataProvider->expects($this->once())
            ->method('getMetadata')
            ->with(get_class($entity))
            ->willReturn($ownershipMetadata);

        $this->tokenAccessor->expects($this->once())
            ->method('hasUser')
            ->willReturn(true);
        $this->tokenAccessor->expects($this->never())
            ->method('getUser');
        $this->tokenAccessor->expects($this->never())
            ->method('getOrganization');

        self::assertEquals([], $this->setter->setOwnershipAssociations($entity));
        $this->assertSame($organization, $entity->getOrganization());
        $this->assertSame($user, $entity->getOwner());
    }

    public function testSetOwnershipAssociationsForBusinessUnitOwnedEntity()
    {
        $entity = new Entity();
        $ownershipMetadata = new OwnershipMetadata(
            'BUSINESS_UNIT',
            'owner',
            'owner_id',
            'organization',
            'organization_id'
        );
        $organization = new Organization();
        $organization->setId(123);
        $user = new User();
        $businessUnit = new BusinessUnit();
        $businessUnit->setOrganization($organization);
        $user->addBusinessUnit($businessUnit);

        $this->ownershipMetadataProvider->expects($this->once())
            ->method('getMetadata')
            ->with(get_class($entity))
            ->willReturn($ownershipMetadata);

        $this->tokenAccessor->expects($this->once())
            ->method('hasUser')
            ->willReturn(true);
        $this->tokenAccessor->expects($this->once())
            ->method('getUser')
            ->willReturn($user);
        $this->tokenAccessor->expects($this->exactly(2))
            ->method('getOrganization')
            ->willReturn($organization);

        self::assertEquals(['owner', 'organization'], $this->setter->setOwnershipAssociations($entity));
        $this->assertSame($organization, $entity->getOrganization());
        $this->assertSame($businessUnit, $entity->getOwner());
    }

    public function testSetOwnershipAssociationsForBusinessUnitOwnedEntityWhenAssociationsAlreadySet()
    {
        $entity = new Entity();
        $ownershipMetadata = new OwnershipMetadata(
            'BUSINESS_UNIT',
            'owner',
            'owner_id',
            'organization',
            'organization_id'
        );
        $organization = new Organization();
        $organization->setId(123);
        $user = new User();
        $businessUnit = new BusinessUnit();
        $businessUnit->setOrganization($organization);
        $user->addBusinessUnit($businessUnit);
        $entity->setOrganization($organization);
        $entity->setOwner($businessUnit);

        $this->ownershipMetadataProvider->expects($this->once())
            ->method('getMetadata')
            ->with(get_class($entity))
            ->willReturn($ownershipMetadata);

        $this->tokenAccessor->expects($this->once())
            ->method('hasUser')
            ->willReturn(true);
        $this->tokenAccessor->expects($this->never())
            ->method('getUser');
        $this->tokenAccessor->expects($this->never())
            ->method('getOrganization');

        self::assertEquals([], $this->setter->setOwnershipAssociations($entity));
        $this->assertSame($organization, $entity->getOrganization());
        $this->assertSame($businessUnit, $entity->getOwner());
    }

    public function testSetOwnershipAssociationsForBusinessUnitOwnedEntityWhenUserHasBusinessUnitsFromDifferentOrgs()
    {
        $entity = new Entity();
        $ownershipMetadata = new OwnershipMetadata(
            'BUSINESS_UNIT',
            'owner',
            'owner_id',
            'organization',
            'organization_id'
        );
        $organization1 = new Organization();
        $organization1->setId(123);
        $organization2 = new Organization();
        $organization2->setId(234);
        $user = new User();
        $businessUnit1 = new BusinessUnit();
        $businessUnit1->setOrganization($organization1);
        $businessUnit2 = new BusinessUnit();
        $businessUnit2->setOrganization($organization2);
        $user->addBusinessUnit($businessUnit1);
        $user->addBusinessUnit($businessUnit2);

        $this->ownershipMetadataProvider->expects($this->once())
            ->method('getMetadata')
            ->with(get_class($entity))
            ->willReturn($ownershipMetadata);

        $this->tokenAccessor->expects($this->once())
            ->method('hasUser')
            ->willReturn(true);
        $this->tokenAccessor->expects($this->once())
            ->method('getUser')
            ->willReturn($user);
        $this->tokenAccessor->expects($this->exactly(2))
            ->method('getOrganization')
            ->willReturn($organization2);

        self::assertEquals(['owner', 'organization'], $this->setter->setOwnershipAssociations($entity));
        $this->assertSame($organization2, $entity->getOrganization());
        $this->assertSame($businessUnit2, $entity->getOwner());
    }

    public function testSetOwnershipAssociationsForBusinessUnitEntity()
    {
        $entity = new BusinessUnit();
        $ownershipMetadata = new OwnershipMetadata(
            'BUSINESS_UNIT',
            'owner',
            'owner_id',
            'organization',
            'organization_id'
        );
        $organization = new Organization();
        $organization->setId(123);

        $this->ownershipMetadataProvider->expects($this->once())
            ->method('getMetadata')
            ->with(get_class($entity))
            ->willReturn($ownershipMetadata);

        $this->tokenAccessor->expects($this->once())
            ->method('hasUser')
            ->willReturn(true);
        $this->tokenAccessor->expects($this->never())
            ->method('getUser');
        $this->tokenAccessor->expects($this->once())
            ->method('getOrganization')
            ->willReturn($organization);

        self::assertEquals(['organization'], $this->setter->setOwnershipAssociations($entity));
        $this->assertSame($organization, $entity->getOrganization());
        $this->assertNull($entity->getOwner());
    }

    public function testSetOwnershipAssociationsForOrganizationOwnedEntity()
    {
        $entity = new Entity();
        $ownershipMetadata = new OwnershipMetadata(
            'ORGANIZATION',
            'organization',
            'organization_id'
        );
        $organization = new Organization();

        $this->ownershipMetadataProvider->expects($this->once())
            ->method('getMetadata')
            ->with(get_class($entity))
            ->willReturn($ownershipMetadata);

        $this->tokenAccessor->expects($this->once())
            ->method('hasUser')
            ->willReturn(true);
        $this->tokenAccessor->expects($this->never())
            ->method('getUser');
        $this->tokenAccessor->expects($this->once())
            ->method('getOrganization')
            ->willReturn($organization);

        self::assertEquals(['organization'], $this->setter->setOwnershipAssociations($entity));
        $this->assertSame($organization, $entity->getOrganization());
        $this->assertNull($entity->getOwner());
    }

    public function testSetOwnershipAssociationsForOrganizationOwnedEntityWhenAssociationsAlreadySet()
    {
        $entity = new Entity();
        $ownershipMetadata = new OwnershipMetadata(
            'ORGANIZATION',
            'organization',
            'organization_id'
        );
        $organization = new Organization();
        $entity->setOrganization($organization);

        $this->ownershipMetadataProvider->expects($this->once())
            ->method('getMetadata')
            ->with(get_class($entity))
            ->willReturn($ownershipMetadata);

        $this->tokenAccessor->expects($this->once())
            ->method('hasUser')
            ->willReturn(true);
        $this->tokenAccessor->expects($this->never())
            ->method('getUser');
        $this->tokenAccessor->expects($this->never())
            ->method('getOrganization');

        self::assertEquals([], $this->setter->setOwnershipAssociations($entity));
        $this->assertSame($organization, $entity->getOrganization());
        $this->assertNull($entity->getOwner());
    }

    public function testSetOwnershipAssociationsForAnonymousToken()
    {
        $this->tokenAccessor->expects($this->once())
            ->method('hasUser')
            ->willReturn(false);

        $this->tokenAccessor->expects($this->never())
            ->method('getToken');

        self::assertEquals([], $this->setter->setOwnershipAssociations(new \stdClass()));
    }
}
