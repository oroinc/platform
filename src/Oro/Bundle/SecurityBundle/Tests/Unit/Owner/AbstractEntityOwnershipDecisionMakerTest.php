<?php

namespace Oro\Bundle\SecurityBundle\Tests\Unit\Owner;

use Doctrine\Inflector\Rules\English\InflectorFactory;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\SecurityBundle\Acl\Domain\DomainObjectReference;
use Oro\Bundle\SecurityBundle\Acl\Domain\ObjectIdAccessor;
use Oro\Bundle\SecurityBundle\Owner\AbstractEntityOwnershipDecisionMaker;
use Oro\Bundle\SecurityBundle\Owner\EntityOwnerAccessor;
use Oro\Bundle\SecurityBundle\Owner\Metadata\OwnershipMetadata;
use Oro\Bundle\SecurityBundle\Owner\OwnerTree;
use Oro\Bundle\SecurityBundle\Owner\OwnerTreeProvider;
use Oro\Bundle\SecurityBundle\Tests\Unit\Acl\Domain\Fixtures\Entity\BusinessUnit;
use Oro\Bundle\SecurityBundle\Tests\Unit\Acl\Domain\Fixtures\Entity\Organization;
use Oro\Bundle\SecurityBundle\Tests\Unit\Acl\Domain\Fixtures\Entity\TestEntity;
use Oro\Bundle\SecurityBundle\Tests\Unit\Acl\Domain\Fixtures\Entity\User;
use Oro\Bundle\SecurityBundle\Tests\Unit\Stub\OwnershipMetadataProviderStub;
use Symfony\Component\Security\Acl\Exception\InvalidDomainObjectException;

/**
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class AbstractEntityOwnershipDecisionMakerTest extends AbstractCommonEntityOwnershipDecisionMakerTest
{
    private OwnershipMetadataProviderStub $metadataProvider;

    private AbstractEntityOwnershipDecisionMaker $decisionMaker;

    protected function setUp(): void
    {
        $this->tree = new OwnerTree();

        $this->metadataProvider = new OwnershipMetadataProviderStub($this);
        $this->metadataProvider->setMetadata(
            $this->metadataProvider->getOrganizationClass(),
            new OwnershipMetadata()
        );
        $this->metadataProvider->setMetadata(
            $this->metadataProvider->getBusinessUnitClass(),
            new OwnershipMetadata('BUSINESS_UNIT', 'owner', 'owner_id', 'organization')
        );
        $this->metadataProvider->setMetadata(
            $this->metadataProvider->getUserClass(),
            new OwnershipMetadata('BUSINESS_UNIT', 'owner', 'owner_id', 'organization')
        );
        $this->metadataProvider->getCacheMock()
            ->expects(self::any())
            ->method('get')
            ->willReturn(true);

        $treeProvider = $this->createMock(OwnerTreeProvider::class);
        $treeProvider->expects($this->any())
            ->method('getTree')
            ->willReturn($this->tree);

        $doctrineHelper = $this->createMock(DoctrineHelper::class);

        $this->decisionMaker = $this->getMockBuilder(AbstractEntityOwnershipDecisionMaker::class)
            ->setConstructorArgs([
                $treeProvider,
                new ObjectIdAccessor($doctrineHelper),
                new EntityOwnerAccessor($this->metadataProvider, (new InflectorFactory())->build()),
                $this->metadataProvider
            ])
            ->getMockForAbstractClass();
    }

    public function testIsOrganization()
    {
        $this->assertFalse($this->decisionMaker->isOrganization(null));
        $this->assertFalse($this->decisionMaker->isOrganization('test'));
        $this->assertFalse($this->decisionMaker->isOrganization(new User()));
        $this->assertTrue($this->decisionMaker->isOrganization(new Organization()));
        $this->assertTrue($this->decisionMaker->isOrganization($this->createMock(Organization::class)));
    }

    public function testIsBusinessUnit()
    {
        $this->assertFalse($this->decisionMaker->isBusinessUnit(null));
        $this->assertFalse($this->decisionMaker->isBusinessUnit('test'));
        $this->assertFalse($this->decisionMaker->isBusinessUnit(new User()));
        $this->assertTrue($this->decisionMaker->isBusinessUnit(new BusinessUnit()));
        $this->assertTrue($this->decisionMaker->isBusinessUnit($this->createMock(BusinessUnit::class)));
    }

    public function testIsUser()
    {
        $this->assertFalse($this->decisionMaker->isUser(null));
        $this->assertFalse($this->decisionMaker->isUser('test'));
        $this->assertFalse($this->decisionMaker->isUser(new BusinessUnit()));
        $this->assertTrue($this->decisionMaker->isUser(new User()));
        $this->assertTrue($this->decisionMaker->isUser($this->createMock(User::class)));
    }

    public function testIsAssociatedWithOrganizationNullUser()
    {
        $this->expectException(InvalidDomainObjectException::class);
        $this->decisionMaker->isAssociatedWithOrganization(null, null);
    }

    public function testIsAssociatedWithOrganizationNullObject()
    {
        $this->expectException(InvalidDomainObjectException::class);
        $user = new User(self::USER_ID);
        $this->decisionMaker->isAssociatedWithOrganization($user, null);
    }

    public function testIsAssociatedWithBusinessUnitNullUser()
    {
        $this->expectException(InvalidDomainObjectException::class);
        $this->decisionMaker->isAssociatedWithBusinessUnit(null, null);
    }

    public function testIsAssociatedWithBusinessUnitNullObject()
    {
        $this->expectException(InvalidDomainObjectException::class);
        $user = new User(self::USER_ID);
        $this->decisionMaker->isAssociatedWithBusinessUnit($user, null);
    }

    public function testIsAssociatedWithUserNullUser()
    {
        $this->expectException(InvalidDomainObjectException::class);
        $this->decisionMaker->isAssociatedWithUser(null, null);
    }

    public function testIsAssociatedWithUserNullObject()
    {
        $this->expectException(InvalidDomainObjectException::class);
        $user = new User(self::USER_ID);
        $this->decisionMaker->isAssociatedWithUser($user, null);
    }

    public function testIsAssociatedWithOrganizationForSystemObject()
    {
        $user = new User(self::USER_ID);
        $this->assertFalse($this->decisionMaker->isAssociatedWithOrganization($user, new \stdClass()));
    }

    public function testIsAssociatedWithBusinessUnitForSystemObject()
    {
        $user = new User(self::USER_ID);
        $this->assertFalse($this->decisionMaker->isAssociatedWithBusinessUnit($user, new \stdClass()));
    }

    public function testIsAssociatedWithUserForSystemObject()
    {
        $user = new User(self::USER_ID);
        $this->assertFalse($this->decisionMaker->isAssociatedWithUser($user, new \stdClass()));
    }

    public function testIsAssociatedWithOrganizationForDomainObjectOwnedByNullObject()
    {
        $this->buildTestTree();
        $objReference = new DomainObjectReference(TestEntity::class, 1, null);

        $this->assertFalse($this->decisionMaker->isAssociatedWithOrganization($this->user1, $objReference));
        $this->assertFalse($this->decisionMaker->isAssociatedWithOrganization($this->user2, $objReference));
        $this->assertFalse($this->decisionMaker->isAssociatedWithOrganization($this->user3, $objReference));
        $this->assertFalse($this->decisionMaker->isAssociatedWithOrganization($this->user4, $objReference));
    }

    public function testIsAssociatedWithOrganizationForDomainObjectOwnedByOrganization()
    {
        $this->buildTestTree();

        $this->metadataProvider->setMetadata(
            TestEntity::class,
            new OwnershipMetadata('ORGANIZATION', 'owner', 'owner_id')
        );

        $org1 = new Organization(1);
        $org2 = new Organization(2);
        $org3 = new Organization(3);
        $org4 = new Organization(4);

        $this->tree->addUserOrganization($this->user1->getId(), $org1->getId());
        $this->tree->addUserOrganization($this->user1->getId(), $org2->getId());
        $this->tree->addUserOrganization($this->user2->getId(), $org2->getId());
        $this->tree->addUserOrganization($this->user3->getId(), $org2->getId());
        $this->tree->addUserOrganization($this->user3->getId(), $org3->getId());
        $this->tree->addUserOrganization($this->user4->getId(), $org4->getId());

        $objReference = new DomainObjectReference(TestEntity::class, 1, $org1->getId());
        $objReference1 = new DomainObjectReference(TestEntity::class, 1, $org2->getId());
        $objReference2 = new DomainObjectReference(TestEntity::class, 1, $org3->getId());
        $objReference3 = new DomainObjectReference(TestEntity::class, 1, $org4->getId());

        $this->assertTrue($this->decisionMaker->isAssociatedWithOrganization($this->user1, $objReference));
        $this->assertTrue($this->decisionMaker->isAssociatedWithOrganization($this->user1, $objReference1));
        $this->assertFalse($this->decisionMaker->isAssociatedWithOrganization($this->user1, $objReference2));
        $this->assertFalse($this->decisionMaker->isAssociatedWithOrganization($this->user1, $objReference3));

        $this->assertFalse($this->decisionMaker->isAssociatedWithOrganization($this->user2, $objReference));
        $this->assertTrue($this->decisionMaker->isAssociatedWithOrganization($this->user2, $objReference1));
        $this->assertFalse($this->decisionMaker->isAssociatedWithOrganization($this->user2, $objReference2));
        $this->assertFalse($this->decisionMaker->isAssociatedWithOrganization($this->user2, $objReference3));

        $this->assertFalse($this->decisionMaker->isAssociatedWithOrganization($this->user3, $objReference));
        $this->assertTrue($this->decisionMaker->isAssociatedWithOrganization($this->user3, $objReference1));
        $this->assertTrue($this->decisionMaker->isAssociatedWithOrganization($this->user3, $objReference2));
        $this->assertFalse($this->decisionMaker->isAssociatedWithOrganization($this->user3, $objReference3));

        $this->assertFalse($this->decisionMaker->isAssociatedWithOrganization($this->user4, $objReference));
        $this->assertFalse($this->decisionMaker->isAssociatedWithOrganization($this->user4, $objReference1));
        $this->assertFalse($this->decisionMaker->isAssociatedWithOrganization($this->user4, $objReference2));
        $this->assertTrue($this->decisionMaker->isAssociatedWithOrganization($this->user4, $objReference3));
    }

    public function testIsAssociatedWithOrganizationForOrganizationObject()
    {
        $this->buildTestTree();

        $this->assertTrue($this->decisionMaker->isAssociatedWithOrganization($this->user1, $this->org1));
        $this->assertTrue($this->decisionMaker->isAssociatedWithOrganization($this->user2, $this->org2));
        $this->assertTrue($this->decisionMaker->isAssociatedWithOrganization($this->user3, $this->org3));
        $this->assertTrue($this->decisionMaker->isAssociatedWithOrganization($this->user31, $this->org3));
        $this->assertFalse($this->decisionMaker->isAssociatedWithOrganization($this->user4, $this->org3));
        $this->assertTrue($this->decisionMaker->isAssociatedWithOrganization($this->user4, $this->org4));
        $this->assertTrue($this->decisionMaker->isAssociatedWithOrganization($this->user411, $this->org4));
    }

    public function testIsAssociatedWithOrganizationForUserObject()
    {
        $this->buildTestTree();

        $this->assertFalse($this->decisionMaker->isAssociatedWithOrganization($this->user1, $this->user1));
        $this->assertFalse($this->decisionMaker->isAssociatedWithOrganization($this->user2, $this->user2));
        $this->assertTrue($this->decisionMaker->isAssociatedWithOrganization($this->user3, $this->user3));
        $this->assertTrue($this->decisionMaker->isAssociatedWithOrganization($this->user3, $this->user31));
        $this->assertTrue($this->decisionMaker->isAssociatedWithOrganization($this->user31, $this->user31));
        $this->assertTrue($this->decisionMaker->isAssociatedWithOrganization($this->user4, $this->user4));
        $this->assertFalse($this->decisionMaker->isAssociatedWithOrganization($this->user4, $this->user3));
        $this->assertFalse($this->decisionMaker->isAssociatedWithOrganization($this->user4, $this->user31));
        $this->assertTrue($this->decisionMaker->isAssociatedWithOrganization($this->user4, $this->user411));
        $this->assertTrue($this->decisionMaker->isAssociatedWithOrganization($this->user411, $this->user411));
    }

    public function testIsAssociatedWithOrganizationForOrganizationOwnedObject()
    {
        $this->buildTestTree();

        $this->metadataProvider->setMetadata(
            TestEntity::class,
            new OwnershipMetadata('ORGANIZATION', 'owner', 'owner_id')
        );

        $obj = new TestEntity(1);
        $obj1 = new TestEntity(1, $this->org1);
        $obj2 = new TestEntity(1, $this->org2);
        $obj3 = new TestEntity(1, $this->org3);
        $obj4 = new TestEntity(1, $this->org4);

        $this->assertFalse($this->decisionMaker->isAssociatedWithOrganization($this->user1, $obj));
        $this->assertTrue($this->decisionMaker->isAssociatedWithOrganization($this->user1, $obj1));
        $this->assertFalse($this->decisionMaker->isAssociatedWithOrganization($this->user2, $obj));
        $this->assertTrue($this->decisionMaker->isAssociatedWithOrganization($this->user2, $obj2));
        $this->assertFalse($this->decisionMaker->isAssociatedWithOrganization($this->user3, $obj));
        $this->assertTrue($this->decisionMaker->isAssociatedWithOrganization($this->user3, $obj3));
        $this->assertFalse($this->decisionMaker->isAssociatedWithOrganization($this->user4, $obj));
        $this->assertFalse($this->decisionMaker->isAssociatedWithOrganization($this->user4, $obj3));
        $this->assertTrue($this->decisionMaker->isAssociatedWithOrganization($this->user4, $obj4));
    }

    public function testIsAssociatedWithOrganizationForBusinessUnitOwnedObject()
    {
        $this->buildTestTree();

        $this->metadataProvider->setMetadata(
            TestEntity::class,
            new OwnershipMetadata('BUSINESS_UNIT', 'owner', 'owner_id', 'organization')
        );

        $obj = new TestEntity(1, null, $this->org1);
        $obj1 = new TestEntity(1, $this->bu1, $this->org1);
        $obj2 = new TestEntity(1, $this->bu2, $this->org2);
        $obj3 = new TestEntity(1, $this->bu3, $this->org3);
        $obj31 = new TestEntity(1, $this->bu31, $this->org3);
        $obj4 = new TestEntity(1, $this->bu4, $this->org4);
        $obj41 = new TestEntity(1, $this->bu41, $this->org4);
        $obj411 = new TestEntity(1, $this->bu411, $this->org4);

        $this->assertTrue($this->decisionMaker->isAssociatedWithOrganization($this->user1, $obj));
        $this->assertTrue($this->decisionMaker->isAssociatedWithOrganization($this->user1, $obj1));
        $this->assertFalse($this->decisionMaker->isAssociatedWithOrganization($this->user2, $obj));
        $this->assertTrue($this->decisionMaker->isAssociatedWithOrganization($this->user2, $obj2));
        $this->assertFalse($this->decisionMaker->isAssociatedWithOrganization($this->user3, $obj));
        $this->assertTrue($this->decisionMaker->isAssociatedWithOrganization($this->user3, $obj3));
        $this->assertTrue($this->decisionMaker->isAssociatedWithOrganization($this->user3, $obj31));
        $this->assertFalse($this->decisionMaker->isAssociatedWithOrganization($this->user4, $obj));
        $this->assertFalse($this->decisionMaker->isAssociatedWithOrganization($this->user4, $obj3));
        $this->assertFalse($this->decisionMaker->isAssociatedWithOrganization($this->user4, $obj31));
        $this->assertTrue($this->decisionMaker->isAssociatedWithOrganization($this->user4, $obj4));
        $this->assertTrue($this->decisionMaker->isAssociatedWithOrganization($this->user4, $obj41));
        $this->assertTrue($this->decisionMaker->isAssociatedWithOrganization($this->user4, $obj411));
    }

    public function testIsAssociatedWithOrganizationForUserOwnedObject()
    {
        $this->buildTestTree();

        $this->metadataProvider->setMetadata(
            TestEntity::class,
            new OwnershipMetadata('USER', 'owner', 'owner_id', 'organization')
        );

        $obj = new TestEntity(1, null, $this->org1);
        $obj1 = new TestEntity(1, $this->user1, $this->org1);
        $obj2 = new TestEntity(1, $this->user2, $this->org2);
        $obj3 = new TestEntity(1, $this->user3, $this->org3);
        $obj31 = new TestEntity(1, $this->user31, $this->org3);
        $obj4 = new TestEntity(1, $this->user4, $this->org4);
        $obj411 = new TestEntity(1, $this->user411, $this->org4);

        $this->assertTrue($this->decisionMaker->isAssociatedWithOrganization($this->user1, $obj));
        $this->assertTrue($this->decisionMaker->isAssociatedWithOrganization($this->user1, $obj1));
        $this->assertFalse($this->decisionMaker->isAssociatedWithOrganization($this->user2, $obj));
        $this->assertTrue($this->decisionMaker->isAssociatedWithOrganization($this->user2, $obj2));
        $this->assertFalse($this->decisionMaker->isAssociatedWithOrganization($this->user3, $obj));
        $this->assertTrue($this->decisionMaker->isAssociatedWithOrganization($this->user3, $obj3));
        $this->assertTrue($this->decisionMaker->isAssociatedWithOrganization($this->user3, $obj31));
        $this->assertFalse($this->decisionMaker->isAssociatedWithOrganization($this->user4, $obj));
        $this->assertFalse($this->decisionMaker->isAssociatedWithOrganization($this->user4, $obj3));
        $this->assertFalse($this->decisionMaker->isAssociatedWithOrganization($this->user4, $obj31));
        $this->assertTrue($this->decisionMaker->isAssociatedWithOrganization($this->user4, $obj4));
        $this->assertTrue($this->decisionMaker->isAssociatedWithOrganization($this->user4, $obj411));
    }

    public function testIsAssociatedWithBusinessUnitForDomainObjectOwnedByNullObject()
    {
        $this->buildTestTree();
        $objReference = new DomainObjectReference(TestEntity::class, 1, null);

        $this->assertFalse($this->decisionMaker->isAssociatedWithBusinessUnit($this->user1, $objReference));
        $this->assertFalse($this->decisionMaker->isAssociatedWithBusinessUnit($this->user2, $objReference));
        $this->assertFalse($this->decisionMaker->isAssociatedWithBusinessUnit($this->user3, $objReference));
        $this->assertFalse($this->decisionMaker->isAssociatedWithBusinessUnit($this->user4, $objReference));
    }

    public function testIsAssociatedWithBusinessUnitForDomainObjectOwnedByBusinessUnit()
    {
        $this->buildTestTree();

        $this->metadataProvider->setMetadata(
            TestEntity::class,
            new OwnershipMetadata('BUSINESS_UNIT', 'owner', 'owner_id', 'organization')
        );

        $bu1 = new BusinessUnit(1);
        $bu2 = new BusinessUnit(2);
        $bu3 = new BusinessUnit(3);
        $bu4 = new BusinessUnit(4);

        $this->tree->addUserBusinessUnit($this->user1->getId(), $this->org1->getId(), $bu1->getId());
        $this->tree->addUserBusinessUnit($this->user1->getId(), $this->org1->getId(), $bu2->getId());
        $this->tree->addUserBusinessUnit($this->user2->getId(), $this->org2->getId(), $bu2->getId());
        $this->tree->addUserBusinessUnit($this->user3->getId(), $this->org3->getId(), $bu2->getId());
        $this->tree->addUserBusinessUnit($this->user3->getId(), $this->org3->getId(), $bu3->getId());
        $this->tree->addUserBusinessUnit($this->user4->getId(), $this->org4->getId(), $bu4->getId());

        $objReference = new DomainObjectReference(TestEntity::class, 1, $bu1->getId());
        $objReference1 = new DomainObjectReference(TestEntity::class, 1, $bu2->getId());
        $objReference2 = new DomainObjectReference(TestEntity::class, 1, $bu3->getId());
        $objReference3 = new DomainObjectReference(TestEntity::class, 1, $bu4->getId());

        $this->assertTrue($this->decisionMaker->isAssociatedWithBusinessUnit($this->user1, $objReference));
        $this->assertTrue($this->decisionMaker->isAssociatedWithBusinessUnit($this->user1, $objReference1));
        $this->assertFalse($this->decisionMaker->isAssociatedWithBusinessUnit($this->user1, $objReference2));
        $this->assertFalse($this->decisionMaker->isAssociatedWithBusinessUnit($this->user1, $objReference3));

        $this->assertFalse($this->decisionMaker->isAssociatedWithBusinessUnit($this->user2, $objReference));
        $this->assertTrue($this->decisionMaker->isAssociatedWithBusinessUnit($this->user2, $objReference1));
        $this->assertFalse($this->decisionMaker->isAssociatedWithBusinessUnit($this->user2, $objReference2));
        $this->assertFalse($this->decisionMaker->isAssociatedWithBusinessUnit($this->user2, $objReference3));

        $this->assertFalse($this->decisionMaker->isAssociatedWithBusinessUnit($this->user3, $objReference));
        $this->assertTrue($this->decisionMaker->isAssociatedWithBusinessUnit($this->user3, $objReference1));
        $this->assertTrue($this->decisionMaker->isAssociatedWithBusinessUnit($this->user3, $objReference2));
        $this->assertFalse($this->decisionMaker->isAssociatedWithBusinessUnit($this->user3, $objReference3));

        $this->assertFalse($this->decisionMaker->isAssociatedWithBusinessUnit($this->user4, $objReference));
        $this->assertFalse($this->decisionMaker->isAssociatedWithBusinessUnit($this->user4, $objReference1));
        $this->assertFalse($this->decisionMaker->isAssociatedWithBusinessUnit($this->user4, $objReference2));
        $this->assertTrue($this->decisionMaker->isAssociatedWithBusinessUnit($this->user4, $objReference3));
    }

    public function testIsAssociatedWithBusinessUnitForDomainObjectOwnedByUser()
    {
        $this->buildTestTree();

        $this->metadataProvider->setMetadata(
            TestEntity::class,
            new OwnershipMetadata('USER', 'owner', 'owner_id', 'organization')
        );

        $user1 = new User(1, null, $this->org1);
        $user2 = new User(2, $this->bu2, $this->org2);
        $user3 = new User(3, $this->bu3, $this->org3);
        $user4 = new User(4, $this->bu4, $this->org4);

        $this->tree->addUserBusinessUnit($user1->getId(), $this->org1->getId(), $this->bu1->getId());
        $this->tree->addUserBusinessUnit($user1->getId(), $this->org2->getId(), $this->bu2->getId());
        $this->tree->addUserBusinessUnit($user2->getId(), $this->org2->getId(), $this->bu2->getId());
        $this->tree->addUserBusinessUnit($user3->getId(), $this->org2->getId(), $this->bu2->getId());
        $this->tree->addUserBusinessUnit($user3->getId(), $this->org3->getId(), $this->bu3->getId());
        $this->tree->addUserBusinessUnit($user4->getId(), $this->org4->getId(), $this->bu4->getId());

        $objReference = new DomainObjectReference(TestEntity::class, 1, $user1->getId());
        $objReference1 = new DomainObjectReference(TestEntity::class, 1, $user2->getId());
        $objReference2 = new DomainObjectReference(TestEntity::class, 1, $user3->getId());
        $objReference3 = new DomainObjectReference(TestEntity::class, 1, $user4->getId());

        $this->assertTrue($this->decisionMaker->isAssociatedWithBusinessUnit($this->user1, $objReference));
        $this->assertTrue($this->decisionMaker->isAssociatedWithBusinessUnit($this->user1, $objReference1));
        $this->assertTrue($this->decisionMaker->isAssociatedWithBusinessUnit($this->user1, $objReference2));
        $this->assertFalse($this->decisionMaker->isAssociatedWithBusinessUnit($this->user1, $objReference3));

        $this->assertTrue($this->decisionMaker->isAssociatedWithBusinessUnit($this->user2, $objReference));
        $this->assertTrue($this->decisionMaker->isAssociatedWithBusinessUnit($this->user2, $objReference1));
        $this->assertTrue($this->decisionMaker->isAssociatedWithBusinessUnit($this->user2, $objReference2));
        $this->assertFalse($this->decisionMaker->isAssociatedWithBusinessUnit($this->user2, $objReference3));

        $this->assertTrue($this->decisionMaker->isAssociatedWithBusinessUnit($this->user3, $objReference));
        $this->assertTrue($this->decisionMaker->isAssociatedWithBusinessUnit($this->user3, $objReference1));
        $this->assertTrue($this->decisionMaker->isAssociatedWithBusinessUnit($this->user3, $objReference2));
        $this->assertFalse($this->decisionMaker->isAssociatedWithBusinessUnit($this->user3, $objReference3));

        $this->assertFalse($this->decisionMaker->isAssociatedWithBusinessUnit($this->user4, $objReference));
        $this->assertFalse($this->decisionMaker->isAssociatedWithBusinessUnit($this->user4, $objReference1));
        $this->assertFalse($this->decisionMaker->isAssociatedWithBusinessUnit($this->user4, $objReference2));
        $this->assertTrue($this->decisionMaker->isAssociatedWithBusinessUnit($this->user4, $objReference3));
    }

    public function testIsAssociatedWithBusinessUnitForOrganizationObject()
    {
        $this->buildTestTree();

        $this->assertFalse($this->decisionMaker->isAssociatedWithBusinessUnit($this->user1, $this->org1));
        $this->assertFalse($this->decisionMaker->isAssociatedWithBusinessUnit($this->user2, $this->org2));
        $this->assertFalse($this->decisionMaker->isAssociatedWithBusinessUnit($this->user3, $this->org3));
        $this->assertFalse($this->decisionMaker->isAssociatedWithBusinessUnit($this->user4, $this->org3));
        $this->assertFalse($this->decisionMaker->isAssociatedWithBusinessUnit($this->user4, $this->org4));
    }

    public function testIsAssociatedWithBusinessUnitForBusinessUnitObject()
    {
        $this->buildTestTree();

        $this->assertTrue($this->decisionMaker->isAssociatedWithBusinessUnit($this->user1, $this->bu1));
        $this->assertTrue($this->decisionMaker->isAssociatedWithBusinessUnit($this->user2, $this->bu2));
        $this->assertTrue($this->decisionMaker->isAssociatedWithBusinessUnit($this->user3, $this->bu3));
        $this->assertFalse($this->decisionMaker->isAssociatedWithBusinessUnit($this->user4, $this->bu3));
        $this->assertFalse($this->decisionMaker->isAssociatedWithBusinessUnit($this->user4, $this->bu3, true));
        $this->assertFalse($this->decisionMaker->isAssociatedWithBusinessUnit($this->user4, $this->bu31));
        $this->assertFalse($this->decisionMaker->isAssociatedWithBusinessUnit($this->user4, $this->bu31, true));
        $this->assertTrue($this->decisionMaker->isAssociatedWithBusinessUnit($this->user4, $this->bu4));
        $this->assertTrue($this->decisionMaker->isAssociatedWithBusinessUnit($this->user4, $this->bu4, true));
        $this->assertFalse($this->decisionMaker->isAssociatedWithBusinessUnit($this->user4, $this->bu41));
        $this->assertTrue($this->decisionMaker->isAssociatedWithBusinessUnit($this->user4, $this->bu41, true));
        $this->assertFalse($this->decisionMaker->isAssociatedWithBusinessUnit($this->user4, $this->bu411));
        $this->assertTrue($this->decisionMaker->isAssociatedWithBusinessUnit($this->user4, $this->bu411, true));
    }

    public function testIsAssociatedWithBusinessUnitForUserObject()
    {
        $this->buildTestTree();

        $this->assertFalse($this->decisionMaker->isAssociatedWithBusinessUnit($this->user1, $this->user1));
        $this->assertTrue($this->decisionMaker->isAssociatedWithBusinessUnit($this->user2, $this->user2));
        $this->assertTrue($this->decisionMaker->isAssociatedWithBusinessUnit($this->user3, $this->user3));
        $this->assertTrue($this->decisionMaker->isAssociatedWithBusinessUnit($this->user3, $this->user3, true));
        $this->assertFalse($this->decisionMaker->isAssociatedWithBusinessUnit($this->user3, $this->user31));
        $this->assertTrue($this->decisionMaker->isAssociatedWithBusinessUnit($this->user3, $this->user31, true));
        $this->assertTrue($this->decisionMaker->isAssociatedWithBusinessUnit($this->user31, $this->user31));
        $this->assertTrue($this->decisionMaker->isAssociatedWithBusinessUnit($this->user31, $this->user31, true));
        $this->assertTrue($this->decisionMaker->isAssociatedWithBusinessUnit($this->user4, $this->user4));
        $this->assertTrue($this->decisionMaker->isAssociatedWithBusinessUnit($this->user4, $this->user4, true));
        $this->assertFalse($this->decisionMaker->isAssociatedWithBusinessUnit($this->user4, $this->user3));
        $this->assertFalse($this->decisionMaker->isAssociatedWithBusinessUnit($this->user4, $this->user3, true));
        $this->assertFalse($this->decisionMaker->isAssociatedWithBusinessUnit($this->user4, $this->user31));
        $this->assertFalse($this->decisionMaker->isAssociatedWithBusinessUnit($this->user4, $this->user31, true));
        $this->assertFalse($this->decisionMaker->isAssociatedWithBusinessUnit($this->user4, $this->user411));
        $this->assertTrue($this->decisionMaker->isAssociatedWithBusinessUnit($this->user4, $this->user411, true));
        $this->assertTrue($this->decisionMaker->isAssociatedWithBusinessUnit($this->user411, $this->user411));
        $this->assertTrue($this->decisionMaker->isAssociatedWithBusinessUnit($this->user411, $this->user411, true));
    }

    public function testIsAssociatedWithBusinessUnitForOrganizationOwnedObject()
    {
        $this->buildTestTree();

        $this->metadataProvider->setMetadata(
            TestEntity::class,
            new OwnershipMetadata('ORGANIZATION', 'owner', 'owner_id')
        );

        $obj = new TestEntity(1);
        $obj1 = new TestEntity(1, $this->org1);
        $obj2 = new TestEntity(1, $this->org2);
        $obj3 = new TestEntity(1, $this->org3);
        $obj4 = new TestEntity(1, $this->org4);

        $this->assertFalse($this->decisionMaker->isAssociatedWithBusinessUnit($this->user1, $obj));
        $this->assertFalse($this->decisionMaker->isAssociatedWithBusinessUnit($this->user1, $obj1));
        $this->assertFalse($this->decisionMaker->isAssociatedWithBusinessUnit($this->user2, $obj));
        $this->assertFalse($this->decisionMaker->isAssociatedWithBusinessUnit($this->user2, $obj2));
        $this->assertFalse($this->decisionMaker->isAssociatedWithBusinessUnit($this->user3, $obj));
        $this->assertFalse($this->decisionMaker->isAssociatedWithBusinessUnit($this->user3, $obj3));
        $this->assertFalse($this->decisionMaker->isAssociatedWithBusinessUnit($this->user4, $obj));
        $this->assertFalse($this->decisionMaker->isAssociatedWithBusinessUnit($this->user4, $obj3));
        $this->assertFalse($this->decisionMaker->isAssociatedWithBusinessUnit($this->user4, $obj4));
    }

    public function testIsAssociatedWithBusinessUnitForBusinessUnitOwnedObject()
    {
        $this->buildTestTree();

        $this->metadataProvider->setMetadata(
            TestEntity::class,
            new OwnershipMetadata('BUSINESS_UNIT', 'owner', 'owner_id', 'organization')
        );

        $obj = new TestEntity(1, null, $this->org1);
        $obj1 = new TestEntity(1, $this->bu1, $this->org1);
        $obj2 = new TestEntity(1, $this->bu2, $this->org2);
        $obj3 = new TestEntity(1, $this->bu3, $this->org3);
        $obj31 = new TestEntity(1, $this->bu31, $this->org3);
        $obj4 = new TestEntity(1, $this->bu4, $this->org4);
        $obj41 = new TestEntity(1, $this->bu41, $this->org4);
        $obj411 = new TestEntity(1, $this->bu411, $this->org4);

        $this->assertFalse($this->decisionMaker->isAssociatedWithBusinessUnit($this->user1, $obj));
        $this->assertTrue($this->decisionMaker->isAssociatedWithBusinessUnit($this->user1, $obj1));
        $this->assertFalse($this->decisionMaker->isAssociatedWithBusinessUnit($this->user2, $obj));
        $this->assertTrue($this->decisionMaker->isAssociatedWithBusinessUnit($this->user2, $obj2));
        $this->assertFalse($this->decisionMaker->isAssociatedWithBusinessUnit($this->user3, $obj));
        $this->assertTrue($this->decisionMaker->isAssociatedWithBusinessUnit($this->user3, $obj3));
        $this->assertFalse($this->decisionMaker->isAssociatedWithBusinessUnit($this->user4, $obj));
        $this->assertFalse($this->decisionMaker->isAssociatedWithBusinessUnit($this->user4, $obj3));
        $this->assertFalse($this->decisionMaker->isAssociatedWithBusinessUnit($this->user4, $obj3, true));
        $this->assertFalse($this->decisionMaker->isAssociatedWithBusinessUnit($this->user4, $obj31));
        $this->assertFalse($this->decisionMaker->isAssociatedWithBusinessUnit($this->user4, $obj31, true));
        $this->assertTrue($this->decisionMaker->isAssociatedWithBusinessUnit($this->user4, $obj4));
        $this->assertTrue($this->decisionMaker->isAssociatedWithBusinessUnit($this->user4, $obj4, true));
        $this->assertFalse($this->decisionMaker->isAssociatedWithBusinessUnit($this->user4, $obj41));
        $this->assertTrue($this->decisionMaker->isAssociatedWithBusinessUnit($this->user4, $obj41, true));
        $this->assertFalse($this->decisionMaker->isAssociatedWithBusinessUnit($this->user4, $obj411));
        $this->assertTrue($this->decisionMaker->isAssociatedWithBusinessUnit($this->user4, $obj411, true));
    }

    public function testIsAssociatedWithBusinessUnitForUserOwnedObject()
    {
        $this->buildTestTree();

        $this->metadataProvider->setMetadata(
            TestEntity::class,
            new OwnershipMetadata('USER', 'owner', 'owner_id', 'organization')
        );

        $obj = new TestEntity(1, null, $this->org1);
        $obj1 = new TestEntity(1, $this->user1, $this->org1);
        $obj2 = new TestEntity(1, $this->user2, $this->org2);
        $obj3 = new TestEntity(1, $this->user3, $this->org3);
        $obj31 = new TestEntity(1, $this->user31, $this->org3);
        $obj4 = new TestEntity(1, $this->user4, $this->org4);
        $obj411 = new TestEntity(1, $this->user411, $this->org4);

        $this->assertFalse($this->decisionMaker->isAssociatedWithBusinessUnit($this->user1, $obj));
        $this->assertTrue($this->decisionMaker->isAssociatedWithBusinessUnit($this->user1, $obj1));
        $this->assertFalse($this->decisionMaker->isAssociatedWithBusinessUnit($this->user2, $obj));
        $this->assertTrue($this->decisionMaker->isAssociatedWithBusinessUnit($this->user2, $obj2));
        $this->assertFalse($this->decisionMaker->isAssociatedWithBusinessUnit($this->user3, $obj));
        $this->assertTrue($this->decisionMaker->isAssociatedWithBusinessUnit($this->user3, $obj3));
        $this->assertFalse($this->decisionMaker->isAssociatedWithBusinessUnit($this->user3, $obj31));
        $this->assertFalse($this->decisionMaker->isAssociatedWithBusinessUnit($this->user4, $obj));
        $this->assertFalse($this->decisionMaker->isAssociatedWithBusinessUnit($this->user4, $obj3));
        $this->assertFalse($this->decisionMaker->isAssociatedWithBusinessUnit($this->user4, $obj3, true));
        $this->assertFalse($this->decisionMaker->isAssociatedWithBusinessUnit($this->user4, $obj31));
        $this->assertFalse($this->decisionMaker->isAssociatedWithBusinessUnit($this->user4, $obj31, true));
        $this->assertTrue($this->decisionMaker->isAssociatedWithBusinessUnit($this->user4, $obj4));
        $this->assertTrue($this->decisionMaker->isAssociatedWithBusinessUnit($this->user4, $obj4, true));
        $this->assertFalse($this->decisionMaker->isAssociatedWithBusinessUnit($this->user4, $obj411));
        $this->assertTrue($this->decisionMaker->isAssociatedWithBusinessUnit($this->user4, $obj411, true));
    }

    public function testIsAssociatedWithUserForDomainObjectOwnedByNullObject()
    {
        $this->buildTestTree();
        $objReference = new DomainObjectReference(TestEntity::class, 1, null);

        $this->assertFalse($this->decisionMaker->isAssociatedWithUser($this->user1, $objReference));
        $this->assertFalse($this->decisionMaker->isAssociatedWithUser($this->user2, $objReference));
        $this->assertFalse($this->decisionMaker->isAssociatedWithUser($this->user3, $objReference));
        $this->assertFalse($this->decisionMaker->isAssociatedWithUser($this->user4, $objReference));
    }

    public function testIsAssociatedWithUserForDomainObjectOwnedByUser()
    {
        $this->buildTestTree();

        $this->metadataProvider->setMetadata(
            TestEntity::class,
            new OwnershipMetadata('USER', 'owner', 'owner_id', 'organization')
        );

        $user1 = new User(1);
        $user2 = new User(2);
        $user3 = new User(3);
        $user4 = new User(4);

        $objReference = new DomainObjectReference(TestEntity::class, 1, $user1->getId());
        $objReference1 = new DomainObjectReference(TestEntity::class, 1, $user2->getId());
        $objReference2 = new DomainObjectReference(TestEntity::class, 1, $user3->getId());
        $objReference3 = new DomainObjectReference(TestEntity::class, 1, $user4->getId());

        $this->assertTrue($this->decisionMaker->isAssociatedWithUser($user1, $objReference));
        $this->assertFalse($this->decisionMaker->isAssociatedWithUser($user1, $objReference1));
        $this->assertFalse($this->decisionMaker->isAssociatedWithUser($user1, $objReference2));
        $this->assertFalse($this->decisionMaker->isAssociatedWithUser($user1, $objReference3));

        $this->assertFalse($this->decisionMaker->isAssociatedWithUser($user2, $objReference));
        $this->assertTrue($this->decisionMaker->isAssociatedWithUser($user2, $objReference1));
        $this->assertFalse($this->decisionMaker->isAssociatedWithUser($user2, $objReference2));
        $this->assertFalse($this->decisionMaker->isAssociatedWithUser($user2, $objReference3));

        $this->assertFalse($this->decisionMaker->isAssociatedWithUser($user3, $objReference));
        $this->assertFalse($this->decisionMaker->isAssociatedWithUser($user3, $objReference1));
        $this->assertTrue($this->decisionMaker->isAssociatedWithUser($user3, $objReference2));
        $this->assertFalse($this->decisionMaker->isAssociatedWithUser($user3, $objReference3));

        $this->assertFalse($this->decisionMaker->isAssociatedWithUser($user4, $objReference));
        $this->assertFalse($this->decisionMaker->isAssociatedWithUser($user4, $objReference1));
        $this->assertFalse($this->decisionMaker->isAssociatedWithUser($user4, $objReference2));
        $this->assertTrue($this->decisionMaker->isAssociatedWithUser($user4, $objReference3));
    }

    public function testIsAssociatedWithUserForUserObject()
    {
        $this->buildTestTree();

        $this->assertTrue($this->decisionMaker->isAssociatedWithUser($this->user1, $this->user1));
        $this->assertTrue($this->decisionMaker->isAssociatedWithUser($this->user2, $this->user2));
        $this->assertTrue($this->decisionMaker->isAssociatedWithUser($this->user3, $this->user3));
        $this->assertFalse($this->decisionMaker->isAssociatedWithUser($this->user3, $this->user31));
        $this->assertTrue($this->decisionMaker->isAssociatedWithUser($this->user31, $this->user31));
        $this->assertTrue($this->decisionMaker->isAssociatedWithUser($this->user4, $this->user4));
        $this->assertFalse($this->decisionMaker->isAssociatedWithUser($this->user4, $this->user3));
        $this->assertFalse($this->decisionMaker->isAssociatedWithUser($this->user4, $this->user411));
        $this->assertTrue($this->decisionMaker->isAssociatedWithUser($this->user411, $this->user411));
    }

    public function testIsAssociatedWithUserForOrganizationOwnedObject()
    {
        $this->buildTestTree();

        $this->metadataProvider->setMetadata(
            TestEntity::class,
            new OwnershipMetadata('ORGANIZATION', 'owner', 'owner_id')
        );

        $obj = new TestEntity(1);
        $obj1 = new TestEntity(1, $this->org1);
        $obj2 = new TestEntity(1, $this->org2);
        $obj3 = new TestEntity(1, $this->org3);
        $obj4 = new TestEntity(1, $this->org4);

        $this->assertFalse($this->decisionMaker->isAssociatedWithUser($this->user1, $obj));
        $this->assertFalse($this->decisionMaker->isAssociatedWithUser($this->user1, $obj1));
        $this->assertFalse($this->decisionMaker->isAssociatedWithUser($this->user2, $obj));
        $this->assertFalse($this->decisionMaker->isAssociatedWithUser($this->user2, $obj2));
        $this->assertFalse($this->decisionMaker->isAssociatedWithUser($this->user3, $obj));
        $this->assertFalse($this->decisionMaker->isAssociatedWithUser($this->user3, $obj3));
        $this->assertFalse($this->decisionMaker->isAssociatedWithUser($this->user4, $obj));
        $this->assertFalse($this->decisionMaker->isAssociatedWithUser($this->user4, $obj3));
        $this->assertFalse($this->decisionMaker->isAssociatedWithUser($this->user4, $obj4));
    }

    public function testIsAssociatedWithUserForBusinessUnitOwnedObject()
    {
        $this->buildTestTree();

        $this->metadataProvider->setMetadata(
            TestEntity::class,
            new OwnershipMetadata('BUSINESS_UNIT', 'owner', 'owner_id')
        );

        $obj = new TestEntity(1);
        $obj1 = new TestEntity(1, $this->bu1);
        $obj2 = new TestEntity(1, $this->bu2);
        $obj3 = new TestEntity(1, $this->bu3);
        $obj31 = new TestEntity(1, $this->bu31);
        $obj4 = new TestEntity(1, $this->bu4);
        $obj41 = new TestEntity(1, $this->bu41);
        $obj411 = new TestEntity(1, $this->bu411);

        $this->assertFalse($this->decisionMaker->isAssociatedWithUser($this->user1, $obj));
        $this->assertFalse($this->decisionMaker->isAssociatedWithUser($this->user1, $obj1));
        $this->assertFalse($this->decisionMaker->isAssociatedWithUser($this->user2, $obj));
        $this->assertFalse($this->decisionMaker->isAssociatedWithUser($this->user2, $obj2));
        $this->assertFalse($this->decisionMaker->isAssociatedWithUser($this->user3, $obj));
        $this->assertFalse($this->decisionMaker->isAssociatedWithUser($this->user3, $obj3));
        $this->assertFalse($this->decisionMaker->isAssociatedWithUser($this->user3, $obj31));
        $this->assertFalse($this->decisionMaker->isAssociatedWithUser($this->user4, $obj));
        $this->assertFalse($this->decisionMaker->isAssociatedWithUser($this->user4, $obj3));
        $this->assertFalse($this->decisionMaker->isAssociatedWithUser($this->user4, $obj31));
        $this->assertFalse($this->decisionMaker->isAssociatedWithUser($this->user4, $obj4));
        $this->assertFalse($this->decisionMaker->isAssociatedWithUser($this->user4, $obj41));
        $this->assertFalse($this->decisionMaker->isAssociatedWithUser($this->user4, $obj411));
    }

    public function testIsAssociatedWithUserForUserOwnedObject()
    {
        $this->buildTestTree();

        $this->metadataProvider->setMetadata(
            TestEntity::class,
            new OwnershipMetadata('USER', 'owner', 'owner_id')
        );

        $obj = new TestEntity(1);
        $obj1 = new TestEntity(1, $this->user1);
        $obj2 = new TestEntity(1, $this->user2);
        $obj3 = new TestEntity(1, $this->user3);
        $obj31 = new TestEntity(1, $this->user31);
        $obj4 = new TestEntity(1, $this->user4);
        $obj411 = new TestEntity(1, $this->user411);

        $this->assertFalse($this->decisionMaker->isAssociatedWithUser($this->user1, $obj));
        $this->assertTrue($this->decisionMaker->isAssociatedWithUser($this->user1, $obj1));
        $this->assertFalse($this->decisionMaker->isAssociatedWithUser($this->user2, $obj));
        $this->assertTrue($this->decisionMaker->isAssociatedWithUser($this->user2, $obj2));
        $this->assertFalse($this->decisionMaker->isAssociatedWithUser($this->user3, $obj));
        $this->assertTrue($this->decisionMaker->isAssociatedWithUser($this->user3, $obj3));
        $this->assertFalse($this->decisionMaker->isAssociatedWithUser($this->user3, $obj31));
        $this->assertFalse($this->decisionMaker->isAssociatedWithUser($this->user4, $obj));
        $this->assertFalse($this->decisionMaker->isAssociatedWithUser($this->user4, $obj3));
        $this->assertFalse($this->decisionMaker->isAssociatedWithUser($this->user4, $obj31));
        $this->assertTrue($this->decisionMaker->isAssociatedWithUser($this->user4, $obj4));
        $this->assertFalse($this->decisionMaker->isAssociatedWithUser($this->user4, $obj411));
    }
}
