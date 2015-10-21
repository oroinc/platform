<?php

namespace Oro\Bundle\SecurityBundle\Tests\Unit\Acl\Extension;

use Symfony\Component\Security\Core\Util\ClassUtils;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Security\Acl\Domain\ObjectIdentity;

use Oro\Bundle\EntityBundle\ORM\EntityClassResolver;

use Oro\Bundle\SecurityBundle\Acl\AccessLevel;
use Oro\Bundle\SecurityBundle\Acl\Domain\ObjectIdAccessor;
use Oro\Bundle\SecurityBundle\Acl\Domain\ObjectIdentityFactory;
use Oro\Bundle\SecurityBundle\Acl\Extension\EntityAclExtension;
use Oro\Bundle\SecurityBundle\Acl\Extension\EntityMaskBuilder;
use Oro\Bundle\SecurityBundle\Annotation\Acl as AclAnnotation;
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
 */
class EntityAclExtensionTest extends \PHPUnit_Framework_TestCase
{
    /** @var EntityAclExtension */
    private $extension;

    /** @var OwnershipMetadataProviderStub */
    private $metadataProvider;

    /** @var OwnerTree */
    private $tree;

    /** @var EntityOwnershipDecisionMaker */
    private $decisionMaker;

    /** @var Organization */
    private $org1;
    /** @var Organization */
    private $org2;
    /** @var Organization */
    private $org3;
    /** @var Organization */
    private $org4;

    /** @var BusinessUnit */
    private $bu1;
    /** @var BusinessUnit */
    private $bu2;
    /** @var BusinessUnit */
    private $bu3;
    /** @var BusinessUnit */
    private $bu31;
    /** @var BusinessUnit */
    private $bu4;
    /** @var BusinessUnit */
    private $bu41;
    /** @var BusinessUnit */
    private $bu411;

    /** @var User */
    private $user1;
    /** @var User */
    private $user2;
    /** @var User */
    private $user3;
    /** @var User */
    private $user31;
    /** @var User */
    private $user4;
    /** @var User */
    private $user411;

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
            new OwnershipMetadata('BUSINESS_UNIT', 'owner', 'owner_id')
        );
        $this->metadataProvider->setMetadata(
            $this->metadataProvider->getUserClass(),
            new OwnershipMetadata('BUSINESS_UNIT', 'owner', 'owner_id')
        );

        /** @var \PHPUnit_Framework_MockObject_MockObject|OwnerTreeProvider $treeProviderMock */
        $treeProviderMock = $this->getMockBuilder('Oro\Bundle\SecurityBundle\Owner\OwnerTreeProvider')
            ->disableOriginalConstructor()
            ->getMock();

        $treeProviderMock->expects($this->any())
            ->method('getTree')
            ->will($this->returnValue($this->tree));

        $configProvider = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider')
            ->disableOriginalConstructor()
            ->getMock();
        $configProvider->expects($this->any())
            ->method('hasConfig')
            ->willReturn(false);

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
                            new ObjectIdAccessor(),
                        ],
                        [
                            'oro_security.owner.entity_owner_accessor',
                            ContainerInterface::EXCEPTION_ON_INVALID_REFERENCE,
                            new EntityOwnerAccessor($this->metadataProvider),
                        ],
                        [
                            'oro_entity_config.provider.security',
                            ContainerInterface::EXCEPTION_ON_INVALID_REFERENCE,
                            $configProvider
                        ],
                    ]
                )
            );
        $entityOwnerAccessor = new EntityOwnerAccessor($this->metadataProvider);
        $this->decisionMaker = new EntityOwnershipDecisionMaker(
            $treeProviderMock,
            new ObjectIdAccessor(),
            $entityOwnerAccessor, //new EntityOwnerAccessor($this->metadataProvider),
            $this->metadataProvider
        );
        $this->decisionMaker->setContainer($container);

        $this->extension = TestHelper::get($this)->createEntityAclExtension(
            $this->metadataProvider,
            $this->tree,
            new ObjectIdAccessor(),
            $this->decisionMaker
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
     * @dataProvider validateMaskForUserOwnedProvider
     */
    public function testValidateMaskForRoot($mask)
    {
        $this->extension->validateMask($mask, new ObjectIdentity('entity', ObjectIdentityFactory::ROOT_IDENTITY_TYPE));
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
            array('VIEW', 'CREATE', 'EDIT', 'DELETE', 'ASSIGN', 'SHARE'),
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
        $this->assertEquals(new EntityMaskBuilder(), $this->extension->getMaskBuilder('VIEW'));
        $this->assertEquals(new EntityMaskBuilder(), $this->extension->getMaskBuilder('CREATE'));
        $this->assertEquals(new EntityMaskBuilder(), $this->extension->getMaskBuilder('EDIT'));
        $this->assertEquals(new EntityMaskBuilder(), $this->extension->getMaskBuilder('DELETE'));
        $this->assertEquals(new EntityMaskBuilder(), $this->extension->getMaskBuilder('ASSIGN'));
        $this->assertEquals(new EntityMaskBuilder(), $this->extension->getMaskBuilder('SHARE'));
    }

    public function testGetAllMaskBuilders()
    {
        $this->assertEquals(array(new EntityMaskBuilder()), $this->extension->getAllMaskBuilders());
    }

    /**
     * @dataProvider adaptRootMaskProvider
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

    public function testGetAccessLevel()
    {
        $this->assertEquals(
            AccessLevel::NONE_LEVEL,
            $this->extension->getAccessLevel(EntityMaskBuilder::GROUP_NONE)
        );
        $this->assertEquals(
            AccessLevel::SYSTEM_LEVEL,
            $this->extension->getAccessLevel(EntityMaskBuilder::MASK_VIEW_SYSTEM)
        );
        $this->assertEquals(
            AccessLevel::GLOBAL_LEVEL,
            $this->extension->getAccessLevel(EntityMaskBuilder::MASK_VIEW_GLOBAL)
        );
        $this->assertEquals(
            AccessLevel::DEEP_LEVEL,
            $this->extension->getAccessLevel(EntityMaskBuilder::MASK_VIEW_DEEP)
        );
        $this->assertEquals(
            AccessLevel::LOCAL_LEVEL,
            $this->extension->getAccessLevel(EntityMaskBuilder::MASK_VIEW_LOCAL)
        );
        $this->assertEquals(
            AccessLevel::BASIC_LEVEL,
            $this->extension->getAccessLevel(EntityMaskBuilder::MASK_VIEW_BASIC)
        );
        $this->assertEquals(
            AccessLevel::SYSTEM_LEVEL,
            $this->extension->getAccessLevel(
                EntityMaskBuilder::MASK_VIEW_SYSTEM | EntityMaskBuilder::MASK_EDIT_BASIC,
                'VIEW'
            )
        );
        $this->assertEquals(
            AccessLevel::BASIC_LEVEL,
            $this->extension->getAccessLevel(
                EntityMaskBuilder::MASK_VIEW_SYSTEM | EntityMaskBuilder::MASK_EDIT_BASIC,
                'EDIT'
            )
        );
        $this->assertEquals(
            AccessLevel::NONE_LEVEL,
            $this->extension->getAccessLevel(
                EntityMaskBuilder::MASK_VIEW_SYSTEM | EntityMaskBuilder::MASK_EDIT_BASIC,
                'CREATE'
            )
        );
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

        return array(
            array(EntityMaskBuilder::MASK_VIEW_SYSTEM, null, $this->org4, null, true),
            array(EntityMaskBuilder::MASK_VIEW_GLOBAL, null, $this->org4, null, true),
            array(EntityMaskBuilder::MASK_VIEW_DEEP, null, $this->org4, null, true),
            array(EntityMaskBuilder::MASK_VIEW_LOCAL, null, $this->org4, null, true),
            array(EntityMaskBuilder::MASK_VIEW_BASIC, null, $this->org4, null, true),
            array(EntityMaskBuilder::MASK_VIEW_SYSTEM, null, $this->org4, 'foo', true),
            array(EntityMaskBuilder::MASK_VIEW_GLOBAL, null, $this->org4, 'foo', true),
            array(EntityMaskBuilder::MASK_VIEW_DEEP, null, $this->org4, 'foo', true),
            array(EntityMaskBuilder::MASK_VIEW_LOCAL, null, $this->org4, 'foo', true),
            array(EntityMaskBuilder::MASK_VIEW_BASIC, null, $this->org4, 'foo', true),
            array(EntityMaskBuilder::MASK_VIEW_SYSTEM, null, $this->org4, new ObjectIdentity('test', 'foo'), true),
            array(EntityMaskBuilder::MASK_VIEW_GLOBAL, null, $this->org4, new ObjectIdentity('test', 'foo'), true),
            array(EntityMaskBuilder::MASK_VIEW_DEEP, null, $this->org4, new ObjectIdentity('test', 'foo'), true),
            array(EntityMaskBuilder::MASK_VIEW_LOCAL, null, $this->org4, new ObjectIdentity('test', 'foo'), true),
            array(EntityMaskBuilder::MASK_VIEW_BASIC, null, $this->org4, new ObjectIdentity('test', 'foo'), true),
            array(EntityMaskBuilder::MASK_VIEW_SYSTEM, null, $this->org4, new TestEntity(1), true),
            array(EntityMaskBuilder::MASK_VIEW_GLOBAL, null, $this->org4, new TestEntity(1), true),
            array(EntityMaskBuilder::MASK_VIEW_DEEP, null, $this->org4, new TestEntity(1), true),
            array(EntityMaskBuilder::MASK_VIEW_LOCAL, null, $this->org4, new TestEntity(1), true),
            array(EntityMaskBuilder::MASK_VIEW_BASIC, null, $this->org4, new TestEntity(1), true),
            array(
                EntityMaskBuilder::MASK_VIEW_GLOBAL,
                $this->user3,
                $this->org4,
                new TestEntity(1, $this->org3),
                false
            ),
            array(EntityMaskBuilder::MASK_VIEW_GLOBAL, $this->user4, $this->org4, new TestEntity(1, $this->org4), true),
            array(
                EntityMaskBuilder::MASK_VIEW_GLOBAL,
                $this->user3,
                $this->org4,
                new TestEntity(1, $this->bu3, $this->org3),
                false
            ),
            array(
                EntityMaskBuilder::MASK_VIEW_GLOBAL,
                $this->user4,
                $this->org4,
                new TestEntity(1, $this->bu4, $this->org4),
                true
            ),
            array(
                EntityMaskBuilder::MASK_VIEW_GLOBAL,
                $this->user4,
                $this->org4,
                new TestEntity(1, $this->bu411, $this->org4),
                true
            ),
            array(
                EntityMaskBuilder::MASK_VIEW_DEEP,
                $this->user3,
                $this->org4,
                new TestEntity(1, $this->bu3, $this->org3),
                false
            ),
            array(
                EntityMaskBuilder::MASK_VIEW_DEEP,
                $this->user4,
                $this->org4,
                new TestEntity(1, $this->bu4, $this->org4),
                true
            ),
            array(
                EntityMaskBuilder::MASK_VIEW_DEEP,
                $this->user4,
                $this->org4,
                new TestEntity(1, $this->bu411, $this->org4),
                true
            ),
            array(
                EntityMaskBuilder::MASK_VIEW_LOCAL,
                $this->user3,
                $this->org4,
                new TestEntity(1, $this->bu3, $this->org3),
                false
            ),
            array(
                EntityMaskBuilder::MASK_VIEW_LOCAL,
                $this->user4,
                $this->org4,
                new TestEntity(1, $this->bu4, $this->org4),
                true
            ),
            array(
                EntityMaskBuilder::MASK_VIEW_LOCAL,
                $this->user4,
                $this->org4,
                new TestEntity(1, $this->bu411, $this->org4),
                false
            ),
            array(
                EntityMaskBuilder::MASK_VIEW_GLOBAL,
                $this->user3,
                $this->org4,
                new TestEntity(1, $this->user3, $this->org3),
                false
            ),
            array(
                EntityMaskBuilder::MASK_VIEW_GLOBAL,
                $this->user4,
                $this->org4,
                new TestEntity(1, $this->user4, $this->org4),
                true
            ),
            array(
                EntityMaskBuilder::MASK_VIEW_GLOBAL,
                $this->user4,
                $this->org4,
                new TestEntity(1, $this->user411, $this->org4),
                true
            ),
            array(
                EntityMaskBuilder::MASK_VIEW_GLOBAL,
                $this->user4,
                $this->org4,
                new TestEntity(1, $this->user3, $this->org3),
                false
            ),
            array(
                EntityMaskBuilder::MASK_VIEW_DEEP,
                $this->user3,
                $this->org4,
                new TestEntity(1, $this->user3, $this->org3),
                false
            ),
            array(
                EntityMaskBuilder::MASK_VIEW_DEEP,
                $this->user4,
                $this->org4,
                new TestEntity(1, $this->user4, $this->org4),
                true
            ),
            array(
                EntityMaskBuilder::MASK_VIEW_DEEP,
                $this->user4,
                $this->org4,
                new TestEntity(1, $this->user411, $this->org4),
                true
            ),
            array(
                EntityMaskBuilder::MASK_VIEW_DEEP,
                $this->user4,
                $this->org4,
                new TestEntity(1, $this->user3, $this->org4),
                false
            ),
            array(
                EntityMaskBuilder::MASK_VIEW_LOCAL,
                $this->user3,
                $this->org4,
                new TestEntity(1, $this->user3, $this->org3),
                false
            ),
            array(
                EntityMaskBuilder::MASK_VIEW_LOCAL,
                $this->user4,
                $this->org4,
                new TestEntity(1, $this->user4, $this->org4),
                true
            ),
            array(
                EntityMaskBuilder::MASK_VIEW_LOCAL,
                $this->user4,
                $this->org4,
                new TestEntity(1, $this->user411, $this->org4),
                false
            ),
            array(
                EntityMaskBuilder::MASK_VIEW_LOCAL,
                $this->user4,
                $this->org4,
                new TestEntity(1, $this->user3, $this->org3),
                false
            ),
            array(
                EntityMaskBuilder::MASK_VIEW_BASIC,
                $this->user3,
                $this->org4,
                new TestEntity(1, $this->user3, $this->org3),
                false
            ),
            array(
                EntityMaskBuilder::MASK_VIEW_BASIC,
                $this->user4,
                $this->org4,
                new TestEntity(1, $this->user4, $this->org4),
                true
            ),
            array(
                EntityMaskBuilder::MASK_VIEW_BASIC,
                $this->user4,
                $this->org4,
                new TestEntity(1, $this->user411, $this->org4),
                false
            ),
            array(
                EntityMaskBuilder::MASK_VIEW_BASIC,
                $this->user4,
                $this->org4,
                new TestEntity(1, $this->user3, $this->org3),
                false
            ),
        );
    }

    public static function adaptRootMaskProvider()
    {
        return array(
            array(
                new TestEntity(),
                null,
                EntityMaskBuilder::MASK_VIEW_SYSTEM | EntityMaskBuilder::MASK_CREATE_SYSTEM,
                EntityMaskBuilder::MASK_VIEW_SYSTEM | EntityMaskBuilder::MASK_CREATE_SYSTEM
            ),
            array(
                new TestEntity(),
                null,
                EntityMaskBuilder::MASK_VIEW_BASIC | EntityMaskBuilder::MASK_CREATE_LOCAL,
                EntityMaskBuilder::MASK_VIEW_SYSTEM | EntityMaskBuilder::MASK_CREATE_SYSTEM
            ),
            array(
                new TestEntity(),
                null,
                EntityMaskBuilder::MASK_ASSIGN_SYSTEM | EntityMaskBuilder::MASK_SHARE_BASIC,
                EntityMaskBuilder::GROUP_NONE
            ),
            array(
                new Organization(),
                null,
                EntityMaskBuilder::MASK_VIEW_BASIC | EntityMaskBuilder::MASK_CREATE_LOCAL,
                EntityMaskBuilder::MASK_VIEW_SYSTEM | EntityMaskBuilder::MASK_CREATE_SYSTEM
            ),
            array(
                new BusinessUnit(),
                null,
                EntityMaskBuilder::MASK_VIEW_BASIC | EntityMaskBuilder::MASK_CREATE_LOCAL,
                EntityMaskBuilder::MASK_VIEW_LOCAL | EntityMaskBuilder::MASK_CREATE_LOCAL
            ),
            array(
                new BusinessUnit(),
                null,
                EntityMaskBuilder::MASK_VIEW_DEEP | EntityMaskBuilder::MASK_CREATE_LOCAL,
                EntityMaskBuilder::MASK_VIEW_DEEP | EntityMaskBuilder::MASK_CREATE_LOCAL
            ),
            array(
                new User(),
                null,
                EntityMaskBuilder::MASK_VIEW_BASIC | EntityMaskBuilder::MASK_CREATE_LOCAL,
                EntityMaskBuilder::MASK_VIEW_LOCAL | EntityMaskBuilder::MASK_CREATE_LOCAL
            ),
            array(
                new TestEntity(),
                'ORGANIZATION',
                EntityMaskBuilder::MASK_VIEW_SYSTEM | EntityMaskBuilder::MASK_CREATE_DEEP,
                EntityMaskBuilder::MASK_VIEW_SYSTEM | EntityMaskBuilder::MASK_CREATE_GLOBAL
            ),
            array(
                new TestEntity(),
                'BUSINESS_UNIT',
                EntityMaskBuilder::MASK_VIEW_DEEP | EntityMaskBuilder::MASK_CREATE_BASIC,
                EntityMaskBuilder::MASK_VIEW_DEEP | EntityMaskBuilder::MASK_CREATE_LOCAL
            ),
            array(
                new TestEntity(),
                'USER',
                EntityMaskBuilder::MASK_VIEW_GLOBAL | EntityMaskBuilder::MASK_CREATE_BASIC,
                EntityMaskBuilder::MASK_VIEW_GLOBAL | EntityMaskBuilder::MASK_CREATE_BASIC
            ),
        );
    }

    public static function validateMaskForOrganizationProvider()
    {
        return array(
            array(EntityMaskBuilder::MASK_VIEW_SYSTEM),
            array(EntityMaskBuilder::MASK_CREATE_SYSTEM),
            array(EntityMaskBuilder::MASK_EDIT_SYSTEM),
            array(EntityMaskBuilder::MASK_DELETE_SYSTEM),
            array(EntityMaskBuilder::MASK_VIEW_SYSTEM | EntityMaskBuilder::MASK_DELETE_SYSTEM),
        );
    }

    public static function validateMaskForOrganizationInvalidProvider()
    {
        return array(
            array(EntityMaskBuilder::MASK_ASSIGN_SYSTEM),
            array(EntityMaskBuilder::MASK_SHARE_SYSTEM),
            array(EntityMaskBuilder::MASK_VIEW_GLOBAL),
            array(EntityMaskBuilder::MASK_VIEW_DEEP),
            array(EntityMaskBuilder::MASK_VIEW_LOCAL),
            array(EntityMaskBuilder::MASK_VIEW_BASIC),
        );
    }

    public static function validateMaskForBusinessUnitProvider()
    {
        return array(
            array(EntityMaskBuilder::MASK_VIEW_SYSTEM),
            array(EntityMaskBuilder::MASK_CREATE_SYSTEM),
            array(EntityMaskBuilder::MASK_EDIT_SYSTEM),
            array(EntityMaskBuilder::MASK_DELETE_SYSTEM),
            array(EntityMaskBuilder::MASK_ASSIGN_SYSTEM),
            array(EntityMaskBuilder::MASK_SHARE_SYSTEM),
            array(EntityMaskBuilder::MASK_VIEW_GLOBAL),
            array(EntityMaskBuilder::MASK_CREATE_GLOBAL),
            array(EntityMaskBuilder::MASK_EDIT_GLOBAL),
            array(EntityMaskBuilder::MASK_DELETE_GLOBAL),
            array(EntityMaskBuilder::MASK_ASSIGN_GLOBAL),
            array(EntityMaskBuilder::MASK_SHARE_GLOBAL),
            array(EntityMaskBuilder::MASK_VIEW_DEEP),
            array(EntityMaskBuilder::MASK_CREATE_DEEP),
            array(EntityMaskBuilder::MASK_EDIT_DEEP),
            array(EntityMaskBuilder::MASK_DELETE_DEEP),
            array(EntityMaskBuilder::MASK_ASSIGN_DEEP),
            array(EntityMaskBuilder::MASK_SHARE_DEEP),
            array(EntityMaskBuilder::MASK_VIEW_LOCAL),
            array(EntityMaskBuilder::MASK_CREATE_LOCAL),
            array(EntityMaskBuilder::MASK_EDIT_LOCAL),
            array(EntityMaskBuilder::MASK_DELETE_LOCAL),
            array(EntityMaskBuilder::MASK_ASSIGN_LOCAL),
            array(EntityMaskBuilder::MASK_SHARE_LOCAL),
            array(
                EntityMaskBuilder::MASK_VIEW_SYSTEM
                | EntityMaskBuilder::MASK_CREATE_GLOBAL
                | EntityMaskBuilder::MASK_EDIT_DEEP
                | EntityMaskBuilder::MASK_DELETE_LOCAL
            ),
        );
    }

    public static function validateMaskForBusinessUnitInvalidProvider()
    {
        return array(
            array(EntityMaskBuilder::MASK_VIEW_BASIC),
            array(EntityMaskBuilder::MASK_VIEW_SYSTEM | EntityMaskBuilder::MASK_VIEW_GLOBAL),
            array(EntityMaskBuilder::MASK_VIEW_GLOBAL | EntityMaskBuilder::MASK_VIEW_DEEP),
            array(EntityMaskBuilder::MASK_VIEW_DEEP | EntityMaskBuilder::MASK_VIEW_LOCAL),
        );
    }

    public static function validateMaskForUserProvider()
    {
        return array(
            array(EntityMaskBuilder::MASK_VIEW_SYSTEM),
            array(EntityMaskBuilder::MASK_CREATE_SYSTEM),
            array(EntityMaskBuilder::MASK_EDIT_SYSTEM),
            array(EntityMaskBuilder::MASK_DELETE_SYSTEM),
            array(EntityMaskBuilder::MASK_ASSIGN_SYSTEM),
            array(EntityMaskBuilder::MASK_SHARE_SYSTEM),
            array(EntityMaskBuilder::MASK_VIEW_GLOBAL),
            array(EntityMaskBuilder::MASK_CREATE_GLOBAL),
            array(EntityMaskBuilder::MASK_EDIT_GLOBAL),
            array(EntityMaskBuilder::MASK_DELETE_GLOBAL),
            array(EntityMaskBuilder::MASK_ASSIGN_GLOBAL),
            array(EntityMaskBuilder::MASK_SHARE_GLOBAL),
            array(EntityMaskBuilder::MASK_VIEW_DEEP),
            array(EntityMaskBuilder::MASK_CREATE_DEEP),
            array(EntityMaskBuilder::MASK_EDIT_DEEP),
            array(EntityMaskBuilder::MASK_DELETE_DEEP),
            array(EntityMaskBuilder::MASK_ASSIGN_DEEP),
            array(EntityMaskBuilder::MASK_SHARE_DEEP),
            array(EntityMaskBuilder::MASK_VIEW_LOCAL),
            array(EntityMaskBuilder::MASK_CREATE_LOCAL),
            array(EntityMaskBuilder::MASK_EDIT_LOCAL),
            array(EntityMaskBuilder::MASK_DELETE_LOCAL),
            array(EntityMaskBuilder::MASK_ASSIGN_LOCAL),
            array(EntityMaskBuilder::MASK_SHARE_LOCAL),
            array(
                EntityMaskBuilder::MASK_VIEW_SYSTEM
                | EntityMaskBuilder::MASK_CREATE_GLOBAL
                | EntityMaskBuilder::MASK_EDIT_DEEP
                | EntityMaskBuilder::MASK_DELETE_LOCAL
            ),
        );
    }

    public static function validateMaskForUserInvalidProvider()
    {
        return array(
            array(EntityMaskBuilder::MASK_VIEW_BASIC),
            array(EntityMaskBuilder::MASK_VIEW_SYSTEM | EntityMaskBuilder::MASK_VIEW_GLOBAL),
            array(EntityMaskBuilder::MASK_VIEW_GLOBAL | EntityMaskBuilder::MASK_VIEW_DEEP),
            array(EntityMaskBuilder::MASK_VIEW_DEEP | EntityMaskBuilder::MASK_VIEW_LOCAL),
        );
    }

    public static function validateMaskForUserOwnedProvider()
    {
        return array(
            array(EntityMaskBuilder::MASK_VIEW_SYSTEM),
            array(EntityMaskBuilder::MASK_CREATE_SYSTEM),
            array(EntityMaskBuilder::MASK_EDIT_SYSTEM),
            array(EntityMaskBuilder::MASK_DELETE_SYSTEM),
            array(EntityMaskBuilder::MASK_ASSIGN_SYSTEM),
            array(EntityMaskBuilder::MASK_SHARE_SYSTEM),
            array(EntityMaskBuilder::MASK_VIEW_GLOBAL),
            array(EntityMaskBuilder::MASK_CREATE_GLOBAL),
            array(EntityMaskBuilder::MASK_EDIT_GLOBAL),
            array(EntityMaskBuilder::MASK_DELETE_GLOBAL),
            array(EntityMaskBuilder::MASK_ASSIGN_GLOBAL),
            array(EntityMaskBuilder::MASK_SHARE_GLOBAL),
            array(EntityMaskBuilder::MASK_VIEW_DEEP),
            array(EntityMaskBuilder::MASK_CREATE_DEEP),
            array(EntityMaskBuilder::MASK_EDIT_DEEP),
            array(EntityMaskBuilder::MASK_DELETE_DEEP),
            array(EntityMaskBuilder::MASK_ASSIGN_DEEP),
            array(EntityMaskBuilder::MASK_SHARE_DEEP),
            array(EntityMaskBuilder::MASK_VIEW_LOCAL),
            array(EntityMaskBuilder::MASK_CREATE_LOCAL),
            array(EntityMaskBuilder::MASK_EDIT_LOCAL),
            array(EntityMaskBuilder::MASK_DELETE_LOCAL),
            array(EntityMaskBuilder::MASK_ASSIGN_LOCAL),
            array(EntityMaskBuilder::MASK_SHARE_LOCAL),
            array(EntityMaskBuilder::MASK_VIEW_BASIC),
            array(EntityMaskBuilder::MASK_CREATE_BASIC),
            array(EntityMaskBuilder::MASK_EDIT_BASIC),
            array(EntityMaskBuilder::MASK_DELETE_BASIC),
            array(EntityMaskBuilder::MASK_ASSIGN_BASIC),
            array(EntityMaskBuilder::MASK_SHARE_BASIC),
            array(
                EntityMaskBuilder::MASK_VIEW_SYSTEM
                | EntityMaskBuilder::MASK_CREATE_GLOBAL
                | EntityMaskBuilder::MASK_EDIT_DEEP
                | EntityMaskBuilder::MASK_DELETE_LOCAL
                | EntityMaskBuilder::MASK_ASSIGN_BASIC
            ),
        );
    }

    public static function validateMaskForUserOwnedInvalidProvider()
    {
        return array(
            array(EntityMaskBuilder::MASK_VIEW_SYSTEM | EntityMaskBuilder::MASK_VIEW_GLOBAL),
            array(EntityMaskBuilder::MASK_VIEW_GLOBAL | EntityMaskBuilder::MASK_VIEW_DEEP),
            array(EntityMaskBuilder::MASK_VIEW_DEEP | EntityMaskBuilder::MASK_VIEW_LOCAL),
            array(EntityMaskBuilder::MASK_VIEW_LOCAL | EntityMaskBuilder::MASK_VIEW_BASIC),
        );
    }

    public static function validateMaskForOrganizationOwnedProvider()
    {
        return array(
            array(EntityMaskBuilder::MASK_VIEW_SYSTEM),
            array(EntityMaskBuilder::MASK_CREATE_SYSTEM),
            array(EntityMaskBuilder::MASK_EDIT_SYSTEM),
            array(EntityMaskBuilder::MASK_DELETE_SYSTEM),
            array(EntityMaskBuilder::MASK_ASSIGN_SYSTEM),
            array(EntityMaskBuilder::MASK_SHARE_SYSTEM),
            array(EntityMaskBuilder::MASK_VIEW_GLOBAL),
            array(EntityMaskBuilder::MASK_CREATE_GLOBAL),
            array(EntityMaskBuilder::MASK_EDIT_GLOBAL),
            array(EntityMaskBuilder::MASK_DELETE_GLOBAL),
            array(EntityMaskBuilder::MASK_ASSIGN_GLOBAL),
            array(EntityMaskBuilder::MASK_SHARE_GLOBAL),
            array(EntityMaskBuilder::MASK_VIEW_SYSTEM | EntityMaskBuilder::MASK_CREATE_GLOBAL),
        );
    }

    public static function validateMaskForOrganizationOwnedInvalidProvider()
    {
        return array(
            array(EntityMaskBuilder::MASK_VIEW_DEEP),
            array(EntityMaskBuilder::MASK_VIEW_LOCAL),
            array(EntityMaskBuilder::MASK_VIEW_BASIC),
            array(EntityMaskBuilder::MASK_VIEW_SYSTEM | EntityMaskBuilder::MASK_VIEW_GLOBAL),
            array(EntityMaskBuilder::MASK_VIEW_GLOBAL | EntityMaskBuilder::MASK_VIEW_DEEP),
        );
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
            new ObjectIdAccessor(),
            $entityClassResolverMock,
            $entityMetadataProvider,
            $this->metadataProvider,
            $this->decisionMaker
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
}
