<?php

namespace Oro\Bundle\SecurityBundle\Tests\Unit\Owner;

use Symfony\Component\DependencyInjection\ContainerInterface;

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

class AbstractEntityOwnershipDecisionMakerTest extends AbstractCommonEntityOwnershipDecisionMakerTest
{
    /**
     * @var AbstractEntityOwnershipDecisionMaker
     */
    protected $decisionMaker;

    /**
     * @var ContainerInterface
     */
    protected $container;

    protected function setUp()
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

        /** @var OwnerTreeProvider|\PHPUnit_Framework_MockObject_MockObject $treeProvider */
        $treeProvider = $this->getMockBuilder('Oro\Bundle\SecurityBundle\Owner\OwnerTreeProvider')
            ->disableOriginalConstructor()
            ->getMock();

        $treeProvider->expects($this->any())
            ->method('getTree')
            ->will($this->returnValue($this->tree));

        $this->container = $this->getMock('Symfony\Component\DependencyInjection\ContainerInterface');

        $doctrineHelper = $this->getMockBuilder('Oro\Bundle\EntityBundle\ORM\DoctrineHelper')
            ->disableOriginalConstructor()
            ->getMock();

        $this->container->expects($this->any())
            ->method('get')
            ->will(
                $this->returnValueMap(
                    [
                        [
                            'oro_security.ownership_tree_provider.chain',
                            ContainerInterface::EXCEPTION_ON_INVALID_REFERENCE,
                            $treeProvider,
                        ],
                        [
                            'oro_security.owner.metadata_provider.chain',
                            ContainerInterface::EXCEPTION_ON_INVALID_REFERENCE,
                            $this->metadataProvider,
                        ],
                        [
                            'oro_security.acl.object_id_accessor',
                            ContainerInterface::EXCEPTION_ON_INVALID_REFERENCE,
                            new ObjectIdAccessor($doctrineHelper),
                        ],
                        [
                            'oro_security.owner.entity_owner_accessor',
                            ContainerInterface::EXCEPTION_ON_INVALID_REFERENCE,
                            new EntityOwnerAccessor($this->metadataProvider),
                        ],
                    ]
                )
            );

        $this->decisionMaker = $this
            ->getMockBuilder('Oro\Bundle\SecurityBundle\Owner\AbstractEntityOwnershipDecisionMaker')
            ->setMethods(['getContainer'])
            ->getMockForAbstractClass();

        $this->decisionMaker->expects($this->any())->method('getContainer')->willReturn($this->container);
    }

    public function testIsGlobalLevelEntity()
    {
        $this->assertFalse($this->decisionMaker->isGlobalLevelEntity(null));
        $this->assertFalse($this->decisionMaker->isGlobalLevelEntity('test'));
        $this->assertFalse($this->decisionMaker->isGlobalLevelEntity(new User('')));
        $this->assertTrue($this->decisionMaker->isGlobalLevelEntity(new Organization('')));
        $this->assertTrue(
            $this->decisionMaker->isGlobalLevelEntity(
                $this->getMockBuilder('Oro\Bundle\SecurityBundle\Tests\Unit\Acl\Domain\Fixtures\Entity\Organization')
                    ->disableOriginalConstructor()
                    ->getMock()
            )
        );
    }

    public function testIsLocalLevelEntity()
    {
        $this->assertFalse($this->decisionMaker->isLocalLevelEntity(null));
        $this->assertFalse($this->decisionMaker->isLocalLevelEntity('test'));
        $this->assertFalse($this->decisionMaker->isLocalLevelEntity(new User('')));
        $this->assertTrue($this->decisionMaker->isLocalLevelEntity(new BusinessUnit('')));
        $this->assertTrue(
            $this->decisionMaker->isLocalLevelEntity(
                $this->getMockBuilder('Oro\Bundle\SecurityBundle\Tests\Unit\Acl\Domain\Fixtures\Entity\BusinessUnit')
                    ->disableOriginalConstructor()
                    ->getMock()
            )
        );
    }

    public function testIsBasicLevelEntity()
    {
        $this->assertFalse($this->decisionMaker->isBasicLevelEntity(null));
        $this->assertFalse($this->decisionMaker->isBasicLevelEntity('test'));
        $this->assertFalse($this->decisionMaker->isBasicLevelEntity(new BusinessUnit('')));
        $this->assertTrue($this->decisionMaker->isBasicLevelEntity(new User('')));
        $this->assertTrue(
            $this->decisionMaker->isBasicLevelEntity(
                $this->getMockBuilder('Oro\Bundle\SecurityBundle\Tests\Unit\Acl\Domain\Fixtures\Entity\User')
                    ->disableOriginalConstructor()
                    ->getMock()
            )
        );
    }

    /**
     * @expectedException \Symfony\Component\Security\Acl\Exception\InvalidDomainObjectException
     */
    public function testIsAssociatedWithGlobalLevelEntityNullUser()
    {
        $this->decisionMaker->isAssociatedWithGlobalLevelEntity(null, null);
    }

    /**
     * @expectedException \Symfony\Component\Security\Acl\Exception\InvalidDomainObjectException
     */
    public function testIsAssociatedWithGlobalLevelEntityNullObject()
    {
        $user = new User('user');
        $this->decisionMaker->isAssociatedWithGlobalLevelEntity($user, null);
    }

    /**
     * @expectedException \Symfony\Component\Security\Acl\Exception\InvalidDomainObjectException
     */
    public function testIsAssociatedWithLocalLevelEntityNullUser()
    {
        $this->decisionMaker->isAssociatedWithLocalLevelEntity(null, null);
    }

    /**
     * @expectedException \Symfony\Component\Security\Acl\Exception\InvalidDomainObjectException
     */
    public function testIsAssociatedWithLocalLevelEntityNullObject()
    {
        $user = new User('user');
        $this->decisionMaker->isAssociatedWithLocalLevelEntity($user, null);
    }

    /**
     * @expectedException \Symfony\Component\Security\Acl\Exception\InvalidDomainObjectException
     */
    public function testIsAssociatedWithBasicLevelEntityNullUser()
    {
        $this->decisionMaker->isAssociatedWithBasicLevelEntity(null, null);
    }

    /**
     * @expectedException \Symfony\Component\Security\Acl\Exception\InvalidDomainObjectException
     */
    public function testIsAssociatedWithBasicLevelEntityNullObject()
    {
        $user = new User('user');
        $this->decisionMaker->isAssociatedWithBasicLevelEntity($user, null);
    }

    public function testIsAssociatedWithGlobalLevelEntityForSystemObject()
    {
        $user = new User('user');
        $this->assertFalse($this->decisionMaker->isAssociatedWithGlobalLevelEntity($user, new \stdClass()));
    }

    public function testIsAssociatedWithLocalLevelEntityForSystemObject()
    {
        $user = new User('user');
        $this->assertFalse($this->decisionMaker->isAssociatedWithLocalLevelEntity($user, new \stdClass()));
    }

    public function testIsAssociatedWithBasicLevelEntityForSystemObject()
    {
        $user = new User('user');
        $this->assertFalse($this->decisionMaker->isAssociatedWithBasicLevelEntity($user, new \stdClass()));
    }

    public function testIsAssociatedWithGlobalLevelEntityForOrganizationObject()
    {
        $this->buildTestTree();

        $this->assertTrue($this->decisionMaker->isAssociatedWithGlobalLevelEntity($this->user1, $this->org1));
        $this->assertTrue($this->decisionMaker->isAssociatedWithGlobalLevelEntity($this->user2, $this->org2));
        $this->assertTrue($this->decisionMaker->isAssociatedWithGlobalLevelEntity($this->user3, $this->org3));
        $this->assertTrue($this->decisionMaker->isAssociatedWithGlobalLevelEntity($this->user31, $this->org3));
        $this->assertFalse($this->decisionMaker->isAssociatedWithGlobalLevelEntity($this->user4, $this->org3));
        $this->assertTrue($this->decisionMaker->isAssociatedWithGlobalLevelEntity($this->user4, $this->org4));
        $this->assertTrue($this->decisionMaker->isAssociatedWithGlobalLevelEntity($this->user411, $this->org4));
    }

    public function testIsAssociatedWithGlobalLevelEntityForUserObject()
    {
        $this->buildTestTree();

        $this->assertFalse($this->decisionMaker->isAssociatedWithGlobalLevelEntity($this->user1, $this->user1));
        $this->assertFalse($this->decisionMaker->isAssociatedWithGlobalLevelEntity($this->user2, $this->user2));
        $this->assertTrue($this->decisionMaker->isAssociatedWithGlobalLevelEntity($this->user3, $this->user3));
        $this->assertTrue($this->decisionMaker->isAssociatedWithGlobalLevelEntity($this->user3, $this->user31));
        $this->assertTrue($this->decisionMaker->isAssociatedWithGlobalLevelEntity($this->user31, $this->user31));
        $this->assertTrue($this->decisionMaker->isAssociatedWithGlobalLevelEntity($this->user4, $this->user4));
        $this->assertFalse($this->decisionMaker->isAssociatedWithGlobalLevelEntity($this->user4, $this->user3));
        $this->assertFalse($this->decisionMaker->isAssociatedWithGlobalLevelEntity($this->user4, $this->user31));
        $this->assertTrue($this->decisionMaker->isAssociatedWithGlobalLevelEntity($this->user4, $this->user411));
        $this->assertTrue($this->decisionMaker->isAssociatedWithGlobalLevelEntity($this->user411, $this->user411));
    }

    public function testIsAssociatedWithGlobalLevelEntityForOrganizationOwnedObject()
    {
        $this->buildTestTree();

        $this->metadataProvider->setMetadata(
            'Oro\Bundle\SecurityBundle\Tests\Unit\Acl\Domain\Fixtures\Entity\TestEntity',
            new OwnershipMetadata('ORGANIZATION', 'owner', 'owner_id')
        );

        $obj = new TestEntity(1);
        $obj1 = new TestEntity(1, $this->org1);
        $obj2 = new TestEntity(1, $this->org2);
        $obj3 = new TestEntity(1, $this->org3);
        $obj4 = new TestEntity(1, $this->org4);

        $this->assertFalse($this->decisionMaker->isAssociatedWithGlobalLevelEntity($this->user1, $obj));
        $this->assertTrue($this->decisionMaker->isAssociatedWithGlobalLevelEntity($this->user1, $obj1));
        $this->assertFalse($this->decisionMaker->isAssociatedWithGlobalLevelEntity($this->user2, $obj));
        $this->assertTrue($this->decisionMaker->isAssociatedWithGlobalLevelEntity($this->user2, $obj2));
        $this->assertFalse($this->decisionMaker->isAssociatedWithGlobalLevelEntity($this->user3, $obj));
        $this->assertTrue($this->decisionMaker->isAssociatedWithGlobalLevelEntity($this->user3, $obj3));
        $this->assertFalse($this->decisionMaker->isAssociatedWithGlobalLevelEntity($this->user4, $obj));
        $this->assertFalse($this->decisionMaker->isAssociatedWithGlobalLevelEntity($this->user4, $obj3));
        $this->assertTrue($this->decisionMaker->isAssociatedWithGlobalLevelEntity($this->user4, $obj4));
    }

    public function testIsAssociatedWithGlobalLevelEntityForBusinessUnitOwnedObject()
    {
        $this->buildTestTree();

        $this->metadataProvider->setMetadata(
            'Oro\Bundle\SecurityBundle\Tests\Unit\Acl\Domain\Fixtures\Entity\TestEntity',
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

        $this->assertTrue($this->decisionMaker->isAssociatedWithGlobalLevelEntity($this->user1, $obj));
        $this->assertTrue($this->decisionMaker->isAssociatedWithGlobalLevelEntity($this->user1, $obj1));
        $this->assertFalse($this->decisionMaker->isAssociatedWithGlobalLevelEntity($this->user2, $obj));
        $this->assertTrue($this->decisionMaker->isAssociatedWithGlobalLevelEntity($this->user2, $obj2));
        $this->assertFalse($this->decisionMaker->isAssociatedWithGlobalLevelEntity($this->user3, $obj));
        $this->assertTrue($this->decisionMaker->isAssociatedWithGlobalLevelEntity($this->user3, $obj3));
        $this->assertTrue($this->decisionMaker->isAssociatedWithGlobalLevelEntity($this->user3, $obj31));
        $this->assertFalse($this->decisionMaker->isAssociatedWithGlobalLevelEntity($this->user4, $obj));
        $this->assertFalse($this->decisionMaker->isAssociatedWithGlobalLevelEntity($this->user4, $obj3));
        $this->assertFalse($this->decisionMaker->isAssociatedWithGlobalLevelEntity($this->user4, $obj31));
        $this->assertTrue($this->decisionMaker->isAssociatedWithGlobalLevelEntity($this->user4, $obj4));
        $this->assertTrue($this->decisionMaker->isAssociatedWithGlobalLevelEntity($this->user4, $obj41));
        $this->assertTrue($this->decisionMaker->isAssociatedWithGlobalLevelEntity($this->user4, $obj411));
    }

    public function testIsAssociatedWithGlobalLevelEntityForUserOwnedObject()
    {
        $this->buildTestTree();

        $this->metadataProvider->setMetadata(
            'Oro\Bundle\SecurityBundle\Tests\Unit\Acl\Domain\Fixtures\Entity\TestEntity',
            new OwnershipMetadata('USER', 'owner', 'owner_id', 'organization')
        );

        $obj = new TestEntity(1, null, $this->org1);
        $obj1 = new TestEntity(1, $this->user1, $this->org1);
        $obj2 = new TestEntity(1, $this->user2, $this->org2);
        $obj3 = new TestEntity(1, $this->user3, $this->org3);
        $obj31 = new TestEntity(1, $this->user31, $this->org3);
        $obj4 = new TestEntity(1, $this->user4, $this->org4);
        $obj411 = new TestEntity(1, $this->user411, $this->org4);

        $this->assertTrue($this->decisionMaker->isAssociatedWithGlobalLevelEntity($this->user1, $obj));
        $this->assertTrue($this->decisionMaker->isAssociatedWithGlobalLevelEntity($this->user1, $obj1));
        $this->assertFalse($this->decisionMaker->isAssociatedWithGlobalLevelEntity($this->user2, $obj));
        $this->assertTrue($this->decisionMaker->isAssociatedWithGlobalLevelEntity($this->user2, $obj2));
        $this->assertFalse($this->decisionMaker->isAssociatedWithGlobalLevelEntity($this->user3, $obj));
        $this->assertTrue($this->decisionMaker->isAssociatedWithGlobalLevelEntity($this->user3, $obj3));
        $this->assertTrue($this->decisionMaker->isAssociatedWithGlobalLevelEntity($this->user3, $obj31));
        $this->assertFalse($this->decisionMaker->isAssociatedWithGlobalLevelEntity($this->user4, $obj));
        $this->assertFalse($this->decisionMaker->isAssociatedWithGlobalLevelEntity($this->user4, $obj3));
        $this->assertFalse($this->decisionMaker->isAssociatedWithGlobalLevelEntity($this->user4, $obj31));
        $this->assertTrue($this->decisionMaker->isAssociatedWithGlobalLevelEntity($this->user4, $obj4));
        $this->assertTrue($this->decisionMaker->isAssociatedWithGlobalLevelEntity($this->user4, $obj411));
    }

    public function testIsAssociatedWithLocalLevelEntityForOrganizationObject()
    {
        $this->buildTestTree();

        $this->assertFalse($this->decisionMaker->isAssociatedWithLocalLevelEntity($this->user1, $this->org1));
        $this->assertFalse($this->decisionMaker->isAssociatedWithLocalLevelEntity($this->user2, $this->org2));
        $this->assertFalse($this->decisionMaker->isAssociatedWithLocalLevelEntity($this->user3, $this->org3));
        $this->assertFalse($this->decisionMaker->isAssociatedWithLocalLevelEntity($this->user4, $this->org3));
        $this->assertFalse($this->decisionMaker->isAssociatedWithLocalLevelEntity($this->user4, $this->org4));
    }

    public function testIsAssociatedWithLocalLevelEntityForBusinessUnitObject()
    {
        $this->buildTestTree();

        $this->assertTrue($this->decisionMaker->isAssociatedWithLocalLevelEntity($this->user1, $this->bu1));
        $this->assertTrue($this->decisionMaker->isAssociatedWithLocalLevelEntity($this->user2, $this->bu2));
        $this->assertTrue($this->decisionMaker->isAssociatedWithLocalLevelEntity($this->user3, $this->bu3));
        $this->assertFalse($this->decisionMaker->isAssociatedWithLocalLevelEntity($this->user4, $this->bu3));
        $this->assertFalse($this->decisionMaker->isAssociatedWithLocalLevelEntity($this->user4, $this->bu3, true));
        $this->assertFalse($this->decisionMaker->isAssociatedWithLocalLevelEntity($this->user4, $this->bu31));
        $this->assertFalse($this->decisionMaker->isAssociatedWithLocalLevelEntity($this->user4, $this->bu31, true));
        $this->assertTrue($this->decisionMaker->isAssociatedWithLocalLevelEntity($this->user4, $this->bu4));
        $this->assertTrue($this->decisionMaker->isAssociatedWithLocalLevelEntity($this->user4, $this->bu4, true));
        $this->assertFalse($this->decisionMaker->isAssociatedWithLocalLevelEntity($this->user4, $this->bu41));
        $this->assertTrue($this->decisionMaker->isAssociatedWithLocalLevelEntity($this->user4, $this->bu41, true));
        $this->assertFalse($this->decisionMaker->isAssociatedWithLocalLevelEntity($this->user4, $this->bu411));
        $this->assertTrue($this->decisionMaker->isAssociatedWithLocalLevelEntity($this->user4, $this->bu411, true));
    }

    public function testIsAssociatedWithLocalLevelEntityForUserObject()
    {
        $this->buildTestTree();

        $this->assertFalse($this->decisionMaker->isAssociatedWithLocalLevelEntity($this->user1, $this->user1));
        $this->assertTrue($this->decisionMaker->isAssociatedWithLocalLevelEntity($this->user2, $this->user2));
        $this->assertTrue($this->decisionMaker->isAssociatedWithLocalLevelEntity($this->user3, $this->user3));
        $this->assertTrue($this->decisionMaker->isAssociatedWithLocalLevelEntity($this->user3, $this->user3, true));
        $this->assertFalse($this->decisionMaker->isAssociatedWithLocalLevelEntity($this->user3, $this->user31));
        $this->assertTrue($this->decisionMaker->isAssociatedWithLocalLevelEntity($this->user3, $this->user31, true));
        $this->assertTrue($this->decisionMaker->isAssociatedWithLocalLevelEntity($this->user31, $this->user31));
        $this->assertTrue($this->decisionMaker->isAssociatedWithLocalLevelEntity($this->user31, $this->user31, true));
        $this->assertTrue($this->decisionMaker->isAssociatedWithLocalLevelEntity($this->user4, $this->user4));
        $this->assertTrue($this->decisionMaker->isAssociatedWithLocalLevelEntity($this->user4, $this->user4, true));
        $this->assertFalse($this->decisionMaker->isAssociatedWithLocalLevelEntity($this->user4, $this->user3));
        $this->assertFalse($this->decisionMaker->isAssociatedWithLocalLevelEntity($this->user4, $this->user3, true));
        $this->assertFalse($this->decisionMaker->isAssociatedWithLocalLevelEntity($this->user4, $this->user31));
        $this->assertFalse($this->decisionMaker->isAssociatedWithLocalLevelEntity($this->user4, $this->user31, true));
        $this->assertFalse($this->decisionMaker->isAssociatedWithLocalLevelEntity($this->user4, $this->user411));
        $this->assertTrue($this->decisionMaker->isAssociatedWithLocalLevelEntity($this->user4, $this->user411, true));
        $this->assertTrue($this->decisionMaker->isAssociatedWithLocalLevelEntity($this->user411, $this->user411));
        $this->assertTrue($this->decisionMaker->isAssociatedWithLocalLevelEntity($this->user411, $this->user411, true));
    }

    public function testIsAssociatedWithLocalLevelEntityForOrganizationOwnedObject()
    {
        $this->buildTestTree();

        $this->metadataProvider->setMetadata(
            'Oro\Bundle\SecurityBundle\Tests\Unit\Acl\Domain\Fixtures\Entity\TestEntity',
            new OwnershipMetadata('ORGANIZATION', 'owner', 'owner_id')
        );

        $obj = new TestEntity(1);
        $obj1 = new TestEntity(1, $this->org1);
        $obj2 = new TestEntity(1, $this->org2);
        $obj3 = new TestEntity(1, $this->org3);
        $obj4 = new TestEntity(1, $this->org4);

        $this->assertFalse($this->decisionMaker->isAssociatedWithLocalLevelEntity($this->user1, $obj));
        $this->assertFalse($this->decisionMaker->isAssociatedWithLocalLevelEntity($this->user1, $obj1));
        $this->assertFalse($this->decisionMaker->isAssociatedWithLocalLevelEntity($this->user2, $obj));
        $this->assertFalse($this->decisionMaker->isAssociatedWithLocalLevelEntity($this->user2, $obj2));
        $this->assertFalse($this->decisionMaker->isAssociatedWithLocalLevelEntity($this->user3, $obj));
        $this->assertFalse($this->decisionMaker->isAssociatedWithLocalLevelEntity($this->user3, $obj3));
        $this->assertFalse($this->decisionMaker->isAssociatedWithLocalLevelEntity($this->user4, $obj));
        $this->assertFalse($this->decisionMaker->isAssociatedWithLocalLevelEntity($this->user4, $obj3));
        $this->assertFalse($this->decisionMaker->isAssociatedWithLocalLevelEntity($this->user4, $obj4));
    }

    public function testIsAssociatedWithLocalLevelEntityForBusinessUnitOwnedObject()
    {
        $this->buildTestTree();

        $this->metadataProvider->setMetadata(
            'Oro\Bundle\SecurityBundle\Tests\Unit\Acl\Domain\Fixtures\Entity\TestEntity',
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

        $this->assertFalse($this->decisionMaker->isAssociatedWithLocalLevelEntity($this->user1, $obj));
        $this->assertTrue($this->decisionMaker->isAssociatedWithLocalLevelEntity($this->user1, $obj1));
        $this->assertFalse($this->decisionMaker->isAssociatedWithLocalLevelEntity($this->user2, $obj));
        $this->assertTrue($this->decisionMaker->isAssociatedWithLocalLevelEntity($this->user2, $obj2));
        $this->assertFalse($this->decisionMaker->isAssociatedWithLocalLevelEntity($this->user3, $obj));
        $this->assertTrue($this->decisionMaker->isAssociatedWithLocalLevelEntity($this->user3, $obj3));
        $this->assertFalse($this->decisionMaker->isAssociatedWithLocalLevelEntity($this->user4, $obj));
        $this->assertFalse($this->decisionMaker->isAssociatedWithLocalLevelEntity($this->user4, $obj3));
        $this->assertFalse($this->decisionMaker->isAssociatedWithLocalLevelEntity($this->user4, $obj3, true));
        $this->assertFalse($this->decisionMaker->isAssociatedWithLocalLevelEntity($this->user4, $obj31));
        $this->assertFalse($this->decisionMaker->isAssociatedWithLocalLevelEntity($this->user4, $obj31, true));
        $this->assertTrue($this->decisionMaker->isAssociatedWithLocalLevelEntity($this->user4, $obj4));
        $this->assertTrue($this->decisionMaker->isAssociatedWithLocalLevelEntity($this->user4, $obj4, true));
        $this->assertFalse($this->decisionMaker->isAssociatedWithLocalLevelEntity($this->user4, $obj41));
        $this->assertTrue($this->decisionMaker->isAssociatedWithLocalLevelEntity($this->user4, $obj41, true));
        $this->assertFalse($this->decisionMaker->isAssociatedWithLocalLevelEntity($this->user4, $obj411));
        $this->assertTrue($this->decisionMaker->isAssociatedWithLocalLevelEntity($this->user4, $obj411, true));
    }

    public function testIsAssociatedWithLocalLevelEntityForUserOwnedObject()
    {
        $this->buildTestTree();

        $this->metadataProvider->setMetadata(
            'Oro\Bundle\SecurityBundle\Tests\Unit\Acl\Domain\Fixtures\Entity\TestEntity',
            new OwnershipMetadata('USER', 'owner', 'owner_id', 'organization')
        );

        $obj = new TestEntity(1, null, $this->org1);
        $obj1 = new TestEntity(1, $this->user1, $this->org1);
        $obj2 = new TestEntity(1, $this->user2, $this->org2);
        $obj3 = new TestEntity(1, $this->user3, $this->org3);
        $obj31 = new TestEntity(1, $this->user31, $this->org3);
        $obj4 = new TestEntity(1, $this->user4, $this->org4);
        $obj411 = new TestEntity(1, $this->user411, $this->org4);

        $this->assertFalse($this->decisionMaker->isAssociatedWithLocalLevelEntity($this->user1, $obj));
        $this->assertTrue($this->decisionMaker->isAssociatedWithLocalLevelEntity($this->user1, $obj1));
        $this->assertFalse($this->decisionMaker->isAssociatedWithLocalLevelEntity($this->user2, $obj));
        $this->assertTrue($this->decisionMaker->isAssociatedWithLocalLevelEntity($this->user2, $obj2));
        $this->assertFalse($this->decisionMaker->isAssociatedWithLocalLevelEntity($this->user3, $obj));
        $this->assertTrue($this->decisionMaker->isAssociatedWithLocalLevelEntity($this->user3, $obj3));
        $this->assertFalse($this->decisionMaker->isAssociatedWithLocalLevelEntity($this->user3, $obj31));
        $this->assertFalse($this->decisionMaker->isAssociatedWithLocalLevelEntity($this->user4, $obj));
        $this->assertFalse($this->decisionMaker->isAssociatedWithLocalLevelEntity($this->user4, $obj3));
        $this->assertFalse($this->decisionMaker->isAssociatedWithLocalLevelEntity($this->user4, $obj3, true));
        $this->assertFalse($this->decisionMaker->isAssociatedWithLocalLevelEntity($this->user4, $obj31));
        $this->assertFalse($this->decisionMaker->isAssociatedWithLocalLevelEntity($this->user4, $obj31, true));
        $this->assertTrue($this->decisionMaker->isAssociatedWithLocalLevelEntity($this->user4, $obj4));
        $this->assertTrue($this->decisionMaker->isAssociatedWithLocalLevelEntity($this->user4, $obj4, true));
        $this->assertFalse($this->decisionMaker->isAssociatedWithLocalLevelEntity($this->user4, $obj411));
        $this->assertTrue($this->decisionMaker->isAssociatedWithLocalLevelEntity($this->user4, $obj411, true));
    }

    public function testIsAssociatedWithBasicLevelEntityForUserObject()
    {
        $this->buildTestTree();

        $this->assertTrue($this->decisionMaker->isAssociatedWithBasicLevelEntity($this->user1, $this->user1));
        $this->assertTrue($this->decisionMaker->isAssociatedWithBasicLevelEntity($this->user2, $this->user2));
        $this->assertTrue($this->decisionMaker->isAssociatedWithBasicLevelEntity($this->user3, $this->user3));
        $this->assertFalse($this->decisionMaker->isAssociatedWithBasicLevelEntity($this->user3, $this->user31));
        $this->assertTrue($this->decisionMaker->isAssociatedWithBasicLevelEntity($this->user31, $this->user31));
        $this->assertTrue($this->decisionMaker->isAssociatedWithBasicLevelEntity($this->user4, $this->user4));
        $this->assertFalse($this->decisionMaker->isAssociatedWithBasicLevelEntity($this->user4, $this->user3));
        $this->assertFalse($this->decisionMaker->isAssociatedWithBasicLevelEntity($this->user4, $this->user411));
        $this->assertTrue($this->decisionMaker->isAssociatedWithBasicLevelEntity($this->user411, $this->user411));
    }

    public function testIsAssociatedWithBasicLevelEntityForOrganizationOwnedObject()
    {
        $this->buildTestTree();

        $this->metadataProvider->setMetadata(
            'Oro\Bundle\SecurityBundle\Tests\Unit\Acl\Domain\Fixtures\Entity\TestEntity',
            new OwnershipMetadata('ORGANIZATION', 'owner', 'owner_id')
        );

        $obj = new TestEntity(1);
        $obj1 = new TestEntity(1, $this->org1);
        $obj2 = new TestEntity(1, $this->org2);
        $obj3 = new TestEntity(1, $this->org3);
        $obj4 = new TestEntity(1, $this->org4);

        $this->assertFalse($this->decisionMaker->isAssociatedWithBasicLevelEntity($this->user1, $obj));
        $this->assertFalse($this->decisionMaker->isAssociatedWithBasicLevelEntity($this->user1, $obj1));
        $this->assertFalse($this->decisionMaker->isAssociatedWithBasicLevelEntity($this->user2, $obj));
        $this->assertFalse($this->decisionMaker->isAssociatedWithBasicLevelEntity($this->user2, $obj2));
        $this->assertFalse($this->decisionMaker->isAssociatedWithBasicLevelEntity($this->user3, $obj));
        $this->assertFalse($this->decisionMaker->isAssociatedWithBasicLevelEntity($this->user3, $obj3));
        $this->assertFalse($this->decisionMaker->isAssociatedWithBasicLevelEntity($this->user4, $obj));
        $this->assertFalse($this->decisionMaker->isAssociatedWithBasicLevelEntity($this->user4, $obj3));
        $this->assertFalse($this->decisionMaker->isAssociatedWithBasicLevelEntity($this->user4, $obj4));
    }

    public function testIsAssociatedWithBasicLevelEntityForBusinessUnitOwnedObject()
    {
        $this->buildTestTree();

        $this->metadataProvider->setMetadata(
            'Oro\Bundle\SecurityBundle\Tests\Unit\Acl\Domain\Fixtures\Entity\TestEntity',
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

        $this->assertFalse($this->decisionMaker->isAssociatedWithBasicLevelEntity($this->user1, $obj));
        $this->assertFalse($this->decisionMaker->isAssociatedWithBasicLevelEntity($this->user1, $obj1));
        $this->assertFalse($this->decisionMaker->isAssociatedWithBasicLevelEntity($this->user2, $obj));
        $this->assertFalse($this->decisionMaker->isAssociatedWithBasicLevelEntity($this->user2, $obj2));
        $this->assertFalse($this->decisionMaker->isAssociatedWithBasicLevelEntity($this->user3, $obj));
        $this->assertFalse($this->decisionMaker->isAssociatedWithBasicLevelEntity($this->user3, $obj3));
        $this->assertFalse($this->decisionMaker->isAssociatedWithBasicLevelEntity($this->user3, $obj31));
        $this->assertFalse($this->decisionMaker->isAssociatedWithBasicLevelEntity($this->user4, $obj));
        $this->assertFalse($this->decisionMaker->isAssociatedWithBasicLevelEntity($this->user4, $obj3));
        $this->assertFalse($this->decisionMaker->isAssociatedWithBasicLevelEntity($this->user4, $obj31));
        $this->assertFalse($this->decisionMaker->isAssociatedWithBasicLevelEntity($this->user4, $obj4));
        $this->assertFalse($this->decisionMaker->isAssociatedWithBasicLevelEntity($this->user4, $obj41));
        $this->assertFalse($this->decisionMaker->isAssociatedWithBasicLevelEntity($this->user4, $obj411));
    }

    public function testIsAssociatedWithBasicLevelEntityForUserOwnedObject()
    {
        $this->buildTestTree();

        $this->metadataProvider->setMetadata(
            'Oro\Bundle\SecurityBundle\Tests\Unit\Acl\Domain\Fixtures\Entity\TestEntity',
            new OwnershipMetadata('USER', 'owner', 'owner_id')
        );

        $obj = new TestEntity(1);
        $obj1 = new TestEntity(1, $this->user1);
        $obj2 = new TestEntity(1, $this->user2);
        $obj3 = new TestEntity(1, $this->user3);
        $obj31 = new TestEntity(1, $this->user31);
        $obj4 = new TestEntity(1, $this->user4);
        $obj411 = new TestEntity(1, $this->user411);

        $this->assertFalse($this->decisionMaker->isAssociatedWithBasicLevelEntity($this->user1, $obj));
        $this->assertTrue($this->decisionMaker->isAssociatedWithBasicLevelEntity($this->user1, $obj1));
        $this->assertFalse($this->decisionMaker->isAssociatedWithBasicLevelEntity($this->user2, $obj));
        $this->assertTrue($this->decisionMaker->isAssociatedWithBasicLevelEntity($this->user2, $obj2));
        $this->assertFalse($this->decisionMaker->isAssociatedWithBasicLevelEntity($this->user3, $obj));
        $this->assertTrue($this->decisionMaker->isAssociatedWithBasicLevelEntity($this->user3, $obj3));
        $this->assertFalse($this->decisionMaker->isAssociatedWithBasicLevelEntity($this->user3, $obj31));
        $this->assertFalse($this->decisionMaker->isAssociatedWithBasicLevelEntity($this->user4, $obj));
        $this->assertFalse($this->decisionMaker->isAssociatedWithBasicLevelEntity($this->user4, $obj3));
        $this->assertFalse($this->decisionMaker->isAssociatedWithBasicLevelEntity($this->user4, $obj31));
        $this->assertTrue($this->decisionMaker->isAssociatedWithBasicLevelEntity($this->user4, $obj4));
        $this->assertFalse($this->decisionMaker->isAssociatedWithBasicLevelEntity($this->user4, $obj411));
    }
}
