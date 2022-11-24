<?php

namespace Oro\Bundle\SecurityBundle\Tests\Unit\Acl\Extension;

use Doctrine\Inflector\Inflector;
use Doctrine\Inflector\Rules\English\InflectorFactory;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\EntityBundle\ORM\EntityClassResolver;
use Oro\Bundle\SecurityBundle\Acl\AccessLevel;
use Oro\Bundle\SecurityBundle\Acl\Domain\ObjectIdAccessor;
use Oro\Bundle\SecurityBundle\Acl\Domain\ObjectIdentityFactory;
use Oro\Bundle\SecurityBundle\Acl\Exception\InvalidAclMaskException;
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
use Oro\Bundle\SecurityBundle\Owner\Metadata\OwnershipMetadataProvider;
use Oro\Bundle\SecurityBundle\Owner\OwnerTree;
use Oro\Bundle\SecurityBundle\Owner\OwnerTreeProvider;
use Oro\Bundle\SecurityBundle\Tests\Unit\Acl\Domain\Fixtures\Entity\BusinessUnit;
use Oro\Bundle\SecurityBundle\Tests\Unit\Acl\Domain\Fixtures\Entity\Organization;
use Oro\Bundle\SecurityBundle\Tests\Unit\Acl\Domain\Fixtures\Entity\TestEntity;
use Oro\Bundle\SecurityBundle\Tests\Unit\Acl\Domain\Fixtures\Entity\User;
use Oro\Bundle\SecurityBundle\Tests\Unit\Stub\OwnershipMetadataProviderStub;
use Oro\Bundle\SecurityBundle\Tests\Unit\TestHelper;
use Oro\Component\Testing\ReflectionUtil;
use Symfony\Component\Security\Acl\Domain\ObjectIdentity;
use Symfony\Component\Security\Acl\Util\ClassUtils;
use Symfony\Contracts\Cache\ItemInterface;

/**
 * @SuppressWarnings(PHPMD.ExcessiveClassLength)
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 * @SuppressWarnings(PHPMD.ExcessivePublicCount)
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
class EntityAclExtensionTest extends \PHPUnit\Framework\TestCase
{
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

    /** @var Inflector */
    private $inflector;

    protected function setUp(): void
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

        $treeProvider = $this->createMock(OwnerTreeProvider::class);
        $treeProvider->expects($this->any())
            ->method('getTree')
            ->willReturn($this->tree);

        $this->inflector = (new InflectorFactory())->build();

        $entityOwnerAccessor = new EntityOwnerAccessor($this->metadataProvider, $this->inflector);
        $this->decisionMaker = new EntityOwnershipDecisionMaker(
            $treeProvider,
            new ObjectIdAccessor($this->doctrineHelper),
            $entityOwnerAccessor,
            $this->metadataProvider,
            $this->createMock(TokenAccessorInterface::class)
        );

        $this->permissionManager = $this->getPermissionManager();

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

        $this->buildTree();
    }

    /**
     * @dataProvider validateMaskForOrganizationProvider
     */
    public function testValidateMaskForOrganization(int $mask)
    {
        $this->extension->validateMask($mask, new Organization());
    }

    /**
     * @dataProvider validateMaskForOrganizationInvalidProvider
     */
    public function testValidateMaskForOrganizationInvalid(int $mask)
    {
        $this->expectException(InvalidAclMaskException::class);
        $this->extension->validateMask($mask, new Organization());
    }

    /**
     * @dataProvider validateMaskForBusinessUnitProvider
     */
    public function testValidateMaskForBusinessUnit(int $mask)
    {
        $this->extension->validateMask($mask, new BusinessUnit());
    }

    /**
     * @dataProvider validateMaskForBusinessUnitInvalidProvider
     */
    public function testValidateMaskForBusinessUnitInvalid(int $mask)
    {
        $this->expectException(InvalidAclMaskException::class);
        $this->extension->validateMask($mask, new BusinessUnit());
    }

    /**
     * @dataProvider validateMaskForUserProvider
     */
    public function testValidateMaskForUser(int $mask)
    {
        $this->extension->validateMask($mask, new User());
    }

    /**
     * @dataProvider validateMaskForUserInvalidProvider
     */
    public function testValidateMaskForUserInvalid(int $mask)
    {
        $this->expectException(InvalidAclMaskException::class);
        $this->extension->validateMask($mask, new User());
    }

    /**
     * @dataProvider validateMaskForOrganizationOwnedProvider
     */
    public function testValidateMaskForOrganizationOwned(int $mask)
    {
        $this->metadataProvider->setMetadata(
            TestEntity::class,
            new OwnershipMetadata('ORGANIZATION', 'owner', 'owner_id')
        );
        $this->extension->validateMask($mask, new TestEntity());
    }

    /**
     * @dataProvider validateMaskForOrganizationOwnedInvalidProvider
     */
    public function testValidateMaskForOrganizationOwnedInvalid(int $mask)
    {
        $this->expectException(InvalidAclMaskException::class);
        $this->metadataProvider->setMetadata(
            TestEntity::class,
            new OwnershipMetadata('ORGANIZATION', 'owner', 'owner_id')
        );
        $this->extension->validateMask($mask, new TestEntity());
    }

    /**
     * @dataProvider validateMaskForUserOwnedProvider
     */
    public function testValidateMaskForUserOwned(int $mask)
    {
        $this->metadataProvider->setMetadata(
            TestEntity::class,
            new OwnershipMetadata('USER', 'owner', 'owner_id')
        );
        $this->extension->validateMask($mask, new TestEntity());
    }

    /**
     * @dataProvider validateMaskForUserOwnedInvalidProvider
     */
    public function testValidateMaskForUserOwnedInvalid(int $mask)
    {
        $this->expectException(InvalidAclMaskException::class);
        $this->metadataProvider->setMetadata(
            TestEntity::class,
            new OwnershipMetadata('USER', 'owner', 'owner_id')
        );
        $this->extension->validateMask($mask, new TestEntity());
    }

    /**
     * @dataProvider validateMaskForUserOwnedProvider
     */
    public function testValidateMaskForRoot(int $mask)
    {
        $this->extension->validateMask($mask, new ObjectIdentity('entity', ObjectIdentityFactory::ROOT_IDENTITY_TYPE));
    }

    /**
     * @dataProvider validateMaskForUserOwnedInvalidProvider
     */
    public function testValidateMaskForRootInvalid(int $mask)
    {
        $this->expectException(InvalidAclMaskException::class);
        $this->extension->validateMask($mask, new ObjectIdentity('entity', ObjectIdentityFactory::ROOT_IDENTITY_TYPE));
    }

    /**
     * @dataProvider validateMaskForRootWithoutSystemAccessLevelProvider
     */
    public function testValidateMaskForRootWithoutSystemAccessLevel(int $mask)
    {
        $metadataProvider = $this->createMock(OwnershipMetadataProvider::class);
        $metadataProvider->expects($this->any())
            ->method('getMaxAccessLevel')
            ->willReturn(AccessLevel::GLOBAL_LEVEL);

        $entityOwnerAccessor = new EntityOwnerAccessor($this->metadataProvider, $this->inflector);

        $extension = TestHelper::get($this)->createEntityAclExtension(
            $metadataProvider,
            $this->tree,
            new ObjectIdAccessor($this->doctrineHelper),
            $this->decisionMaker,
            $entityOwnerAccessor,
            $this->permissionManager,
            $this->groupProvider
        );

        $extension->validateMask($mask, new ObjectIdentity('entity', ObjectIdentityFactory::ROOT_IDENTITY_TYPE));
    }

    /**
     * @dataProvider validateMaskForRootWithoutSystemAccessLevelInvalidProvider
     */
    public function testValidateMaskForRootWithoutSystemAccessLevelInvalid(int $mask)
    {
        $this->expectException(InvalidAclMaskException::class);

        $entityOwnerAccessor = new EntityOwnerAccessor($this->metadataProvider, $this->inflector);

        $metadataProvider = $this->createMock(OwnershipMetadataProvider::class);
        $metadataProvider->expects($this->any())
            ->method('getMaxAccessLevel')
            ->willReturn(AccessLevel::GLOBAL_LEVEL);

        $extension = TestHelper::get($this)->createEntityAclExtension(
            $metadataProvider,
            $this->tree,
            new ObjectIdAccessor($this->doctrineHelper),
            $this->decisionMaker,
            $entityOwnerAccessor,
            $this->permissionManager,
            $this->groupProvider
        );

        $extension->validateMask($mask, new ObjectIdentity('entity', ObjectIdentityFactory::ROOT_IDENTITY_TYPE));
    }

    public function testGetDefaultPermission()
    {
        self::assertSame('', $this->extension->getDefaultPermission());
    }

    /**
     * @dataProvider getPermissionGroupMaskProvider
     */
    public function testGetPermissionGroupMask(int $mask, ?int $expectedPermissionGroupMask)
    {
        self::assertSame($expectedPermissionGroupMask, $this->extension->getPermissionGroupMask($mask));
    }

    public function getPermissionGroupMaskProvider(): array
    {
        return [
            [0, null],
            [1, 31],
            [2, 31],
            [4, 31],
            [8, 31],
            [16, 31],
            [32, 31 << 5],
            [32 << 5, 31 << 10],
            [EntityMaskBuilder::REMOVE_SERVICE_BITS + 1 + 1, EntityMaskBuilder::REMOVE_SERVICE_BITS + 1 + 31]
        ];
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
            ['VIEW', 'CREATE', 'EDIT', 'DELETE', 'ASSIGN'],
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
        $this->metadataProvider->getCacheMock()
            ->expects(self::any())
            ->method('get')
            ->willReturn(true);

        $entityClassResolver = $this->createMock(EntityClassResolver::class);
        $doctrineHelper = $this->createMock(DoctrineHelper::class);
        $fieldAclExtension = $this->createMock(FieldAclExtension::class);

        $extension = new EntityAclExtension(
            new ObjectIdAccessor($doctrineHelper),
            $entityClassResolver,
            $this->securityMetadataProvider,
            $this->metadataProvider,
            new EntityOwnerAccessor($this->metadataProvider, $this->inflector),
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

        $token =$this->createMock(UsernamePasswordOrganizationToken::class);

        $this->assertTrue($this->extension->decideIsGranting(1, $object, $token));
    }

    /**
     * @dataProvider decideIsGrantingProvider
     */
    public function testDecideIsGranting(
        int $triggeredMask,
        ?User $user,
        Organization $organization,
        object|string|null $object,
        bool $expectedResult
    ) {
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

        $token =$this->createMock(UsernamePasswordOrganizationToken::class);
        $token->expects($this->any())
            ->method('getOrganization')
            ->willReturn($organization);
        $token->expects($this->any())
            ->method('getUser')
            ->willReturn($user);
        $this->metadataProvider->getCacheMock()
            ->expects(self::any())
            ->method('get')
            ->willReturn(true);

        $this->assertEquals($expectedResult, $this->extension->decideIsGranting($triggeredMask, $object, $token));
    }

    /**
     * @dataProvider getMaskBuilderProvider
     */
    public function testGetMaskBuilder(string $permission, int $identity, array $permissions)
    {
        $this->assertEquals(
            new EntityMaskBuilder($identity, $permissions),
            $this->extension->getMaskBuilder($permission)
        );
    }

    public function getMaskBuilderProvider(): array
    {
        return [
            [
                'permission' => 'VIEW',
                'identity' => 0,
                'permissions' => ['VIEW', 'CREATE', 'EDIT', 'DELETE', 'ASSIGN']
            ],
            [
                'permission' => 'CREATE',
                'identity' => 0,
                'permissions' => ['VIEW', 'CREATE', 'EDIT', 'DELETE', 'ASSIGN']
            ],
            [
                'permission' => 'EDIT',
                'identity' => 0,
                'permissions' => ['VIEW', 'CREATE', 'EDIT', 'DELETE', 'ASSIGN']
            ],
            [
                'permission' => 'DELETE',
                'identity' => 0,
                'permissions' => ['VIEW', 'CREATE', 'EDIT', 'DELETE', 'ASSIGN']
            ],
            [
                'permission' => 'ASSIGN',
                'identity' => 0,
                'permissions' => ['VIEW', 'CREATE', 'EDIT', 'DELETE', 'ASSIGN']
            ],
            [
                'permission' => 'PERMIT',
                'identity' => 33554432,
                'permissions' => ['PERMIT']
            ],
        ];
    }

    public function testGetAllMaskBuilders()
    {
        $this->assertEquals(
            [
                new EntityMaskBuilder(0, ['VIEW', 'CREATE', 'EDIT', 'DELETE', 'ASSIGN']),
                new EntityMaskBuilder(33554432, ['PERMIT'])
            ],
            $this->extension->getAllMaskBuilders()
        );
    }

    /**
     * @dataProvider adaptRootMaskProvider
     */
    public function testAdaptRootMask(object $object, ?string $ownerType, int $aceMask, int $expectedMask)
    {
        if ($ownerType !== null) {
            $this->metadataProvider->setMetadata(
                TestEntity::class,
                new OwnershipMetadata($ownerType, 'owner', 'owner_id')
            );
        }
        $this->metadataProvider->getCacheMock()
            ->expects(self::any())
            ->method('get')
            ->willReturn(true);

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
     */
    public function testGetAccessLevel(int $mask, int $expectedLevel, string $permission = null)
    {
        $this->assertEquals($expectedLevel, $this->extension->getAccessLevel($mask, $permission));
    }

    public function getAccessLevelProvider(): array
    {
        return [
            [
                'mask' => 0 /* GROUP_NONE */,
                'expectedLevel' => AccessLevel::NONE_LEVEL
            ],
            [
                'mask' => 33554432 /* GROUP_NONE */,
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
        $this->metadataProvider->getCacheMock()
            ->expects(self::once())
            ->method('get')
            ->willReturnCallback(function ($cacheKey, $callback) {
                return $callback($this->createMock(ItemInterface::class));
            });
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
     * @dataProvider accessLevelProvider
     */
    public function testGetAccessLevelNamesForNonRoot(OwnershipMetadata $metadata, array $expected)
    {
        $object = new ObjectIdentity('entity', \stdClass::class);

        $this->metadataProvider->setMetadata(\stdClass::class, $metadata);

        $this->assertEquals(
            $expected,
            $this->extension->getAccessLevelNames($object)
        );
    }

    public function accessLevelProvider(): array
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

    public function getAllowedPermissionsProvider(): array
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

    public function decideIsGrantingProvider(): array
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

    public static function adaptRootMaskProvider(): array
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
                (1 << 19) /* MASK_DELETE_SYSTEM */,
                (1 << 19) /* MASK_DELETE_SYSTEM */
            ],
            [
                new TestEntity(),
                null,
                (1 << 24) /* MASK_ASSIGN_SYSTEM */,
                0 /* GROUP_NONE */
            ],
            [
                new TestEntity(),
                null,
                ((1 << 4) | 33554432) /* MASK_PERMIT_SYSTEM */,
                ((1 << 4) | 33554432) /* MASK_PERMIT_SYSTEM */
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

    public static function validateMaskForOrganizationProvider(): array
    {
        return [
            [1 << 4 /* MASK_VIEW_SYSTEM */],
            [1 << 9 /* MASK_CREATE_SYSTEM */],
            [1 << 14 /* MASK_EDIT_SYSTEM */],
            [1 << 19 /* MASK_DELETE_SYSTEM */],
            [(1 << 4) + 33554432 /* MASK_PERMIT_SYSTEM */],
            [(1 << 4) /* MASK_VIEW_SYSTEM */ | ((1 << 4) + 33554432) /* MASK_PERMIT_SYSTEM */],
        ];
    }

    public static function validateMaskForOrganizationInvalidProvider(): array
    {
        return [
            [(1 << 9) + 33554432 /*MASK_PERMIT_SYSTEM*/],
            [1 << 3 /*MASK_VIEW_GLOBAL*/],
            [1 << 2 /*MASK_VIEW_DEEP*/],
            [1 << 1 /*MASK_VIEW_LOCAL*/],
            [1 << 0 /*MASK_VIEW_BASIC*/]
        ];
    }

    public static function validateMaskForBusinessUnitProvider(): array
    {
        return [
            [1 << 4 /* MASK_VIEW_SYSTEM */],
            [1 << 9 /* MASK_CREATE_SYSTEM */],
            [1 << 14 /* MASK_EDIT_SYSTEM */],
            [1 << 19 /* MASK_DELETE_SYSTEM */],
            [1 << 24 /* MASK_ASSIGN_SYSTEM */],
            [(1 << 4) + 33554432 /* MASK_PERMIT_SYSTEM */],
            [1 << 3 /* MASK_VIEW_GLOBAL */],
            [1 << 8 /* MASK_CREATE_GLOBAL */],
            [1 << 13 /* MASK_EDIT_GLOBAL */],
            [1 << 18 /* MASK_DELETE_GLOBAL */],
            [1 << 23 /* MASK_ASSIGN_GLOBAL */],
            [(1 << 3) + 33554432 /* MASK_PERMIT_GLOBAL */],
            [1 << 2 /* MASK_VIEW_DEEP */],
            [1 << 7 /* MASK_CREATE_DEEP */],
            [1 << 12 /* MASK_EDIT_DEEP */],
            [1 << 17 /* MASK_DELETE_DEEP */],
            [1 << 22 /* MASK_ASSIGN_DEEP */],
            [(1 << 2) + 33554432 /* MASK_PERMIT_DEEP */],
            [1 << 1 /* MASK_VIEW_LOCAL */],
            [1 << 6 /* MASK_CREATE_LOCAL */],
            [1 << 11 /* MASK_EDIT_LOCAL */],
            [1 << 16 /* MASK_DELETE_LOCAL */],
            [1 << 21 /* MASK_ASSIGN_LOCAL */],
            [(1 << 1) + 33554432 /* MASK_PERMIT_LOCAL */],
            [(1 << 4) /* MASK_VIEW_SYSTEM */ | (1 << 8) /* MASK_CREATE_GLOBAL */ | (1 << 12) /* MASK_EDIT_DEEP */],
            [(1 << 3) /* MASK_VIEW_GLOBAL */ | (1 << 7) /* MASK_CREATE_DEEP */ | (1 << 11) /* MASK_EDIT_LOCAL */]
        ];
    }

    public static function validateMaskForBusinessUnitInvalidProvider(): array
    {
        return [
            [1 << 0 /* MASK_VIEW_BASIC */],
            [(1 << 4) /* MASK_VIEW_SYSTEM */ | (1 << 3) /* MASK_VIEW_GLOBAL */],
            [(1 << 3) /* MASK_VIEW_GLOBAL */ | (1 << 2) /* MASK_VIEW_DEEP */],
            [(1 << 2) /* MASK_VIEW_DEEP */ | (1 << 1) /* MASK_VIEW_LOCAL */]
        ];
    }

    public static function validateMaskForUserProvider(): array
    {
        return [
            [1 << 4 /* MASK_VIEW_SYSTEM */],
            [1 << 9 /* MASK_CREATE_SYSTEM */],
            [1 << 14 /* MASK_EDIT_SYSTEM */],
            [1 << 19 /* MASK_DELETE_SYSTEM */],
            [1 << 24 /* MASK_ASSIGN_SYSTEM */],
            [(1 << 4) + 33554432 /* MASK_PERMIT_SYSTEM */],
            [1 << 3 /* MASK_VIEW_GLOBAL */],
            [1 << 8 /* MASK_CREATE_GLOBAL */],
            [1 << 13 /* MASK_EDIT_GLOBAL */],
            [1 << 18 /* MASK_DELETE_GLOBAL */],
            [1 << 23 /* MASK_ASSIGN_GLOBAL */],
            [(1 << 3) + 33554432 /* MASK_PERMIT_GLOBAL */],
            [1 << 2 /* MASK_VIEW_DEEP */],
            [1 << 7 /* MASK_CREATE_DEEP */],
            [1 << 12 /* MASK_EDIT_DEEP */],
            [1 << 17 /* MASK_DELETE_DEEP */],
            [1 << 22 /* MASK_ASSIGN_DEEP */],
            [(1 << 2) + 33554432 /* MASK_PERMIT_DEEP */],
            [1 << 1 /* MASK_VIEW_LOCAL */],
            [1 << 6 /* MASK_CREATE_LOCAL */],
            [1 << 11 /* MASK_EDIT_LOCAL */],
            [1 << 16 /* MASK_DELETE_LOCAL */],
            [1 << 21 /* MASK_ASSIGN_LOCAL */],
            [(1 << 1) + 33554432 /* MASK_PERMIT_LOCAL */],
            [(1 << 4) /* MASK_VIEW_SYSTEM */ | (1 << 8) /* MASK_CREATE_GLOBAL */ | (1 << 12) /* MASK_EDIT_DEEP */],
            [(1 << 3) /* MASK_VIEW_GLOBAL */ | (1 << 7) /* MASK_CREATE_DEEP */ | (1 << 11) /* MASK_EDIT_LOCAL */]
        ];
    }

    public static function validateMaskForUserInvalidProvider(): array
    {
        return [
            [1 << 0 /* MASK_VIEW_BASIC */],
            [(1 << 4) /* MASK_VIEW_SYSTEM */ | (1 << 3) /* MASK_VIEW_GLOBAL */],
            [(1 << 3) /* MASK_VIEW_GLOBAL */ | (1 << 2) /* MASK_VIEW_DEEP */],
            [(1 << 2) /* MASK_VIEW_DEEP */ | (1 << 1) /* MASK_VIEW_LOCAL */]
        ];
    }

    public static function validateMaskForUserOwnedProvider(): array
    {
        return [
            [1 << 4 /* MASK_VIEW_SYSTEM */],
            [1 << 9 /* MASK_CREATE_SYSTEM */],
            [1 << 14 /* MASK_EDIT_SYSTEM */],
            [1 << 19 /* MASK_DELETE_SYSTEM */],
            [1 << 24 /* MASK_ASSIGN_SYSTEM */],
            [(1 << 4) + 33554432 /* MASK_PERMIT_SYSTEM */],
            [1 << 3 /* MASK_VIEW_GLOBAL */],
            [1 << 8 /* MASK_CREATE_GLOBAL */],
            [1 << 13 /* MASK_EDIT_GLOBAL */],
            [1 << 18 /* MASK_DELETE_GLOBAL */],
            [1 << 23 /* MASK_ASSIGN_GLOBAL */],
            [(1 << 3) + 33554432 /* MASK_PERMIT_GLOBAL */],
            [1 << 2 /* MASK_VIEW_DEEP */],
            [1 << 7 /* MASK_CREATE_DEEP */],
            [1 << 12 /* MASK_EDIT_DEEP */],
            [1 << 17 /* MASK_DELETE_DEEP */],
            [1 << 22 /* MASK_ASSIGN_DEEP */],
            [(1 << 2) + 33554432 /* MASK_PERMIT_DEEP */],
            [1 << 1 /* MASK_VIEW_LOCAL */],
            [1 << 6 /* MASK_CREATE_LOCAL */],
            [1 << 11 /* MASK_EDIT_LOCAL */],
            [1 << 16 /* MASK_DELETE_LOCAL */],
            [1 << 21 /* MASK_ASSIGN_LOCAL */],
            [(1 << 1) + 33554432 /* MASK_PERMIT_LOCAL */],
            [1 << 0 /* MASK_VIEW_BASIC */],
            [1 << 5 /* MASK_CREATE_BASIC */],
            [1 << 10 /* MASK_EDIT_BASIC */],
            [1 << 15 /* MASK_DELETE_BASIC */],
            [1 << 20 /* MASK_ASSIGN_BASIC */],
            [(1 << 0) + 33554432 /* MASK_PERMIT_BASIC */],
            [(1 << 4) /* MASK_VIEW_SYSTEM */ | (1 << 8) /* MASK_CREATE_GLOBAL */ | (1 << 12) /* MASK_EDIT_DEEP */],
            [(1 << 3) /* MASK_VIEW_GLOBAL */ | (1 << 7) /* MASK_CREATE_DEEP */ | (1 << 11) /* MASK_EDIT_LOCAL */],
            [(1 << 2) /* MASK_VIEW_DEEP */ | (1 << 6) /* MASK_CREATE_LOCAL */ | (1 << 10) /* MASK_EDIT_BASIC */]
        ];
    }

    public static function validateMaskForRootWithoutSystemAccessLevelInvalidProvider(): array
    {
        return [
            [1 << 4 /* MASK_VIEW_SYSTEM */],
            [1 << 9 /* MASK_CREATE_SYSTEM */],
            [1 << 14 /* MASK_EDIT_SYSTEM */],
            [1 << 19 /* MASK_DELETE_SYSTEM */],
            [1 << 24 /* MASK_ASSIGN_SYSTEM */],
            [(1 << 4) + 33554432 /* MASK_PERMIT_SYSTEM */],
            [(1 << 4) /* MASK_VIEW_SYSTEM */ | (1 << 8) /* MASK_CREATE_GLOBAL */ | (1 << 12) /* MASK_EDIT_DEEP */]
        ];
    }

    public static function validateMaskForRootWithoutSystemAccessLevelProvider(): array
    {
        return [
            [1 << 3 /* MASK_VIEW_GLOBAL */],
            [1 << 8 /* MASK_CREATE_GLOBAL */],
            [1 << 13 /* MASK_EDIT_GLOBAL */],
            [1 << 18 /* MASK_DELETE_GLOBAL */],
            [1 << 23 /* MASK_ASSIGN_GLOBAL */],
            [(1 << 3) + 33554432 /* MASK_PERMIT_GLOBAL */],
            [1 << 2 /* MASK_VIEW_DEEP */],
            [1 << 7 /* MASK_CREATE_DEEP */],
            [1 << 12 /* MASK_EDIT_DEEP */],
            [1 << 17 /* MASK_DELETE_DEEP */],
            [1 << 22 /* MASK_ASSIGN_DEEP */],
            [(1 << 2) + 33554432 /* MASK_PERMIT_DEEP */],
            [1 << 1 /* MASK_VIEW_LOCAL */],
            [1 << 6 /* MASK_CREATE_LOCAL */],
            [1 << 11 /* MASK_EDIT_LOCAL */],
            [1 << 16 /* MASK_DELETE_LOCAL */],
            [1 << 21 /* MASK_ASSIGN_LOCAL */],
            [(1 << 1) + 33554432 /* MASK_PERMIT_LOCAL */],
            [1 << 0 /* MASK_VIEW_BASIC */],
            [1 << 5 /* MASK_CREATE_BASIC */],
            [1 << 10 /* MASK_EDIT_BASIC */],
            [1 << 15 /* MASK_DELETE_BASIC */],
            [1 << 20 /* MASK_ASSIGN_BASIC */],
            [(1 << 0) + 33554432 /* MASK_PERMIT_BASIC */],
            [(1 << 3) /* MASK_VIEW_GLOBAL */ | (1 << 7) /* MASK_CREATE_DEEP */ | (1 << 11) /* MASK_EDIT_LOCAL */],
            [(1 << 2) /* MASK_VIEW_DEEP */ | (1 << 6) /* MASK_CREATE_LOCAL */ | (1 << 10) /* MASK_EDIT_BASIC */]
        ];
    }

    public static function validateMaskForUserOwnedInvalidProvider(): array
    {
        return [
            [(1 << 4) /* MASK_VIEW_SYSTEM */ | (1 << 3) /* MASK_VIEW_GLOBAL */],
            [(1 << 3) /* MASK_VIEW_GLOBAL */ | (1 << 2) /* MASK_VIEW_DEEP */],
            [(1 << 2) /* MASK_VIEW_DEEP */ | (1 << 1) /* MASK_VIEW_LOCAL */],
            [(1 << 1) /* MASK_VIEW_LOCAL */ | (1 << 0) /* MASK_VIEW_BASIC */]
        ];
    }

    public static function validateMaskForOrganizationOwnedProvider(): array
    {
        return [
            [1 << 4 /* MASK_VIEW_SYSTEM */],
            [1 << 9 /* MASK_CREATE_SYSTEM */],
            [1 << 14 /* MASK_EDIT_SYSTEM */],
            [1 << 19 /* MASK_DELETE_SYSTEM */],
            [1 << 24 /* MASK_ASSIGN_SYSTEM */],
            [(1 << 4) + 33554432 /* MASK_PERMIT_SYSTEM */],
            [1 << 3 /* MASK_VIEW_GLOBAL */],
            [1 << 8 /* MASK_CREATE_GLOBAL */],
            [1 << 13 /* MASK_EDIT_GLOBAL */],
            [1 << 18 /* MASK_DELETE_GLOBAL */],
            [1 << 23 /* MASK_ASSIGN_GLOBAL */],
            [(1 << 3) + 33554432 /* MASK_PERMIT_GLOBAL */],
            [(1 << 4) /* MASK_VIEW_SYSTEM */ | (1 << 8) /* MASK_CREATE_GLOBAL */]
        ];
    }

    public static function validateMaskForOrganizationOwnedInvalidProvider(): array
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
     */
    public function testSupports(
        string $id,
        string $type,
        string $class,
        bool $isEntity,
        bool $isProtectedEntity,
        bool $expected
    ) {
        $entityClassResolver = $this->createMock(EntityClassResolver::class);
        $entityClassResolver->expects($isEntity ? $this->once() : $this->never())
            ->method('getEntityClass')
            ->with($class)
            ->willReturn($class);

        $entityMetadataProvider = $this->createMock(EntitySecurityMetadataProvider::class);
        $entityMetadataProvider->expects($this->once())
            ->method('isProtectedEntity')
            ->with($class)
            ->willReturn($isProtectedEntity);
        $fieldAclExtension = $this->createMock(FieldAclExtension::class);

        $extension = new EntityAclExtension(
            new ObjectIdAccessor($this->doctrineHelper),
            $entityClassResolver,
            $entityMetadataProvider,
            $this->metadataProvider,
            new EntityOwnerAccessor($this->metadataProvider, $this->inflector),
            $this->decisionMaker,
            $this->permissionManager,
            $this->groupProvider,
            $fieldAclExtension
        );

        $this->assertEquals($expected, $extension->supports($type, $id));
    }

    public function supportsDataProvider(): array
    {
        return [
            [
                'id' => 'action',
                'type' => \stdClass::class,
                'class' => \stdClass::class,
                'isEntity' => false,
                'isProtectedEntity' => false,
                'expected' => false
            ],
            [
                'id' => 'entity',
                'type' => \stdClass::class,
                'class' => \stdClass::class,
                'isEntity' => true,
                'isProtectedEntity' => true,
                'expected' => true
            ],
            [
                'id' => 'entity',
                'type' => '@' . \stdClass::class,
                'class' => \stdClass::class,
                'isEntity' => true,
                'isProtectedEntity' => true,
                'expected' => true
            ],
            [
                'id' => 'entity',
                'type' => 'group@' . \stdClass::class,
                'class' => \stdClass::class,
                'isEntity' => true,
                'isProtectedEntity' => true,
                'expected' => true
            ],
            [
                'id' => 'entity',
                'type' => '@' . \stdClass::class,
                'class' => \stdClass::class,
                'isEntity' => true,
                'isProtectedEntity' => false,
                'expected' => false
            ],
        ];
    }

    /**
     * @dataProvider getObjectIdentityDataProvider
     */
    public function testGetObjectIdentity(mixed $val, ObjectIdentity $expected)
    {
        $this->assertEquals($expected, $this->extension->getObjectIdentity($val));
    }

    public function getObjectIdentityDataProvider(): array
    {
        $annotation = new AclAnnotation([
            'id' => 'test_id',
            'type' => 'entity',
            'permission' => 'VIEW',
            'class' => \stdClass::class
        ]);

        $annotation2 = new AclAnnotation([
            'id' => 'test_id',
            'type' => 'entity',
            'permission' => 'VIEW',
            'class' => \stdClass::class,
            'group_name' => 'group'
        ]);

        $domainObject = new Stub\DomainObjectStub();

        return [
            [
                'val' => 'entity:' . \stdClass::class,
                'expected' => new ObjectIdentity('entity', \stdClass::class)
            ],
            [
                'val' => 'entity:group@' . \stdClass::class,
                'expected' => new ObjectIdentity('entity', 'group@' . \stdClass::class)
            ],
            [
                'val' => 'entity:@' . \stdClass::class,
                'expected' => new ObjectIdentity('entity', \stdClass::class)
            ],
            [
                'val' => $annotation,
                'expected' => new ObjectIdentity('entity', \stdClass::class)
            ],
            [
                'val' => $annotation2,
                'expected' => new ObjectIdentity('entity', 'group@' . \stdClass::class)
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

    private function getPermission(string $id, string $name): Permission
    {
        $permission = new Permission();
        ReflectionUtil::setId($permission, $id);
        $permission->setName($name);

        return $permission;
    }

    private function getPermissionManager(): PermissionManager
    {
        $permissionManager = $this->createMock(PermissionManager::class);
        $permissionManager->expects($this->any())
            ->method('getPermissionsMap')
            ->willReturn([
                'VIEW'   => 1,
                'CREATE' => 2,
                'EDIT'   => 3,
                'DELETE' => 4,
                'ASSIGN' => 5,
                'PERMIT' => 6
            ]);

        return $permissionManager;
    }

    private function buildTree()
    {
        $subordinateBusinessUnits = [
            'bu3'  => ['bu31'],
            'bu3a' => ['bu3a1'],
            'bu41' => ['bu411'],
            'bu4'  => ['bu41', 'bu411'],

        ];

        foreach ($subordinateBusinessUnits as $parentBuId => $buIds) {
            $this->tree->setSubordinateBusinessUnitIds($parentBuId, $buIds);
        }
    }
}
