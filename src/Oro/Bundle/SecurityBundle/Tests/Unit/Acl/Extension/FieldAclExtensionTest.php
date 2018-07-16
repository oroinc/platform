<?php

namespace Oro\Bundle\SecurityBundle\Tests\Unit\Acl\Extension;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\SecurityBundle\Acl\AccessLevel;
use Oro\Bundle\SecurityBundle\Acl\Domain\ObjectIdAccessor;
use Oro\Bundle\SecurityBundle\Acl\Domain\ObjectIdentityFactory;
use Oro\Bundle\SecurityBundle\Acl\Extension\EntityAclExtension;
use Oro\Bundle\SecurityBundle\Acl\Extension\FieldAclExtension;
use Oro\Bundle\SecurityBundle\Acl\Extension\FieldMaskBuilder;
use Oro\Bundle\SecurityBundle\Authentication\TokenAccessorInterface;
use Oro\Bundle\SecurityBundle\Metadata\EntitySecurityMetadataProvider;
use Oro\Bundle\SecurityBundle\Owner\EntityOwnerAccessor;
use Oro\Bundle\SecurityBundle\Owner\EntityOwnershipDecisionMaker;
use Oro\Bundle\SecurityBundle\Owner\Metadata\OwnershipMetadata;
use Oro\Bundle\SecurityBundle\Owner\OwnerTree;
use Oro\Bundle\SecurityBundle\Owner\OwnerTreeProvider;
use Oro\Bundle\SecurityBundle\Tests\Unit\Acl\Domain\Fixtures\Entity\BusinessUnit;
use Oro\Bundle\SecurityBundle\Tests\Unit\Acl\Domain\Fixtures\Entity\Organization;
use Oro\Bundle\SecurityBundle\Tests\Unit\Acl\Domain\Fixtures\Entity\TestEntity;
use Oro\Bundle\SecurityBundle\Tests\Unit\Acl\Domain\Fixtures\Entity\User;
use Oro\Bundle\SecurityBundle\Tests\Unit\Stub\OwnershipMetadataProviderStub;
use Oro\Bundle\SecurityBundle\Tests\Unit\TestHelper;
use Symfony\Component\Security\Acl\Domain\ObjectIdentity;

/**
 * @SuppressWarnings(PHPMD.ExcessiveClassLength)
 */
class FieldAclExtensionTest extends \PHPUnit\Framework\TestCase
{
    /** @var FieldAclExtension */
    protected $extension;

    /** @var EntitySecurityMetadataProvider|\PHPUnit\Framework\MockObject\MockObject */
    protected $securityMetadataProvider;

    /** @var OwnershipMetadataProviderStub */
    protected $metadataProvider;

    /** @var OwnerTree */
    protected $tree;

    /** @var Organization */
    protected $org1;

    /** @var Organization */
    protected $org2;

    /** @var Organization */
    protected $org3;

    /** @var Organization */
    protected $org4;

    /** @var BusinessUnit */
    protected $bu1;

    /** @var BusinessUnit */
    protected $bu2;

    /** @var BusinessUnit */
    protected $bu3;

    /** @var BusinessUnit */
    protected $bu31;

    /** @var BusinessUnit */
    protected $bu4;

    /** @var BusinessUnit */
    protected $bu41;

    /** @var BusinessUnit */
    protected $bu411;

    /** @var User */
    protected $user1;

    /** @var User */
    protected $user2;

    /** @var User */
    protected $user3;

    /** @var User */
    protected $user31;

    /** @var User */
    protected $user4;

    /** @var User */
    protected $user411;

    /** @var EntityOwnershipDecisionMaker */
    protected $decisionMaker;

    /** @var \PHPUnit\Framework\MockObject\MockObject|DoctrineHelper */
    protected $doctrineHelper;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    protected $configManager;

    protected function setUp()
    {
        $this->tree = new OwnerTree();

        $this->doctrineHelper = $this->getMockBuilder('Oro\Bundle\EntityBundle\ORM\DoctrineHelper')
            ->disableOriginalConstructor()
            ->getMock();

        $this->securityMetadataProvider = $this
            ->getMockBuilder('Oro\Bundle\SecurityBundle\Metadata\EntitySecurityMetadataProvider')
            ->disableOriginalConstructor()
            ->getMock();

        $this->metadataProvider = new OwnershipMetadataProviderStub($this);
        $this->metadataProvider->setMetadata(
            $this->metadataProvider->getOrganizationClass(),
            new OwnershipMetadata()
        );
        $this->metadataProvider->setMetadata(
            $this->metadataProvider->getBusinessUnitClass(),
            new OwnershipMetadata('BUSINESS_UNIT', 'owner', 'owner_id')
        );
        $this->metadataProvider->setMetadata(
            $this->metadataProvider->getUserClass(),
            new OwnershipMetadata('BUSINESS_UNIT', 'owner', 'owner_id')
        );

        /** @var \PHPUnit\Framework\MockObject\MockObject|OwnerTreeProvider $treeProviderMock */
        $treeProviderMock = $this->getMockBuilder('Oro\Bundle\SecurityBundle\Owner\OwnerTreeProvider')
            ->disableOriginalConstructor()
            ->getMock();

        $treeProviderMock->expects($this->any())
            ->method('getTree')
            ->will($this->returnValue($this->tree));

        $this->decisionMaker = new EntityOwnershipDecisionMaker(
            $treeProviderMock,
            new ObjectIdAccessor($this->doctrineHelper),
            new EntityOwnerAccessor($this->metadataProvider),
            $this->metadataProvider,
            $this->createMock(TokenAccessorInterface::class)
        );

        $this->configManager = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Config\ConfigManager')
            ->disableOriginalConstructor()
            ->getMock();

        $this->extension = TestHelper::get($this)->createFieldAclExtension(
            $this->metadataProvider,
            $this->tree,
            new ObjectIdAccessor($this->doctrineHelper),
            $this->decisionMaker,
            $this->configManager
        );
    }

    protected function buildTestTree()
    {
        /**
         * org1  org2     org3         org4
         *                |            |
         *  bu1   bu2     +-bu3        +-bu4
         *        |       | |            |
         *        |       | +-bu31       |
         *        |       | | |          |
         *        |       | | +-user31   |
         *        |       | |            |
         *  user1 +-user2 | +-user3      +-user4
         *                |                |
         *                +-bu3a           +-bu3
         *                  |              +-bu4
         *                  +-bu3a1          |
         *                                   +-bu41
         *                                     |
         *                                     +-bu411
         *                                       |
         *                                       +-user411
         *
         * user1 user2 user3 user31 user4 user411
         *
         * org1  org2  org3  org3   org4  org4
         * org2        org2
         *
         * bu1   bu2   bu3   bu31   bu4   bu411
         * bu2         bu2
         *
         */
        $this->tree->addBusinessUnit('bu1', null);
        $this->tree->addBusinessUnit('bu2', null);
        $this->tree->addBusinessUnit('bu3', 'org3');
        $this->tree->addBusinessUnit('bu31', 'org3');
        $this->tree->addBusinessUnit('bu3a', 'org3');
        $this->tree->addBusinessUnit('bu3a1', 'org3');
        $this->tree->addBusinessUnit('bu4', 'org4');
        $this->tree->addBusinessUnit('bu41', 'org4');
        $this->tree->addBusinessUnit('bu411', 'org4');

        $this->tree->addBusinessUnitRelation('bu1', null);
        $this->tree->addBusinessUnitRelation('bu2', null);
        $this->tree->addBusinessUnitRelation('bu3', null);
        $this->tree->addBusinessUnitRelation('bu31', 'bu3');
        $this->tree->addBusinessUnitRelation('bu3a', null);
        $this->tree->addBusinessUnitRelation('bu3a1', 'bu3a');
        $this->tree->addBusinessUnitRelation('bu4', null);
        $this->tree->addBusinessUnitRelation('bu41', 'bu4');
        $this->tree->addBusinessUnitRelation('bu411', 'bu41');

        $this->tree->addUser('user1', null);
        $this->tree->addUser('user2', 'bu2');
        $this->tree->addUser('user3', 'bu3');
        $this->tree->addUser('user31', 'bu31');
        $this->tree->addUser('user4', 'bu4');
        $this->tree->addUser('user41', 'bu41');
        $this->tree->addUser('user411', 'bu411');

        $this->tree->addUserOrganization('user1', 'org1');
        $this->tree->addUserOrganization('user1', 'org2');
        $this->tree->addUserOrganization('user2', 'org2');
        $this->tree->addUserOrganization('user3', 'org2');
        $this->tree->addUserOrganization('user3', 'org3');
        $this->tree->addUserOrganization('user31', 'org3');
        $this->tree->addUserOrganization('user4', 'org4');
        $this->tree->addUserOrganization('user411', 'org4');

        $this->tree->addUserBusinessUnit('user1', 'org1', 'bu1');
        $this->tree->addUserBusinessUnit('user1', 'org2', 'bu2');
        $this->tree->addUserBusinessUnit('user2', 'org2', 'bu2');
        $this->tree->addUserBusinessUnit('user3', 'org3', 'bu3');
        $this->tree->addUserBusinessUnit('user3', 'org2', 'bu2');
        $this->tree->addUserBusinessUnit('user31', 'org3', 'bu31');
        $this->tree->addUserBusinessUnit('user4', 'org4', 'bu4');
        $this->tree->addUserBusinessUnit('user411', 'org4', 'bu411');

        $this->tree->buildTree();
    }

    /**
     * @dataProvider validateMaskForOrganizationProvider
     */
    public function testValidateMaskForOrganization($mask)
    {
        $this->extension->validateMask($mask, new Organization());
    }

    /**
     * @dataProvider validateMaskForOrganizationInvalidProvider
     * @expectedException \Oro\Bundle\SecurityBundle\Acl\Exception\InvalidAclMaskException
     */
    public function testValidateMaskForOrganizationInvalid($mask)
    {
        $this->extension->validateMask($mask, new Organization());
    }

    /**
     * @dataProvider validateMaskForBusinessUnitProvider
     */
    public function testValidateMaskForBusinessUnit($mask)
    {
        $this->extension->validateMask($mask, new BusinessUnit());
    }

    /**
     * @dataProvider validateMaskForBusinessUnitInvalidProvider
     * @expectedException \Oro\Bundle\SecurityBundle\Acl\Exception\InvalidAclMaskException
     */
    public function testValidateMaskForBusinessUnitInvalid($mask)
    {
        $this->extension->validateMask($mask, new BusinessUnit());
    }

    /**
     * @dataProvider validateMaskForUserProvider
     */
    public function testValidateMaskForUser($mask)
    {
        $this->extension->validateMask($mask, new User());
    }

    /**
     * @dataProvider validateMaskForUserInvalidProvider
     * @expectedException \Oro\Bundle\SecurityBundle\Acl\Exception\InvalidAclMaskException
     */
    public function testValidateMaskForUserInvalid($mask)
    {
        $this->extension->validateMask($mask, new User());
    }

    /**
     * @dataProvider validateMaskForOrganizationOwnedProvider
     */
    public function testValidateMaskForOrganizationOwned($mask)
    {
        $this->metadataProvider->setMetadata(
            'Oro\Bundle\SecurityBundle\Tests\Unit\Acl\Domain\Fixtures\Entity\TestEntity',
            new OwnershipMetadata('ORGANIZATION', 'owner', 'owner_id')
        );
        $this->extension->validateMask($mask, new TestEntity());
    }

    /**
     * @dataProvider validateMaskForOrganizationOwnedInvalidProvider
     * @expectedException \Oro\Bundle\SecurityBundle\Acl\Exception\InvalidAclMaskException
     */
    public function testValidateMaskForOrganizationOwnedInvalid($mask)
    {
        $this->metadataProvider->setMetadata(
            'Oro\Bundle\SecurityBundle\Tests\Unit\Acl\Domain\Fixtures\Entity\TestEntity',
            new OwnershipMetadata('ORGANIZATION', 'owner', 'owner_id')
        );
        $this->extension->validateMask($mask, new TestEntity());
    }

    /**
     * @dataProvider validateMaskForUserOwnedProvider
     */
    public function testValidateMaskForUserOwned($mask)
    {
        $this->metadataProvider->setMetadata(
            'Oro\Bundle\SecurityBundle\Tests\Unit\Acl\Domain\Fixtures\Entity\TestEntity',
            new OwnershipMetadata('USER', 'owner', 'owner_id')
        );
        $this->extension->validateMask($mask, new TestEntity());
    }

    /**
     * @dataProvider validateMaskForUserOwnedInvalidProvider
     * @expectedException \Oro\Bundle\SecurityBundle\Acl\Exception\InvalidAclMaskException
     */
    public function testValidateMaskForUserOwnedInvalid($mask)
    {
        $this->metadataProvider->setMetadata(
            'Oro\Bundle\SecurityBundle\Tests\Unit\Acl\Domain\Fixtures\Entity\TestEntity',
            new OwnershipMetadata('USER', 'owner', 'owner_id')
        );
        $this->extension->validateMask($mask, new TestEntity());
    }

    /**
     * @dataProvider validateMaskForUserOwnedInvalidProvider
     * @expectedException \Oro\Bundle\SecurityBundle\Acl\Exception\InvalidAclMaskException
     */
    public function testValidateMaskForRootInvalid($mask)
    {
        $this->extension->validateMask($mask, new ObjectIdentity('entity', ObjectIdentityFactory::ROOT_IDENTITY_TYPE));
    }

    public function testGetAllPermissions()
    {
        $this->assertEquals(
            ['VIEW', 'CREATE', 'EDIT'],
            $this->extension->getPermissions()
        );
    }

    /**
     * @dataProvider decideIsGrantingProvider
     */
    public function testDecideIsGranting($triggeredMask, $user, $organization, $object, $expectedResult)
    {
        $this->buildTestTree();
        if ($object instanceof TestEntity && $object->getOwner() !== null) {
            $owner = $object->getOwner();
            if (is_a($owner, $this->metadataProvider->getOrganizationClass())) {
                $this->metadataProvider->setMetadata(
                    get_class($object),
                    new OwnershipMetadata('ORGANIZATION', 'owner', 'owner_id', 'organization')
                );
            } elseif (is_a($owner, $this->metadataProvider->getBusinessUnitClass())) {
                $this->metadataProvider->setMetadata(
                    get_class($object),
                    new OwnershipMetadata('BUSINESS_UNIT', 'owner', 'owner_id', 'organization')
                );
            } elseif (is_a($owner, $this->metadataProvider->getUserClass())) {
                $this->metadataProvider->setMetadata(
                    get_class($object),
                    new OwnershipMetadata('USER', 'owner', 'owner_id', 'organization')
                );
            }
        }
        $token =
            $this->getMockBuilder('Oro\Bundle\SecurityBundle\Authentication\Token\UsernamePasswordOrganizationToken')
                ->disableOriginalConstructor()
                ->getMock();
        $token->expects($this->any())
            ->method('getOrganizationContext')
            ->will($this->returnValue($organization));
        $token->expects($this->any())
            ->method('getUser')
            ->will($this->returnValue($user));
        $this->assertEquals(
            $expectedResult,
            $this->extension->decideIsGranting($triggeredMask, $object, $token)
        );
    }

    public function testGetMaskBuilder()
    {
        $this->assertEquals(new FieldMaskBuilder(), $this->extension->getMaskBuilder('VIEW'));
    }

    public function testGetAllMaskBuilders()
    {
        $this->assertEquals([new FieldMaskBuilder()], $this->extension->getAllMaskBuilders());
    }

    public function testGetExtensionKey()
    {
        $this->assertEquals(EntityAclExtension::NAME, $this->extension->getExtensionKey());
    }

    /**
     * @dataProvider getServiceBitsProvider
     */
    public function testGetServiceBits($mask, $expectedMask)
    {
        self::assertEquals($expectedMask, $this->extension->getServiceBits($mask));
    }

    public function getServiceBitsProvider()
    {
        return [
            'zero mask'                        => [
                FieldMaskBuilder::GROUP_NONE,
                FieldMaskBuilder::GROUP_NONE
            ],
            'not zero mask'                    => [
                FieldMaskBuilder::MASK_EDIT_SYSTEM,
                FieldMaskBuilder::GROUP_NONE
            ],
            'zero mask, not zero identity'     => [
                FieldMaskBuilder::REMOVE_SERVICE_BITS + 1,
                FieldMaskBuilder::REMOVE_SERVICE_BITS + 1
            ],
            'not zero mask, not zero identity' => [
                (FieldMaskBuilder::REMOVE_SERVICE_BITS + 1) | FieldMaskBuilder::MASK_EDIT_SYSTEM,
                FieldMaskBuilder::REMOVE_SERVICE_BITS + 1
            ],
        ];
    }

    /**
     * @dataProvider removeServiceBitsProvider
     */
    public function testRemoveServiceBits($mask, $expectedMask)
    {
        self::assertEquals($expectedMask, $this->extension->removeServiceBits($mask));
    }

    public function removeServiceBitsProvider()
    {
        return [
            'zero mask'                        => [
                FieldMaskBuilder::GROUP_NONE,
                FieldMaskBuilder::GROUP_NONE
            ],
            'not zero mask'                    => [
                FieldMaskBuilder::MASK_EDIT_SYSTEM,
                FieldMaskBuilder::MASK_EDIT_SYSTEM
            ],
            'zero mask, not zero identity'     => [
                FieldMaskBuilder::REMOVE_SERVICE_BITS + 1,
                FieldMaskBuilder::GROUP_NONE
            ],
            'not zero mask, not zero identity' => [
                (FieldMaskBuilder::REMOVE_SERVICE_BITS + 1) | FieldMaskBuilder::MASK_EDIT_SYSTEM,
                FieldMaskBuilder::MASK_EDIT_SYSTEM
            ],
        ];
    }

    public function testSupportsForRootObjectIdentity()
    {
        self::assertTrue(
            $this->extension->supports(ObjectIdentityFactory::ROOT_IDENTITY_TYPE, '')
        );
    }

    public function testSupportsForNotConfigurableEntity()
    {
        $entityClass = 'Test\Entity';

        $this->configManager->expects($this->once())
            ->method('hasConfig')
            ->with($entityClass)
            ->willReturn(false);

        self::assertFalse(
            $this->extension->supports($entityClass, '')
        );
    }

    public function testSupportsWhenFieldAclIsEnabled()
    {
        $entityClass = 'Test\Entity';

        $config = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Config\Config')
            ->disableOriginalConstructor()
            ->getMock();
        $config->expects($this->exactly(2))
            ->method('get')
            ->willReturnMap(
                [
                    ['field_acl_supported', false, null, true],
                    ['field_acl_enabled', false, null, true]
                ]
            );
        $this->configManager->expects($this->once())
            ->method('hasConfig')
            ->with($entityClass)
            ->willReturn(true);
        $this->configManager->expects($this->once())
            ->method('getEntityConfig')
            ->with('security', $entityClass)
            ->willReturn($config);

        self::assertTrue(
            $this->extension->supports($entityClass, '')
        );
    }

    public function testSupportsWhenFieldAclIsDisabled()
    {
        $entityClass = 'Test\Entity';

        $config = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Config\Config')
            ->disableOriginalConstructor()
            ->getMock();
        $config->expects($this->exactly(2))
            ->method('get')
            ->willReturnMap(
                [
                    ['field_acl_supported', false, null, true],
                    ['field_acl_enabled', false, null, false]
                ]
            );
        $this->configManager->expects($this->once())
            ->method('hasConfig')
            ->with($entityClass)
            ->willReturn(true);
        $this->configManager->expects($this->once())
            ->method('getEntityConfig')
            ->with('security', $entityClass)
            ->willReturn($config);

        self::assertFalse(
            $this->extension->supports($entityClass, '')
        );
    }

    public function testSupportsWhenFieldAclIsNotSupported()
    {
        $entityClass = 'Test\Entity';

        $config = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Config\Config')
            ->disableOriginalConstructor()
            ->getMock();
        $config->expects($this->once())
            ->method('get')
            ->with('field_acl_supported', false, null)
            ->willReturn(false);
        $this->configManager->expects($this->once())
            ->method('hasConfig')
            ->with($entityClass)
            ->willReturn(true);
        $this->configManager->expects($this->once())
            ->method('getEntityConfig')
            ->with('security', $entityClass)
            ->willReturn($config);

        self::assertFalse(
            $this->extension->supports($entityClass, '')
        );
    }

    public function testSupportsWhenTypeContainsFieldName()
    {
        $entityClass = 'Test\Entity';

        $config = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Config\Config')
            ->disableOriginalConstructor()
            ->getMock();
        $config->expects($this->exactly(2))
            ->method('get')
            ->willReturnMap(
                [
                    ['field_acl_supported', false, null, true],
                    ['field_acl_enabled', false, null, true]
                ]
            );
        $this->configManager->expects($this->once())
            ->method('hasConfig')
            ->with($entityClass)
            ->willReturn(true);
        $this->configManager->expects($this->once())
            ->method('getEntityConfig')
            ->with('security', $entityClass)
            ->willReturn($config);

        self::assertTrue(
            $this->extension->supports($entityClass . '::testField', '')
        );
    }

    public function testSupportsWhenTypeContainsGroupAndFieldName()
    {
        $entityClass = 'Test\Entity';

        $config = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Config\Config')
            ->disableOriginalConstructor()
            ->getMock();
        $config->expects($this->exactly(2))
            ->method('get')
            ->willReturnMap(
                [
                    ['field_acl_supported', false, null, true],
                    ['field_acl_enabled', false, null, true]
                ]
            );
        $this->configManager->expects($this->once())
            ->method('hasConfig')
            ->with($entityClass)
            ->willReturn(true);
        $this->configManager->expects($this->once())
            ->method('getEntityConfig')
            ->with('security', $entityClass)
            ->willReturn($config);

        self::assertTrue(
            $this->extension->supports($entityClass . '::testField@testGroup', '')
        );
    }

    /**
     * @expectedException \LogicException
     */
    public function testGetClasses()
    {
        $this->extension->getClasses();
    }

    /**
     * @expectedException \LogicException
     */
    public function testGetObjectIdentity()
    {
        $this->extension->getObjectIdentity('');
    }

    public function testGetAccessLevel()
    {
        $this->assertEquals(
            AccessLevel::NONE_LEVEL,
            $this->extension->getAccessLevel(FieldMaskBuilder::GROUP_NONE)
        );
        $this->assertEquals(
            AccessLevel::SYSTEM_LEVEL,
            $this->extension->getAccessLevel(FieldMaskBuilder::MASK_VIEW_SYSTEM)
        );
        $this->assertEquals(
            AccessLevel::GLOBAL_LEVEL,
            $this->extension->getAccessLevel(FieldMaskBuilder::MASK_VIEW_GLOBAL)
        );
        $this->assertEquals(
            AccessLevel::DEEP_LEVEL,
            $this->extension->getAccessLevel(FieldMaskBuilder::MASK_VIEW_DEEP)
        );
        $this->assertEquals(
            AccessLevel::LOCAL_LEVEL,
            $this->extension->getAccessLevel(FieldMaskBuilder::MASK_VIEW_LOCAL)
        );
        $this->assertEquals(
            AccessLevel::BASIC_LEVEL,
            $this->extension->getAccessLevel(FieldMaskBuilder::MASK_VIEW_BASIC)
        );
        $this->assertEquals(
            AccessLevel::SYSTEM_LEVEL,
            $this->extension->getAccessLevel(
                FieldMaskBuilder::MASK_VIEW_SYSTEM | FieldMaskBuilder::MASK_EDIT_BASIC,
                'VIEW'
            )
        );
        $this->assertEquals(
            AccessLevel::BASIC_LEVEL,
            $this->extension->getAccessLevel(
                FieldMaskBuilder::MASK_VIEW_SYSTEM | FieldMaskBuilder::MASK_EDIT_BASIC,
                'EDIT'
            )
        );
        $this->assertEquals(
            AccessLevel::NONE_LEVEL,
            $this->extension->getAccessLevel(
                FieldMaskBuilder::MASK_VIEW_SYSTEM | FieldMaskBuilder::MASK_EDIT_BASIC,
                'CREATE'
            )
        );
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     * @return array
     */
    public function decideIsGrantingProvider()
    {
        $this->org1 = new Organization('org1');
        $this->org2 = new Organization('org2');
        $this->org3 = new Organization('org3');
        $this->org4 = new Organization('org4');
        $this->bu1 = new BusinessUnit('bu1');
        $this->bu2 = new BusinessUnit('bu2');
        $this->bu3 = new BusinessUnit('bu3');
        $this->bu31 = new BusinessUnit('bu31', $this->bu3);
        $this->bu4 = new BusinessUnit('bu4');
        $this->bu41 = new BusinessUnit('bu41', $this->bu4);
        $this->bu411 = new BusinessUnit('bu411', $this->bu41);
        $this->user1 = new User('user1');
        $this->user2 = new User('user2', $this->bu2);
        $this->user3 = new User('user3', $this->bu3);
        $this->user31 = new User('user31', $this->bu31);
        $this->user4 = new User('user4', $this->bu4);
        $this->user411 = new User('user411', $this->bu411);
        return [
            [FieldMaskBuilder::MASK_VIEW_SYSTEM, null, $this->org4, null, true],
            [FieldMaskBuilder::MASK_VIEW_GLOBAL, null, $this->org4, null, true],
            [FieldMaskBuilder::MASK_VIEW_DEEP, null, $this->org4, null, true],
            [FieldMaskBuilder::MASK_VIEW_LOCAL, null, $this->org4, null, true],
            [FieldMaskBuilder::MASK_VIEW_BASIC, null, $this->org4, null, true],
            [FieldMaskBuilder::MASK_VIEW_SYSTEM, null, $this->org4, 'foo', true],
            [FieldMaskBuilder::MASK_VIEW_GLOBAL, null, $this->org4, 'foo', true],
            [FieldMaskBuilder::MASK_VIEW_DEEP, null, $this->org4, 'foo', true],
            [FieldMaskBuilder::MASK_VIEW_LOCAL, null, $this->org4, 'foo', true],
            [FieldMaskBuilder::MASK_VIEW_BASIC, null, $this->org4, 'foo', true],
            [FieldMaskBuilder::MASK_VIEW_SYSTEM, null, $this->org4, new ObjectIdentity('test', 'foo'), true],
            [FieldMaskBuilder::MASK_VIEW_GLOBAL, null, $this->org4, new ObjectIdentity('test', 'foo'), true],
            [FieldMaskBuilder::MASK_VIEW_DEEP, null, $this->org4, new ObjectIdentity('test', 'foo'), true],
            [FieldMaskBuilder::MASK_VIEW_LOCAL, null, $this->org4, new ObjectIdentity('test', 'foo'), true],
            [FieldMaskBuilder::MASK_VIEW_BASIC, null, $this->org4, new ObjectIdentity('test', 'foo'), true],
            [FieldMaskBuilder::MASK_VIEW_SYSTEM, null, $this->org4, new TestEntity(1), true],
            [FieldMaskBuilder::MASK_VIEW_GLOBAL, null, $this->org4, new TestEntity(1), true],
            [FieldMaskBuilder::MASK_VIEW_DEEP, null, $this->org4, new TestEntity(1), true],
            [FieldMaskBuilder::MASK_VIEW_LOCAL, null, $this->org4, new TestEntity(1), true],
            [FieldMaskBuilder::MASK_VIEW_BASIC, null, $this->org4, new TestEntity(1), true],
            [
                FieldMaskBuilder::MASK_VIEW_GLOBAL,
                $this->user3,
                $this->org4,
                new TestEntity(1, $this->org3),
                false
            ],
            [FieldMaskBuilder::MASK_VIEW_GLOBAL, $this->user4, $this->org4, new TestEntity(1, $this->org4), true],
            [
                FieldMaskBuilder::MASK_VIEW_GLOBAL,
                $this->user3,
                $this->org4,
                new TestEntity(1, $this->bu3, $this->org3),
                false
            ],
            [
                FieldMaskBuilder::MASK_VIEW_GLOBAL,
                $this->user4,
                $this->org4,
                new TestEntity(1, $this->bu4, $this->org4),
                true
            ],
            [
                FieldMaskBuilder::MASK_VIEW_GLOBAL,
                $this->user4,
                $this->org4,
                new TestEntity(1, $this->bu411, $this->org4),
                true
            ],
            [
                FieldMaskBuilder::MASK_VIEW_DEEP,
                $this->user3,
                $this->org4,
                new TestEntity(1, $this->bu3, $this->org3),
                false
            ],
            [
                FieldMaskBuilder::MASK_VIEW_DEEP,
                $this->user4,
                $this->org4,
                new TestEntity(1, $this->bu4, $this->org4),
                true
            ],
            [
                FieldMaskBuilder::MASK_VIEW_DEEP,
                $this->user4,
                $this->org4,
                new TestEntity(1, $this->bu411, $this->org4),
                true
            ],
            [
                FieldMaskBuilder::MASK_VIEW_LOCAL,
                $this->user3,
                $this->org4,
                new TestEntity(1, $this->bu3, $this->org3),
                false
            ],
            [
                FieldMaskBuilder::MASK_VIEW_LOCAL,
                $this->user4,
                $this->org4,
                new TestEntity(1, $this->bu4, $this->org4),
                true
            ],
            [
                FieldMaskBuilder::MASK_VIEW_LOCAL,
                $this->user4,
                $this->org4,
                new TestEntity(1, $this->bu411, $this->org4),
                false
            ],
            [
                FieldMaskBuilder::MASK_VIEW_GLOBAL,
                $this->user3,
                $this->org4,
                new TestEntity(1, $this->user3, $this->org3),
                false
            ],
            [
                FieldMaskBuilder::MASK_VIEW_GLOBAL,
                $this->user4,
                $this->org4,
                new TestEntity(1, $this->user4, $this->org4),
                true
            ],
            [
                FieldMaskBuilder::MASK_VIEW_GLOBAL,
                $this->user4,
                $this->org4,
                new TestEntity(1, $this->user411, $this->org4),
                true
            ],
            [
                FieldMaskBuilder::MASK_VIEW_GLOBAL,
                $this->user4,
                $this->org4,
                new TestEntity(1, $this->user3, $this->org3),
                false
            ],
            [
                FieldMaskBuilder::MASK_VIEW_DEEP,
                $this->user3,
                $this->org4,
                new TestEntity(1, $this->user3, $this->org3),
                false
            ],
            [
                FieldMaskBuilder::MASK_VIEW_DEEP,
                $this->user4,
                $this->org4,
                new TestEntity(1, $this->user4, $this->org4),
                true
            ],
            [
                FieldMaskBuilder::MASK_VIEW_DEEP,
                $this->user4,
                $this->org4,
                new TestEntity(1, $this->user411, $this->org4),
                true
            ],
            [
                FieldMaskBuilder::MASK_VIEW_DEEP,
                $this->user4,
                $this->org4,
                new TestEntity(1, $this->user3, $this->org4),
                false
            ],
            [
                FieldMaskBuilder::MASK_VIEW_LOCAL,
                $this->user3,
                $this->org4,
                new TestEntity(1, $this->user3, $this->org3),
                false
            ],
            [
                FieldMaskBuilder::MASK_VIEW_LOCAL,
                $this->user4,
                $this->org4,
                new TestEntity(1, $this->user4, $this->org4),
                true
            ],
            [
                FieldMaskBuilder::MASK_VIEW_LOCAL,
                $this->user4,
                $this->org4,
                new TestEntity(1, $this->user411, $this->org4),
                false
            ],
            [
                FieldMaskBuilder::MASK_VIEW_LOCAL,
                $this->user4,
                $this->org4,
                new TestEntity(1, $this->user3, $this->org3),
                false
            ],
            [
                FieldMaskBuilder::MASK_VIEW_BASIC,
                $this->user3,
                $this->org4,
                new TestEntity(1, $this->user3, $this->org3),
                false
            ],
            [
                FieldMaskBuilder::MASK_VIEW_BASIC,
                $this->user4,
                $this->org4,
                new TestEntity(1, $this->user4, $this->org4),
                true
            ],
            [
                FieldMaskBuilder::MASK_VIEW_BASIC,
                $this->user4,
                $this->org4,
                new TestEntity(1, $this->user411, $this->org4),
                false
            ],
            [
                FieldMaskBuilder::MASK_VIEW_BASIC,
                $this->user4,
                $this->org4,
                new TestEntity(1, $this->user3, $this->org3),
                false
            ],
        ];
    }

    public static function validateMaskForOrganizationProvider()
    {
        return [
            [FieldMaskBuilder::MASK_VIEW_SYSTEM],
            [FieldMaskBuilder::MASK_CREATE_SYSTEM],
            [FieldMaskBuilder::MASK_EDIT_SYSTEM],
        ];
    }

    public static function validateMaskForOrganizationInvalidProvider()
    {
        return [
            [FieldMaskBuilder::MASK_VIEW_GLOBAL],
            [FieldMaskBuilder::MASK_VIEW_DEEP],
            [FieldMaskBuilder::MASK_VIEW_LOCAL],
            [FieldMaskBuilder::MASK_VIEW_BASIC],
        ];
    }

    public static function validateMaskForBusinessUnitProvider()
    {
        return [
            [FieldMaskBuilder::MASK_VIEW_SYSTEM],
            [FieldMaskBuilder::MASK_CREATE_SYSTEM],
            [FieldMaskBuilder::MASK_EDIT_SYSTEM],
            [FieldMaskBuilder::MASK_VIEW_GLOBAL],
            [FieldMaskBuilder::MASK_EDIT_GLOBAL],
            [FieldMaskBuilder::MASK_VIEW_DEEP],
            [FieldMaskBuilder::MASK_EDIT_DEEP],
            [FieldMaskBuilder::MASK_VIEW_LOCAL],
            [FieldMaskBuilder::MASK_EDIT_LOCAL],
            [
                FieldMaskBuilder::MASK_VIEW_SYSTEM
                | FieldMaskBuilder::MASK_CREATE_SYSTEM
                | FieldMaskBuilder::MASK_EDIT_DEEP
            ],
        ];
    }

    public static function validateMaskForBusinessUnitInvalidProvider()
    {
        return [
            [FieldMaskBuilder::MASK_VIEW_BASIC],
            [FieldMaskBuilder::MASK_VIEW_GLOBAL | FieldMaskBuilder::MASK_VIEW_DEEP],
            [FieldMaskBuilder::MASK_VIEW_DEEP | FieldMaskBuilder::MASK_VIEW_LOCAL],
        ];
    }

    public static function validateMaskForUserProvider()
    {
        return [
            [FieldMaskBuilder::MASK_VIEW_SYSTEM],
            [FieldMaskBuilder::MASK_CREATE_SYSTEM],
            [FieldMaskBuilder::MASK_EDIT_SYSTEM],
            [FieldMaskBuilder::MASK_VIEW_GLOBAL],
            [FieldMaskBuilder::MASK_EDIT_GLOBAL],
            [FieldMaskBuilder::MASK_VIEW_DEEP],
            [FieldMaskBuilder::MASK_EDIT_DEEP],
            [FieldMaskBuilder::MASK_VIEW_LOCAL],
            [FieldMaskBuilder::MASK_EDIT_LOCAL],
            [
                FieldMaskBuilder::MASK_VIEW_SYSTEM
                | FieldMaskBuilder::MASK_CREATE_SYSTEM
                | FieldMaskBuilder::MASK_EDIT_DEEP
            ],
        ];
    }

    public static function validateMaskForUserInvalidProvider()
    {
        return [
            [FieldMaskBuilder::MASK_VIEW_BASIC],
            [FieldMaskBuilder::MASK_VIEW_GLOBAL | FieldMaskBuilder::MASK_VIEW_DEEP],
            [FieldMaskBuilder::MASK_VIEW_DEEP | FieldMaskBuilder::MASK_VIEW_LOCAL],
        ];
    }

    public static function validateMaskForUserOwnedProvider()
    {
        return [
            [FieldMaskBuilder::MASK_VIEW_SYSTEM],
            [FieldMaskBuilder::MASK_CREATE_SYSTEM],
            [FieldMaskBuilder::MASK_EDIT_SYSTEM],
            [FieldMaskBuilder::MASK_VIEW_GLOBAL],
            [FieldMaskBuilder::MASK_EDIT_GLOBAL],
            [FieldMaskBuilder::MASK_VIEW_DEEP],
            [FieldMaskBuilder::MASK_EDIT_DEEP],
            [FieldMaskBuilder::MASK_VIEW_LOCAL],
            [FieldMaskBuilder::MASK_EDIT_LOCAL],
            [FieldMaskBuilder::MASK_VIEW_BASIC],
            [FieldMaskBuilder::MASK_EDIT_BASIC],
            [
                FieldMaskBuilder::MASK_VIEW_SYSTEM
                | FieldMaskBuilder::MASK_CREATE_SYSTEM
                | FieldMaskBuilder::MASK_EDIT_DEEP
            ],
        ];
    }

    public static function validateMaskForUserOwnedInvalidProvider()
    {
        return [
            [FieldMaskBuilder::MASK_VIEW_GLOBAL | FieldMaskBuilder::MASK_VIEW_DEEP],
            [FieldMaskBuilder::MASK_VIEW_DEEP | FieldMaskBuilder::MASK_VIEW_LOCAL],
            [FieldMaskBuilder::MASK_VIEW_LOCAL | FieldMaskBuilder::MASK_VIEW_BASIC],
        ];
    }

    public static function validateMaskForOrganizationOwnedProvider()
    {
        return [
            [FieldMaskBuilder::MASK_VIEW_SYSTEM],
            [FieldMaskBuilder::MASK_CREATE_SYSTEM],
            [FieldMaskBuilder::MASK_EDIT_SYSTEM],
            [FieldMaskBuilder::MASK_VIEW_GLOBAL],
            [FieldMaskBuilder::MASK_EDIT_GLOBAL],
            [FieldMaskBuilder::MASK_VIEW_SYSTEM | FieldMaskBuilder::MASK_CREATE_SYSTEM],
        ];
    }

    public static function validateMaskForOrganizationOwnedInvalidProvider()
    {
        return [
            [FieldMaskBuilder::MASK_VIEW_DEEP],
            [FieldMaskBuilder::MASK_VIEW_LOCAL],
            [FieldMaskBuilder::MASK_VIEW_BASIC],
            [FieldMaskBuilder::MASK_VIEW_GLOBAL | FieldMaskBuilder::MASK_VIEW_DEEP],
        ];
    }
}
