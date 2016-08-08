<?php

namespace Oro\Bundle\SecurityBundle\Tests\Unit\Acl\Extension;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Security\Acl\Domain\ObjectIdentity;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\SecurityBundle\Acl\AccessLevel;
use Oro\Bundle\SecurityBundle\Acl\Domain\ObjectIdAccessor;
use Oro\Bundle\SecurityBundle\Acl\Domain\ObjectIdentityFactory;
use Oro\Bundle\SecurityBundle\Acl\Extension\FieldAclExtension;
use Oro\Bundle\SecurityBundle\Acl\Extension\FieldMaskBuilder;
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

class FieldAclExtensionTest extends \PHPUnit_Framework_TestCase
{
    /** @var FieldAclExtension */
    protected $extension;

    /** @var EntitySecurityMetadataProvider|\PHPUnit_Framework_MockObject_MockObject */
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

    /** @var \PHPUnit_Framework_MockObject_MockObject|DoctrineHelper */
    protected $doctrineHelper;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $config;

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
        $this->decisionMaker = new EntityOwnershipDecisionMaker(
            $treeProviderMock,
            new ObjectIdAccessor($this->doctrineHelper),
            new EntityOwnerAccessor($this->metadataProvider),
            $this->metadataProvider
        );
        $this->decisionMaker->setContainer($container);

        $configProvider = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider')
            ->disableOriginalConstructor()
            ->getMock();

        $this->config = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Config\Config')
            ->disableOriginalConstructor()
            ->getMock();

        $configProvider->expects($this->any())
            ->method('getConfig')
            ->willReturn($this->config);

        $this->extension = TestHelper::get($this)->createFieldAclExtension(
            $this->metadataProvider,
            $this->tree,
            new ObjectIdAccessor($this->doctrineHelper),
            $this->decisionMaker,
            $configProvider
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
        $this->config->expects($this->any())
            ->method('get')
            ->willReturnMap(
                [
                    ['field_acl_supported', false, null, true],
                    ['field_acl_enabled', false, null, true]
                ]
            );
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
    
    public function testAdaptRootMask()
    {
        $this->assertEquals(132, $this->extension->adaptRootMask(132, new \stdClass()));
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
            [FieldMaskBuilder::MASK_VIEW_SYSTEM | FieldMaskBuilder::MASK_VIEW_GLOBAL],
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
            [FieldMaskBuilder::MASK_VIEW_SYSTEM | FieldMaskBuilder::MASK_VIEW_GLOBAL],
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
            [FieldMaskBuilder::MASK_VIEW_SYSTEM | FieldMaskBuilder::MASK_VIEW_GLOBAL],
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
            [FieldMaskBuilder::MASK_VIEW_SYSTEM | FieldMaskBuilder::MASK_VIEW_GLOBAL],
            [FieldMaskBuilder::MASK_VIEW_GLOBAL | FieldMaskBuilder::MASK_VIEW_DEEP],
        ];
    }
}
