<?php

namespace Oro\Bundle\SecurityBundle\Tests\Unit\Acl\Extension;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Security\Core\Util\ClassUtils;
use Symfony\Component\Security\Acl\Domain\ObjectIdentity;

use Oro\Bundle\EntityBundle\ORM\EntityClassResolver;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\SecurityBundle\Acl\AccessLevel;
use Oro\Bundle\SecurityBundle\Acl\Domain\ObjectIdAccessor;
use Oro\Bundle\SecurityBundle\Acl\Domain\ObjectIdentityFactory;
use Oro\Bundle\SecurityBundle\Acl\Extension\EntityAclExtension;
use Oro\Bundle\SecurityBundle\Acl\Extension\EntityMaskBuilder;
use Oro\Bundle\SecurityBundle\Acl\Group\AclGroupProviderInterface;
use Oro\Bundle\SecurityBundle\Acl\Permission\PermissionManager;
use Oro\Bundle\SecurityBundle\Annotation\Acl as AclAnnotation;
use Oro\Bundle\SecurityBundle\Authentication\Token\UsernamePasswordOrganizationToken;
use Oro\Bundle\SecurityBundle\Entity\Permission;
use Oro\Bundle\SecurityBundle\Metadata\EntitySecurityMetadata;
use Oro\Bundle\SecurityBundle\Metadata\EntitySecurityMetadataProvider;
use Oro\Bundle\SecurityBundle\Owner\EntityOwnerAccessor;
use Oro\Bundle\SecurityBundle\Owner\EntityOwnershipDecisionMaker;
use Oro\Bundle\SecurityBundle\Owner\Metadata\OwnershipMetadata;
use Oro\Bundle\SecurityBundle\Owner\OwnerTreeProvider;
use Oro\Bundle\SecurityBundle\Tests\Unit\Acl\Domain\Fixtures\Entity\BusinessUnit;
use Oro\Bundle\SecurityBundle\Tests\Unit\Acl\Domain\Fixtures\Entity\Organization;
use Oro\Bundle\SecurityBundle\Tests\Unit\Acl\Domain\Fixtures\Entity\TestEntity;
use Oro\Bundle\SecurityBundle\Tests\Unit\Acl\Domain\Fixtures\Entity\User;
use Oro\Bundle\SecurityBundle\Tests\Unit\TestHelper;
use Oro\Bundle\SecurityBundle\Tests\Unit\Stub\OwnershipMetadataProviderStub;
use Oro\Bundle\SecurityBundle\Owner\OwnerTree;

/**
 * @SuppressWarnings(PHPMD.ExcessiveClassLength)
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
class EntityAclExtensionTest extends \PHPUnit_Framework_TestCase
{
    /** @var EntityAclExtension */
    private $extension;

    /** @var EntitySecurityMetadataProvider|\PHPUnit_Framework_MockObject_MockObject */
    private $securityMetadataProvider;

    /** @var OwnershipMetadataProviderStub */
    private $metadataProvider;

    /** @var OwnerTree */
    private $tree;

    /** @var EntityOwnershipDecisionMaker */
    private $decisionMaker;

    /** @var \PHPUnit_Framework_MockObject_MockObject|PermissionManager */
    private $permissionManager;

    /** @var \PHPUnit_Framework_MockObject_MockObject|AclGroupProviderInterface */
    private $groupProvider;

    /** @var \PHPUnit_Framework_MockObject_MockObject|DoctrineHelper */
    private $doctrineHelper;

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
            $this->metadataProvider->getGlobalLevelClass(),
            new OwnershipMetadata()
        );
        $this->metadataProvider->setMetadata(
            $this->metadataProvider->getLocalLevelClass(),
            new OwnershipMetadata('BUSINESS_UNIT', 'owner', 'owner_id')
        );
        $this->metadataProvider->setMetadata(
            $this->metadataProvider->getBasicLevelClass(),
            new OwnershipMetadata('BUSINESS_UNIT', 'owner', 'owner_id')
        );

        /** @var \PHPUnit_Framework_MockObject_MockObject|OwnerTreeProvider $treeProviderMock */
        $treeProviderMock = $this->getMockBuilder('Oro\Bundle\SecurityBundle\Owner\OwnerTreeProvider')
            ->disableOriginalConstructor()
            ->getMock();

        $treeProviderMock->expects($this->any())
            ->method('getTree')
            ->will($this->returnValue($this->tree));

        /** @var \PHPUnit_Framework_MockObject_MockObject|ContainerInterface $container */
        $container = $this->getMock('Symfony\Component\DependencyInjection\ContainerInterface');
        $container->expects($this->any())
            ->method('get')
            ->will(
                $this->returnValueMap(
                    [
                        [
                            'oro_security.ownership_tree_provider.chain',
                            ContainerInterface::EXCEPTION_ON_INVALID_REFERENCE,
                            $treeProviderMock,
                        ],
                        [
                            'oro_security.owner.metadata_provider.chain',
                            ContainerInterface::EXCEPTION_ON_INVALID_REFERENCE,
                            $this->metadataProvider,
                        ],
                        [
                            'oro_security.acl.object_id_accessor',
                            ContainerInterface::EXCEPTION_ON_INVALID_REFERENCE,
                            new ObjectIdAccessor($this->doctrineHelper),
                        ],
                        [
                            'oro_security.owner.entity_owner_accessor',
                            ContainerInterface::EXCEPTION_ON_INVALID_REFERENCE,
                            new EntityOwnerAccessor($this->metadataProvider),
                        ],
                    ]
                )
            );
        $entityOwnerAccessor = new EntityOwnerAccessor($this->metadataProvider);
        $this->decisionMaker = new EntityOwnershipDecisionMaker(
            $treeProviderMock,
            new ObjectIdAccessor($this->doctrineHelper),
            $entityOwnerAccessor, //new EntityOwnerAccessor($this->metadataProvider),
            $this->metadataProvider
        );
        $this->decisionMaker->setContainer($container);

        $this->permissionManager = $this->getPermissionManagerMock();

        $this->groupProvider = $this->getMock('Oro\Bundle\SecurityBundle\Acl\Group\AclGroupProviderInterface');
        $this->groupProvider->expects($this->any())
            ->method('getGroup')
            ->willReturn(AclGroupProviderInterface::DEFAULT_SECURITY_GROUP);

        $this->extension = TestHelper::get($this)->createEntityAclExtension(
            $this->metadataProvider,
            $this->tree,
            new ObjectIdAccessor($this->doctrineHelper),
            $this->decisionMaker,
            $this->permissionManager,
            $this->groupProvider
        );

        $this->extension->setEntityOwnerAccessor($entityOwnerAccessor);
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
        $this->tree->addLocalEntity('bu1', null);
        $this->tree->addLocalEntity('bu2', null);
        $this->tree->addLocalEntity('bu3', 'org3');
        $this->tree->addLocalEntity('bu31', 'org3');
        $this->tree->addLocalEntity('bu3a', 'org3');
        $this->tree->addLocalEntity('bu3a1', 'org3');
        $this->tree->addLocalEntity('bu4', 'org4');
        $this->tree->addLocalEntity('bu41', 'org4');
        $this->tree->addLocalEntity('bu411', 'org4');

        $this->tree->addDeepEntity('bu1', null);
        $this->tree->addDeepEntity('bu2', null);
        $this->tree->addDeepEntity('bu3', null);
        $this->tree->addDeepEntity('bu31', 'bu3');
        $this->tree->addDeepEntity('bu3a', null);
        $this->tree->addDeepEntity('bu3a1', 'bu3a');
        $this->tree->addDeepEntity('bu4', null);
        $this->tree->addDeepEntity('bu41', 'bu4');
        $this->tree->addDeepEntity('bu411', 'bu41');

        $this->tree->addBasicEntity('user1', null);
        $this->tree->addBasicEntity('user2', 'bu2');
        $this->tree->addBasicEntity('user3', 'bu3');
        $this->tree->addBasicEntity('user31', 'bu31');
        $this->tree->addBasicEntity('user4', 'bu4');
        $this->tree->addBasicEntity('user41', 'bu41');
        $this->tree->addBasicEntity('user411', 'bu411');

        $this->tree->addGlobalEntity('user1', 'org1');
        $this->tree->addGlobalEntity('user1', 'org2');
        $this->tree->addGlobalEntity('user2', 'org2');
        $this->tree->addGlobalEntity('user3', 'org2');
        $this->tree->addGlobalEntity('user3', 'org3');
        $this->tree->addGlobalEntity('user31', 'org3');
        $this->tree->addGlobalEntity('user4', 'org4');
        $this->tree->addGlobalEntity('user411', 'org4');

        $this->tree->addLocalEntityToBasic('user1', 'bu1', 'org1');
        $this->tree->addLocalEntityToBasic('user1', 'bu2', 'org2');
        $this->tree->addLocalEntityToBasic('user2', 'bu2', 'org2');
        $this->tree->addLocalEntityToBasic('user3', 'bu3', 'org3');
        $this->tree->addLocalEntityToBasic('user3', 'bu2', 'org2');
        $this->tree->addLocalEntityToBasic('user31', 'bu31', 'org3');
        $this->tree->addLocalEntityToBasic('user4', 'bu4', 'org4');
        $this->tree->addLocalEntityToBasic('user411', 'bu411', 'org4');

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
            'Oro\Bundle\SecurityBundle\Tests\Unit\Acl\Domain\Fixtures\Entity\TestEntity',
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
            'Oro\Bundle\SecurityBundle\Tests\Unit\Acl\Domain\Fixtures\Entity\TestEntity',
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
            'Oro\Bundle\SecurityBundle\Tests\Unit\Acl\Domain\Fixtures\Entity\TestEntity',
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
            'Oro\Bundle\SecurityBundle\Tests\Unit\Acl\Domain\Fixtures\Entity\TestEntity',
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
            ['VIEW', 'CREATE', 'EDIT', 'DELETE', 'ASSIGN', 'SHARE'],
            $this->extension->getPermissions()
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

        $this->permissionManager = $this->getMockBuilder('Oro\Bundle\SecurityBundle\Acl\Permission\PermissionManager')
            ->disableOriginalConstructor()
            ->getMock();
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
                'VIEW'   => 1,
                'CREATE' => 2,
                'EDIT'   => 3,
                'DELETE' => 4,
                'ASSIGN' => 5,
                'SHARE'  => 6,
                'PERMISSION' => 7,
                'UNKNOWN' => 8,
            ]);

        /* @var $entityClassResolver EntityClassResolver|\PHPUnit_Framework_MockObject_MockObject  */
        $entityClassResolver = $this->getMockBuilder('Oro\Bundle\EntityBundle\ORM\EntityClassResolver')
            ->disableOriginalConstructor()
            ->getMock();

        $doctrineHelper = $this->getMockBuilder('Oro\Bundle\EntityBundle\ORM\DoctrineHelper')
            ->disableOriginalConstructor()
            ->getMock();

        $extension = new EntityAclExtension(
            new ObjectIdAccessor($doctrineHelper),
            $entityClassResolver,
            $this->securityMetadataProvider,
            $this->metadataProvider,
            $this->decisionMaker,
            $this->permissionManager,
            $this->groupProvider
        );

        $this->assertEquals($expectedData, $extension->getAllowedPermissions(
            new ObjectIdentity('entity', $inputData['type'])
        ));
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
            if (is_a($owner, $this->metadataProvider->getGlobalLevelClass())) {
                $this->metadataProvider->setMetadata(
                    get_class($object),
                    new OwnershipMetadata('ORGANIZATION', 'owner', 'owner_id', 'organization')
                );
            } elseif (is_a($owner, $this->metadataProvider->getLocalLevelClass())) {
                $this->metadataProvider->setMetadata(
                    get_class($object),
                    new OwnershipMetadata('BUSINESS_UNIT', 'owner', 'owner_id', 'organization')
                );
            } elseif (is_a($owner, $this->metadataProvider->getBasicLevelClass())) {
                $this->metadataProvider->setMetadata(
                    get_class($object),
                    new OwnershipMetadata('USER', 'owner', 'owner_id', 'organization')
                );
            }
        }

        /** @var \PHPUnit_Framework_MockObject_MockObject|UsernamePasswordOrganizationToken $token */
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
            ['permission' => 'DELETE', 'identity' => 32768, 'permissions' => ['DELETE', 'ASSIGN', 'SHARE']],
            ['permission' => 'ASSIGN', 'identity' => 32768, 'permissions' => ['DELETE', 'ASSIGN', 'SHARE']],
            ['permission' => 'SHARE', 'identity' => 32768, 'permissions' => ['DELETE', 'ASSIGN', 'SHARE']],
        ];
    }

    public function testGetAllMaskBuilders()
    {
        $this->assertEquals(
            [
                new EntityMaskBuilder(0, ['VIEW', 'CREATE', 'EDIT']),
                new EntityMaskBuilder(32768, ['DELETE', 'ASSIGN', 'SHARE'])
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
                'Oro\Bundle\SecurityBundle\Tests\Unit\Acl\Domain\Fixtures\Entity\TestEntity',
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
                        $this->getPermission(6, 'SHARE'),
                        $this->getPermission(7, 'PERMISSION')
                    ],
                ],
                'expected' => ['VIEW', 'CREATE', 'EDIT', 'DELETE', 'ASSIGN', 'SHARE', 'PERMISSION'],
            ],
            'TestEntity1 + config' => [
                'input' => [
                    'type' => 'TestEntity1',
                    'owner' => false,
                    'entityConfig' => ['VIEW', 'CREATE', 'ASSIGN', 'SHARE', 'PERMISSION'],
                    'permissions' => [
                        $this->getPermission(1, 'VIEW'),
                        $this->getPermission(2, 'CREATE'),
                        $this->getPermission(3, 'ASSIGN'),
                        $this->getPermission(4, 'SHARE'),
                        $this->getPermission(5, 'PERMISSION')
                    ],
                ],
                'expected' => ['VIEW', 'CREATE', 'PERMISSION'],
            ],
            'TestEntity1 + config + owner' => [
                'input' => [
                    'type' => 'TestEntity1',
                    'owner' => true,
                    'entityConfig' => ['VIEW', 'CREATE', 'ASSIGN', 'SHARE', 'PERMISSION'],
                    'permissions' => [
                        $this->getPermission(1, 'VIEW'),
                        $this->getPermission(2, 'CREATE'),
                        $this->getPermission(3, 'ASSIGN'),
                        $this->getPermission(4, 'SHARE'),
                        $this->getPermission(5, 'PERMISSION')
                    ],
                ],
                'expected' => ['VIEW', 'CREATE', 'ASSIGN', 'SHARE', 'PERMISSION'],
            ],
            'TestEntity1 + empty owner' => [
                'input' => [
                    'type' => 'TestEntity1',
                    'owner' => false,
                    'entityConfig' => [],
                    'permissions' => [
                        $this->getPermission(1, 'VIEW'),
                        $this->getPermission(2, 'ASSIGN'),
                        $this->getPermission(3, 'SHARE'),
                        $this->getPermission(4, 'PERMISSION'),
                    ],
                ],
                'expected' => ['VIEW', 'PERMISSION'],
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
                ((1 << 9) | 32768) /* MASK_ASSIGN_SYSTEM */ | ((1 << 10) | 32768) /* MASK_SHARE_BASIC */,
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
            [(1 << 14) + 32768 /*MASK_SHARE_SYSTEM*/],
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
            [(1 << 14) + 32768 /* MASK_SHARE_SYSTEM */],
            [1 << 3 /* MASK_VIEW_GLOBAL */],
            [1 << 8 /* MASK_CREATE_GLOBAL */],
            [1 << 13 /* MASK_EDIT_GLOBAL */],
            [(1 << 3) + 32768 /* MASK_DELETE_GLOBAL */],
            [(1 << 8) + 32768 /* MASK_ASSIGN_GLOBAL */],
            [(1 << 13) + 32768 /* MASK_SHARE_GLOBAL */],
            [1 << 2 /* MASK_VIEW_DEEP */],
            [1 << 7 /* MASK_CREATE_DEEP */],
            [1 << 12 /* MASK_EDIT_DEEP */],
            [(1 << 2) + 32768 /* MASK_DELETE_DEEP */],
            [(1 << 7) + 32768 /* MASK_ASSIGN_DEEP */],
            [(1 << 12) + 32768 /* MASK_SHARE_DEEP */],
            [1 << 1 /* MASK_VIEW_LOCAL */],
            [1 << 6 /* MASK_CREATE_LOCAL */],
            [1 << 11 /* MASK_EDIT_LOCAL */],
            [(1 << 1) + 32768 /* MASK_DELETE_LOCAL */],
            [(1 << 6) + 32768 /* MASK_ASSIGN_LOCAL */],
            [(1 << 11) + 32768 /* MASK_SHARE_LOCAL */],
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
            [(1 << 14) + 32768 /* MASK_SHARE_SYSTEM */],
            [1 << 3 /* MASK_VIEW_GLOBAL */],
            [1 << 8 /* MASK_CREATE_GLOBAL */],
            [1 << 13 /* MASK_EDIT_GLOBAL */],
            [(1 << 4) + 32768 /* MASK_DELETE_GLOBAL */],
            [(1 << 9) + 32768 /* MASK_ASSIGN_GLOBAL */],
            [(1 << 14) + 32768 /* MASK_SHARE_GLOBAL */],
            [1 << 2 /* MASK_VIEW_DEEP */],
            [1 << 7 /* MASK_CREATE_DEEP */],
            [1 << 12 /* MASK_EDIT_DEEP */],
            [(1 << 4) + 32768 /* MASK_DELETE_DEEP */],
            [(1 << 9) + 32768 /* MASK_ASSIGN_DEEP */],
            [(1 << 14) + 32768 /* MASK_SHARE_DEEP */],
            [1 << 1 /* MASK_VIEW_LOCAL */],
            [1 << 6 /* MASK_CREATE_LOCAL */],
            [1 << 11 /* MASK_EDIT_LOCAL */],
            [(1 << 4) + 32768 /* MASK_DELETE_LOCAL */],
            [(1 << 9) + 32768 /* MASK_ASSIGN_LOCAL */],
            [(1 << 14) + 32768 /* MASK_SHARE_LOCAL */],
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
            [(1 << 14) + 32768 /* MASK_SHARE_SYSTEM */],
            [1 << 3 /* MASK_VIEW_GLOBAL */],
            [1 << 8 /* MASK_CREATE_GLOBAL */],
            [1 << 13 /* MASK_EDIT_GLOBAL */],
            [(1 << 4) + 32768 /* MASK_DELETE_GLOBAL */],
            [(1 << 9) + 32768 /* MASK_ASSIGN_GLOBAL */],
            [(1 << 14) + 32768 /* MASK_SHARE_GLOBAL */],
            [1 << 2 /* MASK_VIEW_DEEP */],
            [1 << 7 /* MASK_CREATE_DEEP */],
            [1 << 12 /* MASK_EDIT_DEEP */],
            [(1 << 4) + 32768 /* MASK_DELETE_DEEP */],
            [(1 << 9) + 32768 /* MASK_ASSIGN_DEEP */],
            [(1 << 14) + 32768 /* MASK_SHARE_DEEP */],
            [1 << 1 /* MASK_VIEW_LOCAL */],
            [1 << 6 /* MASK_CREATE_LOCAL */],
            [1 << 11 /* MASK_EDIT_LOCAL */],
            [(1 << 4) + 32768 /* MASK_DELETE_LOCAL */],
            [(1 << 9) + 32768 /* MASK_ASSIGN_LOCAL */],
            [(1 << 14) + 32768 /* MASK_SHARE_LOCAL */],
            [1 << 0 /* MASK_VIEW_BASIC */],
            [1 << 5 /* MASK_CREATE_BASIC */],
            [1 << 10 /* MASK_EDIT_BASIC */],
            [(1 << 0) + 32768 /* MASK_DELETE_BASIC */],
            [(1 << 5) + 32768 /* MASK_ASSIGN_BASIC */],
            [(1 << 10) + 32768 /* MASK_SHARE_BASIC */],
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
            [(1 << 14) + 32768 /* MASK_SHARE_SYSTEM */],
            [1 << 3 /* MASK_VIEW_GLOBAL */],
            [1 << 8 /* MASK_CREATE_GLOBAL */],
            [1 << 13 /* MASK_EDIT_GLOBAL */],
            [(1 << 4) + 32768 /* MASK_DELETE_GLOBAL */],
            [(1 << 9) + 32768 /* MASK_ASSIGN_GLOBAL */],
            [(1 << 14) + 32768 /* MASK_SHARE_GLOBAL */],
            [(1 << 4) /* MASK_VIEW_SYSTEM */ | (1 << 8) /* MASK_CREATE_GLOBAL */]
        ];
    }

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
    public function testSupports($id, $type, $class, $isEntity, $expected)
    {
        /** @var \PHPUnit_Framework_MockObject_MockObject|EntityClassResolver $entityClassResolverMock */
        $entityClassResolverMock = $this->getMockBuilder('Oro\Bundle\EntityBundle\ORM\EntityClassResolver')
            ->disableOriginalConstructor()
            ->getMock();
        $entityClassResolverMock->expects($isEntity ? $this->once() : $this->never())
            ->method('getEntityClass')
            ->with($class)
            ->willReturn($class);
        $entityClassResolverMock->expects($this->once())
            ->method('isEntity')
            ->with($class)
            ->willReturn($expected);

        /** @var \PHPUnit_Framework_MockObject_MockObject|EntitySecurityMetadataProvider $entityMetadataProvider */
        $entityMetadataProvider = $this
            ->getMockBuilder('Oro\Bundle\SecurityBundle\Metadata\EntitySecurityMetadataProvider')
            ->disableOriginalConstructor()
            ->getMock();

        $extension = new EntityAclExtension(
            new ObjectIdAccessor($this->doctrineHelper),
            $entityClassResolverMock,
            $entityMetadataProvider,
            $this->metadataProvider,
            $this->decisionMaker,
            $this->permissionManager,
            $this->groupProvider
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
                'expected' => false
            ],
            [
                'id' => 'entity',
                'type' => '\stdClass',
                'class' => '\stdClass',
                'isEntity' => true,
                'expected' => true
            ],
            [
                'id' => 'entity',
                'type' => '@\stdClass',
                'class' => '\stdClass',
                'isEntity' => true,
                'expected' => true
            ],
            [
                'id' => 'entity',
                'type' => 'group@\stdClass',
                'class' => '\stdClass',
                'isEntity' => true,
                'expected' => true
            ]
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
    protected function getPermission($id, $name)
    {
        $permission = new Permission();

        $reflection = new \ReflectionClass('Oro\Bundle\SecurityBundle\Entity\Permission');
        $reflectionProperty = $reflection->getProperty('id');
        $reflectionProperty->setAccessible(true);
        $reflectionProperty->setValue($permission, $id);

        $permission->setName($name);

        return $permission;
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|PermissionManager
     */
    protected function getPermissionManagerMock()
    {
        $mock = $this->getMockBuilder('Oro\Bundle\SecurityBundle\Acl\Permission\PermissionManager')
            ->disableOriginalConstructor()
            ->getMock();
        $mock->expects($this->any())
            ->method('getPermissionsMap')
            ->willReturn([
                'VIEW'   => 1,
                'CREATE' => 2,
                'EDIT'   => 3,
                'DELETE' => 4,
                'ASSIGN' => 5,
                'SHARE'  => 6
            ]);

        return $mock;
    }
}
