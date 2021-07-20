<?php

namespace Oro\Bundle\SecurityBundle\Tests\Unit\ORM\Walker;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\SecurityBundle\Acl\AccessLevel;
use Oro\Bundle\SecurityBundle\Acl\Domain\ObjectIdAccessor;
use Oro\Bundle\SecurityBundle\Acl\Domain\OneShotIsGrantedObserver;
use Oro\Bundle\SecurityBundle\Acl\Group\AclGroupProviderInterface;
use Oro\Bundle\SecurityBundle\Acl\Voter\AclVoter;
use Oro\Bundle\SecurityBundle\Authentication\Token\UsernamePasswordOrganizationToken;
use Oro\Bundle\SecurityBundle\Metadata\EntitySecurityMetadataProvider;
use Oro\Bundle\SecurityBundle\ORM\Walker\OwnershipConditionDataBuilder;
use Oro\Bundle\SecurityBundle\Owner\Metadata\OwnershipMetadata;
use Oro\Bundle\SecurityBundle\Owner\OwnerTree;
use Oro\Bundle\SecurityBundle\Owner\OwnerTreeProvider;
use Oro\Bundle\SecurityBundle\Tests\Unit\Acl\Domain\Fixtures\Entity\BusinessUnit;
use Oro\Bundle\SecurityBundle\Tests\Unit\Acl\Domain\Fixtures\Entity\Organization;
use Oro\Bundle\SecurityBundle\Tests\Unit\Acl\Domain\Fixtures\Entity\TestEntity;
use Oro\Bundle\SecurityBundle\Tests\Unit\Acl\Domain\Fixtures\Entity\User;
use Oro\Bundle\SecurityBundle\Tests\Unit\Stub\OwnershipMetadataProviderStub;
use Symfony\Component\Security\Acl\Domain\ObjectIdentity;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class OwnershipConditionDataBuilderTest extends \PHPUnit\Framework\TestCase
{
    private const BUSINESS_UNIT = BusinessUnit::class;
    private const ORGANIZATION  = Organization::class;
    private const USER          = User::class;
    private const TEST_ENTITY   = TestEntity::class;

    /** @var OwnershipConditionDataBuilder */
    private $builder;

    /** @var OwnershipMetadataProviderStub */
    private $metadataProvider;

    /** @var AuthorizationCheckerInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $authorizationChecker;

    /** @var TokenStorageInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $tokenStorage;

    /** @var AclVoter|\PHPUnit\Framework\MockObject\MockObject */
    private $aclVoter;

    /** @var OwnerTree */
    private $tree;

    protected function setUp(): void
    {
        $this->tree = new OwnerTree();

        $treeProvider = $this->createMock(OwnerTreeProvider::class);
        $treeProvider->expects($this->any())
            ->method('getTree')
            ->willReturn($this->tree);

        $entityMetadataProvider = $this->createMock(EntitySecurityMetadataProvider::class);
        $entityMetadataProvider->expects($this->any())
            ->method('isProtectedEntity')
            ->willReturn(true);

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

        $this->authorizationChecker = $this->createMock(AuthorizationCheckerInterface::class);
        $this->tokenStorage = $this->createMock(TokenStorageInterface::class);
        $this->aclVoter = $this->createMock(AclVoter::class);
        $doctrineHelper = $this->createMock(DoctrineHelper::class);

        $this->builder = new OwnershipConditionDataBuilder(
            $this->authorizationChecker,
            $this->tokenStorage,
            new ObjectIdAccessor($doctrineHelper),
            $entityMetadataProvider,
            $this->metadataProvider,
            $treeProvider,
            $this->aclVoter
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

        $this->buildTree();

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
    }

    /**
     * @dataProvider buildFilterConstraintProvider
     */
    public function testGetAclConditionData(
        $userId,
        $organizationId,
        $isGranted,
        $accessLevel,
        $ownerType,
        $targetEntityClassName,
        $expectedConstraint,
        $expectedGroup = ''
    ) {
        $this->buildTestTree();

        if ($ownerType !== null) {
            $this->metadataProvider->setMetadata(
                self::TEST_ENTITY,
                new OwnershipMetadata($ownerType, 'owner', 'owner_id', 'organization', 'organization_id')
            );
        }

        /** @var OneShotIsGrantedObserver $aclObserver */
        $aclObserver = null;
        $this->aclVoter->expects($this->any())
            ->method('addOneShotIsGrantedObserver')
            ->willReturnCallback(function ($observer) use (&$aclObserver, &$accessLevel) {
                $aclObserver = $observer;
                /** @var OneShotIsGrantedObserver $aclObserver */
                $aclObserver->setAccessLevel($accessLevel);
            });

        $user = new User($userId);
        $organization = new Organization($organizationId);
        $user->addOrganization($organization);
        $token = $this->createMock(UsernamePasswordOrganizationToken::class);

        $token->expects($this->any())
            ->method('getUser')
            ->willReturn($user);
        $token->expects($this->any())
            ->method('getOrganization')
            ->willReturn($organization);

        $aclGroupProvider = $this->createMock(AclGroupProviderInterface::class);
        $aclGroupProvider->expects($this->any())
            ->method('getGroup')
            ->willReturn($expectedGroup);

        $this->builder->setAclGroupProvider($aclGroupProvider);

        $this->authorizationChecker->expects($this->any())
            ->method('isGranted')
            ->with(
                $this->equalTo('VIEW'),
                $this->callback(
                    function (ObjectIdentity $identity) use ($targetEntityClassName, $expectedGroup) {
                        $this->assertEquals('entity', $identity->getIdentifier());
                        $this->assertStringEndsWith($targetEntityClassName, $identity->getType());
                        if ($expectedGroup) {
                            $this->assertStringStartsWith($expectedGroup, $identity->getType());
                        }

                        return true;
                    }
                )
            )
            ->willReturn($isGranted);
        $this->tokenStorage->expects($this->any())
            ->method('getToken')
            ->willReturn($userId ? $token : null);

        $result = $this->builder->getAclConditionData($targetEntityClassName);
        $this->assertEquals($expectedConstraint, $result);
    }

    public function testGetUserIdWithNonLoginUser()
    {
        $token = $this->createMock(TokenInterface::class);
        $token->expects($this->any())
            ->method('getUser')
            ->willReturn('anon');
        $this->tokenStorage->expects($this->any())
            ->method('getToken')
            ->willReturn($token);
        $this->assertNull($this->builder->getUserId());
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public static function buildFilterConstraintProvider()
    {
        return [
            'for the TEST entity without userId, grant, ownerType; with NONE ACL' => [
                '', '', false, AccessLevel::NONE_LEVEL, null, self::TEST_ENTITY, []
            ],
            'for the stdClass entity without userId, grant, ownerType; with NONE ACL' => [
                '', '', false, AccessLevel::NONE_LEVEL, null,
                \stdClass::class, []
            ],
            'for the stdClass entity without userId, ownerType; with grant and NONE ACL' => [
                '', '', true, AccessLevel::NONE_LEVEL, null,
                \stdClass::class, []
            ],
            'for the TEST entity without ownerType; with SYSTEM ACL, userId, grant' => [
                'user4', '', true, AccessLevel::SYSTEM_LEVEL, null, self::TEST_ENTITY, []
            ],
            'for the TEST entity with SYSTEM ACL, userId, grant and ORGANIZATION ownerType' => [
                'user4', '', true, AccessLevel::SYSTEM_LEVEL, 'ORGANIZATION', self::TEST_ENTITY, []
            ],
            'for the TEST entity with SYSTEM ACL, userId, grant and BUSINESS_UNIT ownerType' => [
                'user4', '', true, AccessLevel::SYSTEM_LEVEL, 'BUSINESS_UNIT', self::TEST_ENTITY, []
            ],
            'for the TEST entity with SYSTEM ACL, userId, grant and USER ownerType' => [
                'user4', '', true, AccessLevel::SYSTEM_LEVEL, 'USER', self::TEST_ENTITY, []
            ],
            'for the TEST entity without ownerType; with GLOBAL ACL, userId, grant' => [
                'user4', '', true, AccessLevel::GLOBAL_LEVEL, null, self::TEST_ENTITY, []
            ],
            'for the TEST entity with GLOBAL ACL, userId, grant and ORGANIZATION ownerType' => [
                'user4',
                'org4',
                true,
                AccessLevel::GLOBAL_LEVEL,
                'ORGANIZATION',
                self::TEST_ENTITY,
                [
                    null,
                    null,
                    'organization',
                    'org4',
                    true
                ]
            ],
            'for the TEST entity with GLOBAL ACL, userId, grant and BUSINESS_UNIT ownerType' => [
                'user4',
                'org4',
                true,
                AccessLevel::GLOBAL_LEVEL,
                'BUSINESS_UNIT',
                self::TEST_ENTITY,
                [null, null, 'organization', 'org4', true]
            ],
            'for the TEST entity with GLOBAL ACL, userId, grant and USER ownerType' => [
                'user4',
                'org4',
                true,
                AccessLevel::GLOBAL_LEVEL,
                'USER',
                self::TEST_ENTITY,
                [null, null, 'organization', 'org4', true]
            ],
            'for the ORGANIZATION entity without ownerType; with GLOBAL ACL, userId, grant' => [
                'user4',
                'org4',
                true,
                AccessLevel::GLOBAL_LEVEL,
                null,
                self::ORGANIZATION,
                [
                    'id',
                    ['org4'],
                    null,
                    null,
                    false
                ]
            ],
            'for the TEST entity without ownerType; with DEEP ACL, userId, grant' => [
                'user4', '', true, AccessLevel::DEEP_LEVEL, null, self::TEST_ENTITY, []
            ],
            'for the TEST entity with DEEP ACL, userId, grant and ORGANIZATION ownerType' => [
                'user4', '', true, AccessLevel::DEEP_LEVEL, 'ORGANIZATION', self::TEST_ENTITY, null
            ],
            'for the TEST entity with DEEP ACL, userId, grant and BUSINESS_UNIT ownerType' => [
                'user4',
                'org4',
                true,
                AccessLevel::DEEP_LEVEL,
                'BUSINESS_UNIT',
                self::TEST_ENTITY,
                [
                    'owner',
                    ['bu4', 'bu41', 'bu411'],
                    'organization',
                    'org4',
                    false
                ]
            ],
            'for the TEST entity with DEEP ACL, userId, grant and USER ownerType' => [
                'user4',
                'org4',
                true,
                AccessLevel::DEEP_LEVEL,
                'USER',
                self::TEST_ENTITY,
                [
                    'owner',
                    ['user4', 'user411'],
                    'organization',
                    'org4',
                    false
                ]
            ],
            'for the BUSINESS entity without ownerType; with DEEP ACL, userId, grant' => [
                'user4',
                'org4',
                true,
                AccessLevel::DEEP_LEVEL,
                null,
                self::BUSINESS_UNIT,
                [
                    'id',
                    ['bu4', 'bu41', 'bu411'],
                    null,
                    null,
                    false
                ]
            ],
            'for the TEST entity without ownerType; with LOCAL ACL, userId, grant' => [
                'user4', '', true, AccessLevel::LOCAL_LEVEL, null, self::TEST_ENTITY, []
            ],
            'for the TEST entity with LOCAL ACL, userId, grant and ORGANIZATION ownerType' => [
                'user4', '', true, AccessLevel::LOCAL_LEVEL, 'ORGANIZATION', self::TEST_ENTITY, null
            ],
            'for the TEST entity with LOCAL ACL, userId, grant and BUSINESS_UNIT ownerType' => [
                'user4',
                'org4',
                true,
                AccessLevel::LOCAL_LEVEL,
                'BUSINESS_UNIT',
                self::TEST_ENTITY,
                [
                    'owner',
                    ['bu4'],
                    'organization',
                    'org4',
                    false
                ]
            ],
            'for the TEST entity with LOCAL ACL, userId, grant and USER ownerType' => [
                'user4',
                'org4',
                true,
                AccessLevel::LOCAL_LEVEL,
                'USER',
                self::TEST_ENTITY,
                [
                    'owner',
                    ['user4'],
                    'organization',
                    'org4',
                    false
                ]
            ],
            'for the BUSINESS entity without ownerType; with LOCAL ACL, userId, grant' => [
                'user4',
                'org4',
                true,
                AccessLevel::LOCAL_LEVEL,
                null,
                self::BUSINESS_UNIT,
                [
                    'id',
                    ['bu4'],
                    null,
                    null,
                    false
                ]
            ],
            'for the TEST entity without ownerType; with BASIC ACL, userId, grant' => [
                'user4', '', true, AccessLevel::BASIC_LEVEL, null, self::TEST_ENTITY, []
            ],
            'for the TEST entity with BASIC ACL, userId, grant and ORGANIZATION ownerType' => [
                'user4', '', true, AccessLevel::BASIC_LEVEL, 'ORGANIZATION', self::TEST_ENTITY, null
            ],
            'for the TEST entity with BASIC ACL, userId, grant and BUSINESS_UNIT ownerType' => [
                'user4', '', true, AccessLevel::BASIC_LEVEL, 'BUSINESS_UNIT', self::TEST_ENTITY, null
            ],
            'for the TEST entity with BASIC ACL, userId, grant and USER ownerType' => [
                'user4',
                'org4',
                true,
                AccessLevel::BASIC_LEVEL,
                'USER',
                self::TEST_ENTITY,
                [
                    'owner',
                    'user4',
                    'organization',
                    'org4',
                    false
                ]
            ],
            'for the USER entity without ownerType; with BASIC ACL, userId, grant' => [
                'user4',
                'org4',
                true,
                AccessLevel::BASIC_LEVEL,
                null,
                self::USER,
                [
                    'id',
                    'user4',
                    null,
                    null,
                    false
                ]
            ],
            'TEST entity with BASIC ACL, user1, grant and BUSINESS_UNIT ownerType' => [
                'user1', '', true, AccessLevel::BASIC_LEVEL, 'BUSINESS_UNIT', self::TEST_ENTITY, null
            ],
            'TEST entity with LOCAL ACL, user1, grant and BUSINESS_UNIT ownerType' => [
                'user1', '', true, AccessLevel::LOCAL_LEVEL, 'BUSINESS_UNIT', self::TEST_ENTITY, [
                    'owner',
                    ['bu1', 'bu2'],
                    null,
                    null,
                    false
                ]
            ],
            'BUSINESS entity with LOCAL ACL, user1, grant, BUSINESS_UNIT ownerType' => [
                'user1', 'org1', true, AccessLevel::LOCAL_LEVEL, 'BUSINESS_UNIT', self::BUSINESS_UNIT, [
                    'id',
                    ['bu1'],
                    null,
                    null,
                    false
                ]
            ],
            'USER entity with LOCAL ACL, user1, grant, BUSINESS_UNIT ownerType' => [
                'user1', '', true, AccessLevel::LOCAL_LEVEL, 'BUSINESS_UNIT', self::USER, [
                    'owner',
                    ['bu1', 'bu2'],
                    null,
                    null,
                    false
                ]
            ],
            'TEST entity with DEEP ACL, user1, grant and BUSINESS_UNIT ownerType' => [
                'user1', 'org2', true, AccessLevel::DEEP_LEVEL, 'BUSINESS_UNIT', self::TEST_ENTITY, [
                    'owner',
                    ['bu2'],
                    'organization',
                    'org2',
                    false
                ]
            ],
            'TEST entity with GLOBAL ACL, user1, grant and BUSINESS_UNIT ownerType' => [
                'user1', '', true, AccessLevel::GLOBAL_LEVEL, 'BUSINESS_UNIT', self::TEST_ENTITY, null
            ],
            'TEST entity with GLOBAL ACL, user1, grant and BUSINESS_UNIT ownerType WITH custom group' => [
                'user1', '', true, AccessLevel::GLOBAL_LEVEL, 'BUSINESS_UNIT', self::TEST_ENTITY, null, 'custom_group'
            ],
            'access denied' => [
                'user4',
                'org4',
                false,
                null,
                null,
                self::TEST_ENTITY,
                [null, null, null, null, false]
            ],
        ];
    }

    public function testGetUserInCaseOfDifferentUsersInToken()
    {
        $user1 = new User(1);
        $user2 = new User(2);

        $this->tokenStorage->expects($this->exactly(2))
            ->method('getToken')
            ->willReturnOnConsecutiveCalls(
                new UsernamePasswordToken($user1, null, 'main', []),
                new UsernamePasswordToken($user2, null, 'main', [])
            );

        $user = $this->builder->getUser();
        $this->assertSame($user1, $user);

        // at the second call should be the second user in token
        $user = $this->builder->getUser();
        $this->assertSame($user2, $user);
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
