<?php

namespace Oro\Bundle\SecurityBundle\Tests\Unit\Acl\Extension;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\EntityBundle\ORM\EntityClassResolver;
use Oro\Bundle\SecurityBundle\Acl\AccessLevel;
use Oro\Bundle\SecurityBundle\Acl\Domain\ObjectIdAccessor;
use Oro\Bundle\SecurityBundle\Acl\Domain\ObjectIdentityFactory;
use Oro\Bundle\SecurityBundle\Acl\Extension\EntityAclExtension;
use Oro\Bundle\SecurityBundle\Acl\Extension\EntityMaskBuilder;
use Oro\Bundle\SecurityBundle\Acl\Extension\FieldAclExtension;
use Oro\Bundle\SecurityBundle\Acl\Group\AclGroupProviderInterface;
use Oro\Bundle\SecurityBundle\Acl\Permission\PermissionManager;
use Oro\Bundle\SecurityBundle\Annotation\Acl as AclAnnotation;
use Oro\Bundle\SecurityBundle\Authentication\Token\UsernamePasswordOrganizationToken;
use Oro\Bundle\SecurityBundle\Authentication\TokenAccessorInterface;
use Oro\Bundle\SecurityBundle\Entity\Permission;
use Oro\Bundle\SecurityBundle\Metadata\EntitySecurityMetadata;
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
use Oro\Component\Testing\Unit\EntityTrait;
use Symfony\Component\Security\Acl\Domain\ObjectIdentity;
use Symfony\Component\Security\Acl\Util\ClassUtils;

/**
 * @SuppressWarnings(PHPMD.ExcessiveClassLength)
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
class EntityAclExtensionTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    /** @var EntityAclExtension */
    private $extension;

    /** @var EntitySecurityMetadataProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $securityMetadataProvider;

    /** @var OwnershipMetadataProviderStub */
    private $metadataProvider;

    /** @var OwnerTree */
    private $tree;

    /** @var EntityOwnershipDecisionMaker */
    private $decisionMaker;

    /** @var \PHPUnit\Framework\MockObject\MockObject|PermissionManager */
    private $permissionManager;

    /** @var \PHPUnit\Framework\MockObject\MockObject|AclGroupProviderInterface */
    private $groupProvider;

    /** @var \PHPUnit\Framework\MockObject\MockObject|DoctrineHelper */
    private $doctrineHelper;

    protected function setUp()
    {
        $this->tree = new OwnerTree();

        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);
        $this->securityMetadataProvider = $this->createMock(EntitySecurityMetadataProvider::class);

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
        $treeProviderMock = $this->createMock(OwnerTreeProvider::class);

        $treeProviderMock->expects($this->any())
            ->method('getTree')
            ->will($this->returnValue($this->tree));

        $entityOwnerAccessor = new EntityOwnerAccessor($this->metadataProvider);
        $this->decisionMaker = new EntityOwnershipDecisionMaker(
            $treeProviderMock,
            new ObjectIdAccessor($this->doctrineHelper),
            $entityOwnerAccessor,
            $this->metadataProvider,
            $this->createMock(TokenAccessorInterface::class)
        );

        $this->permissionManager = $this->getPermissionManagerMock();

        $this->groupProvider = $this->createMock(AclGroupProviderInterface::class);
        $this->groupProvider->expects($this->any())
            ->method('getGroup')
            ->willReturn(AclGroupProviderInterface::DEFAULT_SECURITY_GROUP);

        $this->extension = TestHelper::get($this)->createEntityAclExtension(
            $this->metadataProvider,
            $this->tree,
            new ObjectIdAccessor($this->doctrineHelper),
            $this->decisionMaker,
            $entityOwnerAccessor,
            $this->permissionManager,
            $this->groupProvider
        );
    }

    private function buildTestTree()
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
     *
     * @param int $mask
     */
    public function testValidateMaskForOrganization($mask)
    {
        $this->extension->validateMask($mask, new Organization());
    }

    /**
     * @dataProvider validateMaskForOrganizationInvalidProvider
     * @expectedException \Oro\Bundle\SecurityBundle\Acl\Exception\InvalidAclMaskException
     *
     * @param int $mask
     */
    public function testValidateMaskForOrganizationInvalid($mask)
    {
        $this->extension->validateMask($mask, new Organization());
    }

    /**
     * @dataProvider validateMaskForBusinessUnitProvider
     *
     * @param int $mask
     */
    public function testValidateMaskForBusinessUnit($mask)
    {
        $this->extension->validateMask($mask, new BusinessUnit());
    }

    /**
     * @dataProvider validateMaskForBusinessUnitInvalidProvider
     * @expectedException \Oro\Bundle\SecurityBundle\Acl\Exception\InvalidAclMaskException
     *
     * @param int $mask
     */
    public function testValidateMaskForBusinessUnitInvalid($mask)
    {
        $this->extension->validateMask($mask, new BusinessUnit());
    }

    /**
     * @dataProvider validateMaskForUserProvider
     *
     * @param int $mask
     */
    public function testValidateMaskForUser($mask)
    {
        $this->extension->validateMask($mask, new User());
    }

    /**
     * @dataProvider validateMaskForUserInvalidProvider
     * @expectedException \Oro\Bundle\SecurityBundle\Acl\Exception\InvalidAclMaskException
     *
     * @param int $mask
     */
    public function testValidateMaskForUserInvalid($mask)
    {
        $this->extension->validateMask($mask, new User());
    }

    /**
     * @dataProvider validateMaskForOrganizationOwnedProvider
     *
     * @param int $mask
     */
    public function testValidateMaskForOrganizationOwned($mask)
    {
        $this->metadataProvider->setMetadata(
            TestEntity::class,
            new OwnershipMetadata('ORGANIZATION', 'owner', 'owner_id')
        );
        $this->extension->validateMask($mask, new TestEntity());
    }

    /**
     * @dataProvider validateMaskForOrganizationOwnedInvalidProvider
     * @expectedException \Oro\Bundle\SecurityBundle\Acl\Exception\InvalidAclMaskException
     *
     * @param int $mask
     */
    public function testValidateMaskForOrganizationOwnedInvalid($mask)
    {
        $this->metadataProvider->setMetadata(
            TestEntity::class,
            new OwnershipMetadata('ORGANIZATION', 'owner', 'owner_id')
        );
        $this->extension->validateMask($mask, new TestEntity());
    }

    /**
     * @dataProvider validateMaskForUserOwnedProvider
     *
     * @param int $mask
     */
    public function testValidateMaskForUserOwned($mask)
    {
        $this->metadataProvider->setMetadata(
            TestEntity::class,
            new OwnershipMetadata('USER', 'owner', 'owner_id')
        );
        $this->extension->validateMask($mask, new TestEntity());
    }

    /**
     * @dataProvider validateMaskForUserOwnedInvalidProvider
     * @expectedException \Oro\Bundle\SecurityBundle\Acl\Exception\InvalidAclMaskException
     *
     * @param int $mask
     */
    public function testValidateMaskForUserOwnedInvalid($mask)
    {
        $this->metadataProvider->setMetadata(
            TestEntity::class,
            new OwnershipMetadata('USER', 'owner', 'owner_id')
        );
        $this->extension->validateMask($mask, new TestEntity());
    }

    /**
     * @dataProvider validateMaskForUserOwnedProvider
     *
     * @param int $mask
     */
    public function testValidateMaskForRoot($mask)
    {
        $this->extension->validateMask($mask, new ObjectIdentity('entity', ObjectIdentityFactory::ROOT_IDENTITY_TYPE));
    }

    /**
     * @dataProvider validateMaskForUserOwnedInvalidProvider
     * @expectedException \Oro\Bundle\SecurityBundle\Acl\Exception\InvalidAclMaskException
     *
     * @param int $mask
     */
    public function testValidateMaskForRootInvalid($mask)
    {
        $this->extension->validateMask($mask, new ObjectIdentity('entity', ObjectIdentityFactory::ROOT_IDENTITY_TYPE));
    }

    public function testGetPermissions()
    {
        $this->assertEquals(
            ['VIEW', 'CREATE', 'EDIT', 'DELETE', 'ASSIGN', 'PERMIT'],
            $this->extension->getPermissions()
        );
    }

    public function testGetPermissionsByMask()
    {
        $this->assertEquals(
            ['VIEW', 'CREATE', 'EDIT'],
            $this->extension->getPermissions(1)
        );
    }

    public function testGetPermissionsAreSetInMask()
    {
        $this->assertEquals(
            ['VIEW'],
            $this->extension->getPermissions(1, true)
        );
    }

    /**
     * @param array $inputData
     * @param array $expectedData
     *
     * @dataProvider getAllowedPermissionsProvider
     */
    public function testGetAllowedPermissions(array $inputData, array $expectedData)
    {
        $this->securityMetadataProvider->expects($this->any())
            ->method('getMetadata')
            ->with($inputData['type'])
            ->willReturn(new EntitySecurityMetadata('', '', '', '', $inputData['entityConfig']));

        if ($inputData['owner']) {
            $this->metadataProvider->setMetadata(
                'TestEntity1',
                new OwnershipMetadata('BUSINESS_UNIT', 'owner', 'owner_id')
            );
        }

        $isRootType = $inputData['type'] === ObjectIdentityFactory::ROOT_IDENTITY_TYPE;

        $this->permissionManager = $this->createMock(PermissionManager::class);
        $this->permissionManager->expects($isRootType ? $this->never() : $this->once())
            ->method('getPermissionsForEntity')
            ->with($inputData['type'], AclGroupProviderInterface::DEFAULT_SECURITY_GROUP)
            ->willReturn($inputData['permissions']);
        $this->permissionManager->expects($isRootType ? $this->once() : $this->never())
            ->method('getPermissionsForGroup')
            ->with(AclGroupProviderInterface::DEFAULT_SECURITY_GROUP)
            ->willReturn($inputData['permissions']);
        $this->permissionManager->expects($this->any())
            ->method('getPermissionsMap')
            ->willReturn([
                'VIEW' => 1,
                'CREATE' => 2,
                'EDIT' => 3,
                'DELETE' => 4,
                'ASSIGN' => 5,
                'PERMIT' => 6,
                'UNKNOWN' => 7
            ]);

        /* @var $entityClassResolver EntityClassResolver|\PHPUnit\Framework\MockObject\MockObject  */
        $entityClassResolver = $this->createMock(EntityClassResolver::class);
        $doctrineHelper = $this->createMock(DoctrineHelper::class);
        $fieldAclExtension = $this->createMock(FieldAclExtension::class);

        $extension = new EntityAclExtension(
            new ObjectIdAccessor($doctrineHelper),
            $entityClassResolver,
            $this->securityMetadataProvider,
            $this->metadataProvider,
            new EntityOwnerAccessor($this->metadataProvider),
            $this->decisionMaker,
            $this->permissionManager,
            $this->groupProvider,
            $fieldAclExtension
        );

        $this->assertEquals($expectedData, $extension->getAllowedPermissions(
            new ObjectIdentity('entity', $inputData['type'])
        ));
    }

    public function testDecideIsGrantingForNewObject()
    {
        $object = new TestEntity(null);

        $this->metadataProvider->setMetadata(
            get_class($object),
            new OwnershipMetadata('ORGANIZATION', 'owner', 'owner_id', 'organization')
        );

        /** @var \PHPUnit\Framework\MockObject\MockObject|UsernamePasswordOrganizationToken $token */
        $token =$this->createMock(UsernamePasswordOrganizationToken::class);

        $this->assertTrue($this->extension->decideIsGranting(1, $object, $token));
    }

    /**
     * @dataProvider decideIsGrantingProvider
     *
     * @param int $triggeredMask
     * @param User $user
     * @param Organization $organization
     * @param object $object
     * @param bool $expectedResult
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

        /** @var \PHPUnit\Framework\MockObject\MockObject|UsernamePasswordOrganizationToken $token */
        $token =$this->createMock(UsernamePasswordOrganizationToken::class);
        $token->expects($this->any())
            ->method('getOrganizationContext')
            ->will($this->returnValue($organization));
        $token->expects($this->any())
            ->method('getUser')
            ->will($this->returnValue($user));

        $this->assertEquals($expectedResult, $this->extension->decideIsGranting($triggeredMask, $object, $token));
    }

    /**
     * @dataProvider getMaskBuilderProvider
     *
     * @param string $permission
     * @param int $identity
     * @param array $permissions
     */
    public function testGetMaskBuilder($permission, $identity, array $permissions)
    {
        $this->assertEquals(
            new EntityMaskBuilder($identity, $permissions),
            $this->extension->getMaskBuilder($permission)
        );
    }

    /**
     * @return array
     */
    public function getMaskBuilderProvider()
    {
        return [
            ['permission' => 'VIEW', 'identity' => 0, 'permissions' => ['VIEW', 'CREATE', 'EDIT']],
            ['permission' => 'CREATE', 'identity' => 0, 'permissions' => ['VIEW', 'CREATE', 'EDIT']],
            ['permission' => 'EDIT', 'identity' => 0, 'permissions' => ['VIEW', 'CREATE', 'EDIT']],
            ['permission' => 'DELETE', 'identity' => 32768, 'permissions' => ['DELETE', 'ASSIGN', 'PERMIT']],
            ['permission' => 'ASSIGN', 'identity' => 32768, 'permissions' => ['DELETE', 'ASSIGN', 'PERMIT']],
            ['permission' => 'PERMIT', 'identity' => 32768, 'permissions' => ['DELETE', 'ASSIGN', 'PERMIT']],
        ];
    }

    public function testGetAllMaskBuilders()
    {
        $this->assertEquals(
            [
                new EntityMaskBuilder(0, ['VIEW', 'CREATE', 'EDIT']),
                new EntityMaskBuilder(32768, ['DELETE', 'ASSIGN', 'PERMIT'])
            ],
            $this->extension->getAllMaskBuilders()
        );
    }

    /**
     * @dataProvider adaptRootMaskProvider
     *
     * @param object $object
     * @param string $ownerType
     * @param int $aceMask
     * @param int $expectedMask
     */
    public function testAdaptRootMask($object, $ownerType, $aceMask, $expectedMask)
    {
        if ($ownerType !== null) {
            $this->metadataProvider->setMetadata(
                TestEntity::class,
                new OwnershipMetadata($ownerType, 'owner', 'owner_id')
            );
        }

        $resultMask = $this->extension->adaptRootMask($aceMask, $object);
        $this->assertEquals(
            $expectedMask,
            $resultMask,
            sprintf(
                'Expected "%s" -> "%s"; Actual: "%s"',
                $this->extension->getMaskPattern($aceMask),
                $this->extension->getMaskPattern($expectedMask),
                $this->extension->getMaskPattern($resultMask)
            )
        );

        $this->assertSame(
            $this->extension->getServiceBits($aceMask),
            $this->extension->getServiceBits($resultMask),
            'Service bits should not be changed.'
        );
    }

    /**
     * @dataProvider getAccessLevelProvider
     *
     * @param int $mask
     * @param int $expectedLevel
     * @param string $permission
     */
    public function testGetAccessLevel($mask, $expectedLevel, $permission = null)
    {
        $this->assertEquals($expectedLevel, $this->extension->getAccessLevel($mask, $permission));
    }

    /**
     * @return array
     */
    public function getAccessLevelProvider()
    {
        return [
            [
                'mask' => 0 /* GROUP_NONE */,
                'expectedLevel' => AccessLevel::NONE_LEVEL
            ],
            [
                'mask' => 32768 /* GROUP_NONE */,
                'expectedLevel' => AccessLevel::NONE_LEVEL
            ],
            [
                'mask' => 1 << 4 /* MASK_VIEW_SYSTEM */,
                'expectedLevel' => AccessLevel::SYSTEM_LEVEL
            ],
            [
                'mask' => 1 << 3 /* GLOBAL_LEVEL */,
                'expectedLevel' => AccessLevel::GLOBAL_LEVEL
            ],
            [
                'mask' => 1 << 2 /* DEEP_LEVEL */,
                'expectedLevel' => AccessLevel::DEEP_LEVEL
            ],
            [
                'mask' => 1 << 1 /* LOCAL_LEVEL */,
                'expectedLevel' => AccessLevel::LOCAL_LEVEL
            ],
            [
                'mask' => 1 << 0 /* BASIC_LEVEL */,
                'expectedLevel' => AccessLevel::BASIC_LEVEL
            ],
            [
                'mask' => (1 << 4) /* MASK_VIEW_SYSTEM */ | (1 << 10) /* MASK_EDIT_BASIC */,
                'expectedLevel' => AccessLevel::SYSTEM_LEVEL,
                'permission' => 'VIEW'
            ],
            [
                'mask' => (1 << 4) /* MASK_VIEW_SYSTEM */ | (1 << 10) /* MASK_EDIT_BASIC */,
                'expectedLevel' => AccessLevel::BASIC_LEVEL,
                'permission' => 'EDIT'
            ],
            [
                'mask' => (1 << 4) /* MASK_VIEW_SYSTEM */ | (1 << 10) /* MASK_EDIT_BASIC */,
                'expectedLevel' => AccessLevel::NONE_LEVEL,
                'permission' => 'CREATE'
            ]
        ];
    }

    public function testGetAccessLevelNamesForRoot()
    {
        $object = new ObjectIdentity('entity', ObjectIdentityFactory::ROOT_IDENTITY_TYPE);
        $this->assertEquals(
            [
                0 => 'NONE',
                1 => 'BASIC',
                2 => 'LOCAL',
                3 => 'DEEP',
                5 => 'SYSTEM'
            ],
            $this->extension->getAccessLevelNames($object)
        );
    }

    /**
     * @param OwnershipMetadata $metadata
     * @param array $expected
     *
     * @dataProvider accessLevelProvider
     */
    public function testGetAccessLevelNamesForNonRoot(OwnershipMetadata $metadata, array $expected)
    {
        $object = new ObjectIdentity('entity', '\stdClass');

        $this->metadataProvider->setMetadata('\stdClass', $metadata);

        $this->assertEquals(
            $expected,
            $this->extension->getAccessLevelNames($object)
        );
    }

    /**
     * @return array
     */
    public function accessLevelProvider()
    {
        return [
            'without owner' => [new OwnershipMetadata(), [0 => 'NONE', 5 => 'SYSTEM']],
            'basic level owned' => [
                new OwnershipMetadata('USER', 'user', 'user_id'),
                [
                    0 => 'NONE',
                    1 => 'BASIC',
                    2 => 'LOCAL',
                    3 => 'DEEP',
                    4 => 'GLOBAL',
                ],
            ],
            'local level owned' => [
                new OwnershipMetadata('BUSINESS_UNIT', 'bu', 'bu_id'),
                [
                    0 => 'NONE',
                    2 => 'LOCAL',
                    3 => 'DEEP',
                    4 => 'GLOBAL',
                ],
            ],
            'global level owned' => [
                new OwnershipMetadata('ORGANIZATION', 'org', 'org_id'),
                [
                    0 => 'NONE',
                    4 => 'GLOBAL',
                ],
            ],
        ];
    }

    /**
     * @return array
     */
    public function getAllowedPermissionsProvider()
    {
        return [
            '(root)' => [
                'input' => [
                    'type' => ObjectIdentityFactory::ROOT_IDENTITY_TYPE,
                    'owner' => false,
                    'entityConfig' => [],
                    'permissions' => [
                        $this->getPermission(1, 'VIEW'),
                        $this->getPermission(2, 'CREATE'),
                        $this->getPermission(3, 'EDIT'),
                        $this->getPermission(4, 'DELETE'),
                        $this->getPermission(5, 'ASSIGN'),
                        $this->getPermission(6, 'PERMIT')
                    ],
                ],
                'expected' => ['VIEW', 'CREATE', 'EDIT', 'DELETE', 'ASSIGN', 'PERMIT'],
            ],
            'TestEntity1 + config' => [
                'input' => [
                    'type' => 'TestEntity1',
                    'owner' => false,
                    'entityConfig' => ['VIEW', 'CREATE', 'ASSIGN', 'PERMIT'],
                    'permissions' => [
                        $this->getPermission(1, 'VIEW'),
                        $this->getPermission(2, 'CREATE'),
                        $this->getPermission(3, 'ASSIGN'),
                        $this->getPermission(4, 'PERMIT')
                    ],
                ],
                'expected' => ['VIEW', 'CREATE', 'PERMIT'],
            ],
            'TestEntity1 + config + owner' => [
                'input' => [
                    'type' => 'TestEntity1',
                    'owner' => true,
                    'entityConfig' => ['VIEW', 'CREATE', 'ASSIGN', 'PERMIT'],
                    'permissions' => [
                        $this->getPermission(1, 'VIEW'),
                        $this->getPermission(2, 'CREATE'),
                        $this->getPermission(3, 'ASSIGN'),
                        $this->getPermission(4, 'PERMIT')
                    ],
                ],
                'expected' => ['VIEW', 'CREATE', 'ASSIGN', 'PERMIT'],
            ],
            'TestEntity1 + empty owner' => [
                'input' => [
                    'type' => 'TestEntity1',
                    'owner' => false,
                    'entityConfig' => [],
                    'permissions' => [
                        $this->getPermission(1, 'VIEW'),
                        $this->getPermission(2, 'ASSIGN'),
                        $this->getPermission(3, 'PERMIT'),
                    ],
                ],
                'expected' => ['VIEW', 'PERMIT'],
            ],
        ];
    }

    /**
     * @return array
     */
    public function decideIsGrantingProvider()
    {
        $org3 = new Organization('org3');
        $org4 = new Organization('org4');

        $bu3 = new BusinessUnit('bu3');
        $bu4 = new BusinessUnit('bu4');
        $bu41 = new BusinessUnit('bu41', $bu4);
        $bu411 = new BusinessUnit('bu411', $bu41);

        $user3 = new User('user3', $bu3);
        $user4 = new User('user4', $bu4);
        $user411 = new User('user411', $bu411);

        return [
            [1 << 4 /* MASK_VIEW_SYSTEM */, null, $org4, null, true],
            [1 << 3 /* MASK_VIEW_GLOBAL */, null, $org4, null, true],
            [1 << 2 /* MASK_VIEW_DEEP */, null, $org4, null, true],
            [1 << 1 /* MASK_VIEW_LOCAL */, null, $org4, null, true],
            [1 << 0 /* MASK_VIEW_BASIC */, null, $org4, null, true],
            [1 << 4 /* MASK_VIEW_SYSTEM */, null, $org4, 'foo', true],
            [1 << 3 /* MASK_VIEW_GLOBAL */, null, $org4, 'foo', true],
            [1 << 2 /* MASK_VIEW_DEEP */, null, $org4, 'foo', true],
            [1 << 1 /* MASK_VIEW_LOCAL */, null, $org4, 'foo', true],
            [1 << 0 /* MASK_VIEW_BASIC */, null, $org4, 'foo', true],
            [1 << 4 /* MASK_VIEW_SYSTEM */, null, $org4, new ObjectIdentity('test', 'foo'), true],
            [1 << 3 /* MASK_VIEW_GLOBAL */, null, $org4, new ObjectIdentity('test', 'foo'), true],
            [1 << 2 /* MASK_VIEW_DEEP */, null, $org4, new ObjectIdentity('test', 'foo'), true],
            [1 << 1 /* MASK_VIEW_LOCAL */, null, $org4, new ObjectIdentity('test', 'foo'), true],
            [1 << 0 /* MASK_VIEW_BASIC */, null, $org4, new ObjectIdentity('test', 'foo'), true],
            [1 << 4 /* MASK_VIEW_SYSTEM */, null, $org4, new TestEntity(1), true],
            [1 << 3 /* MASK_VIEW_GLOBAL */, null, $org4, new TestEntity(1), true],
            [1 << 2 /* MASK_VIEW_DEEP */, null, $org4, new TestEntity(1), true],
            [1 << 1 /* MASK_VIEW_LOCAL */, null, $org4, new TestEntity(1), true],
            [1 << 0 /* MASK_VIEW_BASIC */, null, $org4, new TestEntity(1), true],
            [1 << 3 /* MASK_VIEW_GLOBAL */, $user3, $org4, new TestEntity(1, $org3), false],
            [1 << 3 /* MASK_VIEW_GLOBAL */, $user4, $org4, new TestEntity(1, $org4), true],
            [1 << 3 /* MASK_VIEW_GLOBAL */, $user3, $org4, new TestEntity(1, $bu3, $org3), false],
            [1 << 3 /* MASK_VIEW_GLOBAL */, $user4, $org4, new TestEntity(1, $bu4, $org4), true],
            [1 << 3 /* MASK_VIEW_GLOBAL */, $user4, $org4, new TestEntity(1, $bu411, $org4), true],
            [1 << 2 /* MASK_VIEW_DEEP */, $user3, $org4, new TestEntity(1, $bu3, $org3), false],
            [1 << 2 /* MASK_VIEW_DEEP */, $user4, $org4, new TestEntity(1, $bu4, $org4), true],
            [1 << 2 /* MASK_VIEW_DEEP */, $user4, $org4, new TestEntity(1, $bu411, $org4), true],
            [1 << 1 /* MASK_VIEW_LOCAL */, $user3, $org4, new TestEntity(1, $bu3, $org3), false],
            [1 << 1 /* MASK_VIEW_LOCAL */, $user4, $org4, new TestEntity(1, $bu4, $org4), true],
            [1 << 1 /* MASK_VIEW_LOCAL */, $user4, $org4, new TestEntity(1, $bu411, $org4), false],
            [1 << 3 /* MASK_VIEW_GLOBAL */, $user3, $org4, new TestEntity(1, $user3, $org3), false],
            [1 << 3 /* MASK_VIEW_GLOBAL */, $user4, $org4, new TestEntity(1, $user4, $org4), true],
            [1 << 3 /* MASK_VIEW_GLOBAL */, $user4, $org4, new TestEntity(1, $user411, $org4), true],
            [1 << 3 /* MASK_VIEW_GLOBAL */, $user4, $org4, new TestEntity(1, $user3, $org3), false],
            [1 << 2 /* MASK_VIEW_DEEP */, $user3, $org4, new TestEntity(1, $user3, $org3), false],
            [1 << 2 /* MASK_VIEW_DEEP */, $user4, $org4, new TestEntity(1, $user4, $org4), true],
            [1 << 2 /* MASK_VIEW_DEEP */, $user4, $org4, new TestEntity(1, $user411, $org4), true],
            [1 << 2 /* MASK_VIEW_DEEP */, $user4, $org4, new TestEntity(1, $user3, $org4), false],
            [1 << 1 /* MASK_VIEW_LOCAL */, $user3, $org4, new TestEntity(1, $user3, $org3), false],
            [1 << 1 /* MASK_VIEW_LOCAL */, $user4, $org4, new TestEntity(1, $user4, $org4), true],
            [1 << 1 /* MASK_VIEW_LOCAL */, $user4, $org4, new TestEntity(1, $user411, $org4), false],
            [1 << 1 /* MASK_VIEW_LOCAL */, $user4, $org4, new TestEntity(1, $user3, $org3), false],
            [1 << 0 /* MASK_VIEW_BASIC */, $user3, $org4, new TestEntity(1, $user3, $org3), false],
            [1 << 0 /* MASK_VIEW_BASIC */, $user4, $org4, new TestEntity(1, $user4, $org4), true],
            [1 << 0 /* MASK_VIEW_BASIC */, $user4, $org4, new TestEntity(1, $user411, $org4), false],
            [1 << 0 /* MASK_VIEW_BASIC */, $user4, $org4, new TestEntity(1, $user3, $org3), false]
        ];
    }

    /**
     * @return array
     */
    public static function adaptRootMaskProvider()
    {
        return [
            [
                new TestEntity(),
                null,
                (1 << 4) /* MASK_VIEW_SYSTEM */ | (1 << 9) /* MASK_CREATE_SYSTEM */,
                (1 << 4) /* MASK_VIEW_SYSTEM */ | (1 << 9) /* MASK_CREATE_SYSTEM */
            ],
            [
                new TestEntity(),
                null,
                (1 << 0) /* MASK_VIEW_BASIC */ | (1 << 6) /* MASK_CREATE_LOCAL */,
                (1 << 4) /* MASK_VIEW_SYSTEM */ | (1 << 9) /* MASK_CREATE_SYSTEM */
            ],
            [
                new TestEntity(),
                null,
                ((1 << 9) | 32768) /* MASK_ASSIGN_SYSTEM */,
                32768 /* GROUP_NONE */
            ],
            [
                new Organization(),
                null,
                (1 << 0) /* MASK_VIEW_BASIC */ | (1 << 6) /* MASK_CREATE_LOCAL */,
                (1 << 4) /* MASK_VIEW_SYSTEM */ | (1 << 9) /* MASK_CREATE_SYSTEM */
            ],
            [
                new BusinessUnit(),
                null,
                (1 << 0) /* MASK_VIEW_BASIC */ | (1 << 6) /* MASK_CREATE_LOCAL */,
                (1 << 1) /* MASK_VIEW_LOCAL */ | (1 << 6) /* MASK_CREATE_LOCAL */
            ],
            [
                new BusinessUnit(),
                null,
                (1 << 2) /* MASK_VIEW_DEEP */ | (1 << 6) /* MASK_CREATE_LOCAL */,
                (1 << 2) /* MASK_VIEW_DEEP */ | (1 << 6) /* MASK_CREATE_LOCAL */
            ],
            [
                new User(),
                null,
                (1 << 0) /* MASK_VIEW_BASIC */ | (1 << 6) /* MASK_CREATE_LOCAL */,
                (1 << 1) /* MASK_VIEW_LOCAL */ | (1 << 6) /* MASK_CREATE_LOCAL */
            ],
            [
                new TestEntity(),
                'ORGANIZATION',
                (1 << 4) /* MASK_VIEW_SYSTEM */ | (1 << 7) /* MASK_CREATE_DEEP */,
                (1 << 4) /* MASK_VIEW_SYSTEM */ | (1 << 8) /* MASK_CREATE_GLOBAL */
            ],
            [
                new TestEntity(),
                'BUSINESS_UNIT',
                (1 << 2) /* MASK_VIEW_DEEP */ | (1 << 5) /* MASK_CREATE_BASIC */,
                (1 << 2) /* MASK_VIEW_DEEP */ | (1 << 6) /* MASK_CREATE_LOCAL */
            ],
            [
                new TestEntity(),
                'USER',
                (1 << 3) /* MASK_VIEW_GLOBAL */ | (1 << 5) /* MASK_CREATE_BASIC */,
                (1 << 3) /* MASK_VIEW_GLOBAL */ | (1 << 5) /* MASK_CREATE_BASIC */
            ]
        ];
    }

    /**
     * @return array
     */
    public static function validateMaskForOrganizationProvider()
    {
        return [
            [1 << 4 /* MASK_VIEW_SYSTEM */],
            [1 << 9 /* MASK_CREATE_SYSTEM */],
            [1 << 14 /* MASK_EDIT_SYSTEM */],
            [(1 << 4) + 32768 /* MASK_DELETE_SYSTEM */],
            [(1 << 4) /* MASK_VIEW_SYSTEM */ | ((1 << 4) + 32768) /* MASK_DELETE_SYSTEM */],
        ];
    }

    /**
     * @return array
     */
    public static function validateMaskForOrganizationInvalidProvider()
    {
        return [
            [(1 << 9) + 32768 /*MASK_ASSIGN_SYSTEM*/],
            [1 << 3 /*MASK_VIEW_GLOBAL*/],
            [1 << 2 /*MASK_VIEW_DEEP*/],
            [1 << 1 /*MASK_VIEW_LOCAL*/],
            [1 << 0 /*MASK_VIEW_BASIC*/]
        ];
    }

    /**
     * @return array
     */
    public static function validateMaskForBusinessUnitProvider()
    {
        return [
            [1 << 4 /* MASK_VIEW_SYSTEM */],
            [1 << 9 /* MASK_CREATE_SYSTEM */],
            [1 << 14 /* MASK_EDIT_SYSTEM */],
            [(1 << 4) + 32768 /* MASK_DELETE_SYSTEM */],
            [(1 << 9) + 32768 /* MASK_ASSIGN_SYSTEM */],
            [(1 << 14) + 32768 /* MASK_PERMIT_SYSTEM */],
            [1 << 3 /* MASK_VIEW_GLOBAL */],
            [1 << 8 /* MASK_CREATE_GLOBAL */],
            [1 << 13 /* MASK_EDIT_GLOBAL */],
            [(1 << 3) + 32768 /* MASK_DELETE_GLOBAL */],
            [(1 << 8) + 32768 /* MASK_ASSIGN_GLOBAL */],
            [(1 << 13) + 32768 /* MASK_PERMIT_GLOBAL */],
            [1 << 2 /* MASK_VIEW_DEEP */],
            [1 << 7 /* MASK_CREATE_DEEP */],
            [1 << 12 /* MASK_EDIT_DEEP */],
            [(1 << 2) + 32768 /* MASK_DELETE_DEEP */],
            [(1 << 7) + 32768 /* MASK_ASSIGN_DEEP */],
            [(1 << 12) + 32768 /* MASK_PERMIT_DEEP */],
            [1 << 1 /* MASK_VIEW_LOCAL */],
            [1 << 6 /* MASK_CREATE_LOCAL */],
            [1 << 11 /* MASK_EDIT_LOCAL */],
            [(1 << 1) + 32768 /* MASK_DELETE_LOCAL */],
            [(1 << 6) + 32768 /* MASK_ASSIGN_LOCAL */],
            [(1 << 11) + 32768 /* MASK_PERMIT_LOCAL */],
            [(1 << 4) /* MASK_VIEW_SYSTEM */ | (1 << 8) /* MASK_CREATE_GLOBAL */ | (1 << 12) /* MASK_EDIT_DEEP */],
            [(1 << 3) /* MASK_VIEW_GLOBAL */ | (1 << 7) /* MASK_CREATE_DEEP */ | (1 << 11) /* MASK_EDIT_LOCAL */]
        ];
    }

    /**
     * @return array
     */
    public static function validateMaskForBusinessUnitInvalidProvider()
    {
        return [
            [1 << 0 /* MASK_VIEW_BASIC */],
            [(1 << 4) /* MASK_VIEW_SYSTEM */ | (1 << 3) /* MASK_VIEW_GLOBAL */],
            [(1 << 3) /* MASK_VIEW_GLOBAL */ | (1 << 2) /* MASK_VIEW_DEEP */],
            [(1 << 2) /* MASK_VIEW_DEEP */ | (1 << 1) /* MASK_VIEW_LOCAL */]
        ];
    }

    /**
     * @return array
     */
    public static function validateMaskForUserProvider()
    {
        return [
            [1 << 4 /* MASK_VIEW_SYSTEM */],
            [1 << 9 /* MASK_CREATE_SYSTEM */],
            [1 << 14 /* MASK_EDIT_SYSTEM */],
            [(1 << 4) + 32768 /* MASK_DELETE_SYSTEM */],
            [(1 << 9) + 32768 /* MASK_ASSIGN_SYSTEM */],
            [(1 << 14) + 32768 /* MASK_PERMIT_SYSTEM */],
            [1 << 3 /* MASK_VIEW_GLOBAL */],
            [1 << 8 /* MASK_CREATE_GLOBAL */],
            [1 << 13 /* MASK_EDIT_GLOBAL */],
            [(1 << 4) + 32768 /* MASK_DELETE_GLOBAL */],
            [(1 << 9) + 32768 /* MASK_ASSIGN_GLOBAL */],
            [(1 << 14) + 32768 /* MASK_PERMIT_GLOBAL */],
            [1 << 2 /* MASK_VIEW_DEEP */],
            [1 << 7 /* MASK_CREATE_DEEP */],
            [1 << 12 /* MASK_EDIT_DEEP */],
            [(1 << 4) + 32768 /* MASK_DELETE_DEEP */],
            [(1 << 9) + 32768 /* MASK_ASSIGN_DEEP */],
            [(1 << 14) + 32768 /* MASK_PERMIT_DEEP */],
            [1 << 1 /* MASK_VIEW_LOCAL */],
            [1 << 6 /* MASK_CREATE_LOCAL */],
            [1 << 11 /* MASK_EDIT_LOCAL */],
            [(1 << 4) + 32768 /* MASK_DELETE_LOCAL */],
            [(1 << 9) + 32768 /* MASK_ASSIGN_LOCAL */],
            [(1 << 14) + 32768 /* MASK_PERMIT_LOCAL */],
            [(1 << 4) /* MASK_VIEW_SYSTEM */ | (1 << 8) /* MASK_CREATE_GLOBAL */ | (1 << 12) /* MASK_EDIT_DEEP */],
            [(1 << 3) /* MASK_VIEW_GLOBAL */ | (1 << 7) /* MASK_CREATE_DEEP */ | (1 << 11) /* MASK_EDIT_LOCAL */]
        ];
    }

    /**
     * @return array
     */
    public static function validateMaskForUserInvalidProvider()
    {
        return [
            [1 << 0 /* MASK_VIEW_BASIC */],
            [(1 << 4) /* MASK_VIEW_SYSTEM */ | (1 << 3) /* MASK_VIEW_GLOBAL */],
            [(1 << 3) /* MASK_VIEW_GLOBAL */ | (1 << 2) /* MASK_VIEW_DEEP */],
            [(1 << 2) /* MASK_VIEW_DEEP */ | (1 << 1) /* MASK_VIEW_LOCAL */]
        ];
    }

    /**
     * @return array
     */
    public static function validateMaskForUserOwnedProvider()
    {
        return [
            [1 << 4 /* MASK_VIEW_SYSTEM */],
            [1 << 9 /* MASK_CREATE_SYSTEM */],
            [1 << 14 /* MASK_EDIT_SYSTEM */],
            [(1 << 4) + 32768 /* MASK_DELETE_SYSTEM */],
            [(1 << 9) + 32768 /* MASK_ASSIGN_SYSTEM */],
            [(1 << 14) + 32768 /* MASK_PERMIT_SYSTEM */],
            [1 << 3 /* MASK_VIEW_GLOBAL */],
            [1 << 8 /* MASK_CREATE_GLOBAL */],
            [1 << 13 /* MASK_EDIT_GLOBAL */],
            [(1 << 4) + 32768 /* MASK_DELETE_GLOBAL */],
            [(1 << 9) + 32768 /* MASK_ASSIGN_GLOBAL */],
            [(1 << 14) + 32768 /* MASK_PERMIT_GLOBAL */],
            [1 << 2 /* MASK_VIEW_DEEP */],
            [1 << 7 /* MASK_CREATE_DEEP */],
            [1 << 12 /* MASK_EDIT_DEEP */],
            [(1 << 4) + 32768 /* MASK_DELETE_DEEP */],
            [(1 << 9) + 32768 /* MASK_ASSIGN_DEEP */],
            [(1 << 14) + 32768 /* MASK_PERMIT_DEEP */],
            [1 << 1 /* MASK_VIEW_LOCAL */],
            [1 << 6 /* MASK_CREATE_LOCAL */],
            [1 << 11 /* MASK_EDIT_LOCAL */],
            [(1 << 4) + 32768 /* MASK_DELETE_LOCAL */],
            [(1 << 9) + 32768 /* MASK_ASSIGN_LOCAL */],
            [(1 << 14) + 32768 /* MASK_PERMIT_LOCAL */],
            [1 << 0 /* MASK_VIEW_BASIC */],
            [1 << 5 /* MASK_CREATE_BASIC */],
            [1 << 10 /* MASK_EDIT_BASIC */],
            [(1 << 0) + 32768 /* MASK_DELETE_BASIC */],
            [(1 << 5) + 32768 /* MASK_ASSIGN_BASIC */],
            [(1 << 10) + 32768 /* MASK_PERMIT_BASIC */],
            [(1 << 4) /* MASK_VIEW_SYSTEM */ | (1 << 8) /* MASK_CREATE_GLOBAL */ | (1 << 12) /* MASK_EDIT_DEEP */],
            [(1 << 3) /* MASK_VIEW_GLOBAL */ | (1 << 7) /* MASK_CREATE_DEEP */ | (1 << 11) /* MASK_EDIT_LOCAL */],
            [(1 << 2) /* MASK_VIEW_DEEP */ | (1 << 6) /* MASK_CREATE_LOCAL */ | (1 << 10) /* MASK_EDIT_BASIC */]
        ];
    }

    /**
     * @return array
     */
    public static function validateMaskForUserOwnedInvalidProvider()
    {
        return [
            [(1 << 4) /* MASK_VIEW_SYSTEM */ | (1 << 3) /* MASK_VIEW_GLOBAL */],
            [(1 << 3) /* MASK_VIEW_GLOBAL */ | (1 << 2) /* MASK_VIEW_DEEP */],
            [(1 << 2) /* MASK_VIEW_DEEP */ | (1 << 1) /* MASK_VIEW_LOCAL */],
            [(1 << 1) /* MASK_VIEW_LOCAL */ | (1 << 0) /* MASK_VIEW_BASIC */]
        ];
    }

    /**
     * @return array
     */
    public static function validateMaskForOrganizationOwnedProvider()
    {
        return [
            [1 << 4 /* MASK_VIEW_SYSTEM */],
            [1 << 9 /* MASK_CREATE_SYSTEM */],
            [1 << 14 /* MASK_EDIT_SYSTEM */],
            [(1 << 4) + 32768 /* MASK_DELETE_SYSTEM */],
            [(1 << 9) + 32768 /* MASK_ASSIGN_SYSTEM */],
            [(1 << 14) + 32768 /* MASK_PERMIT_SYSTEM */],
            [1 << 3 /* MASK_VIEW_GLOBAL */],
            [1 << 8 /* MASK_CREATE_GLOBAL */],
            [1 << 13 /* MASK_EDIT_GLOBAL */],
            [(1 << 4) + 32768 /* MASK_DELETE_GLOBAL */],
            [(1 << 9) + 32768 /* MASK_ASSIGN_GLOBAL */],
            [(1 << 14) + 32768 /* MASK_PERMIT_GLOBAL */],
            [(1 << 4) /* MASK_VIEW_SYSTEM */ | (1 << 8) /* MASK_CREATE_GLOBAL */]
        ];
    }

    /**
     * @return array
     */
    public static function validateMaskForOrganizationOwnedInvalidProvider()
    {
        return [
            [1 << 2 /* MASK_VIEW_DEEP */],
            [1 << 1 /* MASK_VIEW_LOCAL */],
            [1 << 0 /* MASK_VIEW_BASIC */],
            [(1 << 4) /* MASK_VIEW_SYSTEM */ | (1 << 3) /* MASK_VIEW_GLOBAL */],
            [(1 << 3) /* MASK_VIEW_GLOBAL */ | (1 << 2) /* MASK_VIEW_DEEP */]
        ];
    }

    /**
     * @dataProvider supportsDataProvider
     *
     * @param mixed $id
     * @param string $type
     * @param string $class
     * @param bool $isEntity
     * @param bool $expected
     */
    public function testSupports($id, $type, $class, $isEntity, $isProtectedEntity, $expected)
    {
        /** @var \PHPUnit\Framework\MockObject\MockObject|EntityClassResolver $entityClassResolverMock */
        $entityClassResolverMock = $this->createMock(EntityClassResolver::class);
        $entityClassResolverMock->expects($isEntity ? $this->once() : $this->never())
            ->method('getEntityClass')
            ->with($class)
            ->willReturn($class);

        /** @var \PHPUnit\Framework\MockObject\MockObject|EntitySecurityMetadataProvider $entityMetadataProvider */
        $entityMetadataProvider = $this->createMock(EntitySecurityMetadataProvider::class);
        $entityMetadataProvider->expects($this->once())
            ->method('isProtectedEntity')
            ->with($class)
            ->willReturn($isProtectedEntity);
        $fieldAclExtension = $this->createMock(FieldAclExtension::class);

        $extension = new EntityAclExtension(
            new ObjectIdAccessor($this->doctrineHelper),
            $entityClassResolverMock,
            $entityMetadataProvider,
            $this->metadataProvider,
            new EntityOwnerAccessor($this->metadataProvider),
            $this->decisionMaker,
            $this->permissionManager,
            $this->groupProvider,
            $fieldAclExtension
        );

        $this->assertEquals($expected, $extension->supports($type, $id));
    }

    /**
     * @return array
     */
    public function supportsDataProvider()
    {
        return [
            [
                'id' => 'action',
                'type' => '\stdClass',
                'class' => '\stdClass',
                'isEntity' => false,
                'isProtectedEntity' => false,
                'expected' => false
            ],
            [
                'id' => 'entity',
                'type' => '\stdClass',
                'class' => '\stdClass',
                'isEntity' => true,
                'isProtectedEntity' => true,
                'expected' => true
            ],
            [
                'id' => 'entity',
                'type' => '@\stdClass',
                'class' => '\stdClass',
                'isEntity' => true,
                'isProtectedEntity' => true,
                'expected' => true
            ],
            [
                'id' => 'entity',
                'type' => 'group@\stdClass',
                'class' => '\stdClass',
                'isEntity' => true,
                'isProtectedEntity' => true,
                'expected' => true
            ],
            [
                'id' => 'entity',
                'type' => '@\stdClass',
                'class' => '\stdClass',
                'isEntity' => true,
                'isProtectedEntity' => false,
                'expected' => false
            ],
        ];
    }

    /**
     * @dataProvider getObjectIdentityDataProvider
     *
     * @param mixed $val
     * @param ObjectIdentity $expected
     */
    public function testGetObjectIdentity($val, $expected)
    {
        $this->assertEquals($expected, $this->extension->getObjectIdentity($val));
    }

    /**
     * @return array
     */
    public function getObjectIdentityDataProvider()
    {
        $annotation = new AclAnnotation([
            'id' => 'test_id',
            'type' => 'entity',
            'permission' => 'VIEW',
            'class' => '\stdClass'
        ]);

        $annotation2 = new AclAnnotation([
            'id' => 'test_id',
            'type' => 'entity',
            'permission' => 'VIEW',
            'class' => '\stdClass',
            'group_name' => 'group'
        ]);

        $domainObject = new Stub\DomainObjectStub();

        return [
            [
                'val' => 'entity:\stdClass',
                'expected' => new ObjectIdentity('entity', '\stdClass')
            ],
            [
                'val' => 'entity:group@\stdClass',
                'expected' => new ObjectIdentity('entity', 'group@\stdClass')
            ],
            [
                'val' => 'entity:@\stdClass',
                'expected' => new ObjectIdentity('entity', '\stdClass')
            ],
            [
                'val' => $annotation,
                'expected' => new ObjectIdentity('entity', '\stdClass')
            ],
            [
                'val' => $annotation2,
                'expected' => new ObjectIdentity('entity', 'group@\stdClass')
            ],
            [
                'val' => $domainObject,
                'expected' => new ObjectIdentity(
                    Stub\DomainObjectStub::IDENTIFIER,
                    ClassUtils::getRealClass($domainObject)
                ),
            ]
        ];
    }

    /**
     * @param string $id
     * @param string $name
     * @return Permission
     */
    private function getPermission($id, $name)
    {
        return $this->getEntity(Permission::class, ['id' => $id, 'name' => $name]);
    }

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject|PermissionManager
     */
    private function getPermissionManagerMock()
    {
        $mock = $this->createMock(PermissionManager::class);
        $mock->expects($this->any())
            ->method('getPermissionsMap')
            ->willReturn([
                'VIEW'   => 1,
                'CREATE' => 2,
                'EDIT'   => 3,
                'DELETE' => 4,
                'ASSIGN' => 5,
                'PERMIT' => 6
            ]);

        return $mock;
    }
}
