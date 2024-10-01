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
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class OwnershipConditionDataBuilderTest extends \PHPUnit\Framework\TestCase
{
    private const BUSINESS_UNIT = BusinessUnit::class;
    private const ORGANIZATION = Organization::class;
    private const USER = User::class;
    private const TEST_ENTITY = TestEntity::class;

    private const USER_1 = 101;
    private const USER_2 = 102;
    private const USER_3 = 103;
    private const USER_31 = 1031;
    private const USER_4 = 104;
    private const USER_41 = 1041;
    private const USER_411 = 10411;
    private const USER_411_1 = 1041101;
    private const USER_411_2 = 1041102;
    private const BU_1 = 201;
    private const BU_2 = 202;
    private const BU_3 = 203;
    private const BU_31 = 2031;
    private const BU_3_A = 2030;
    private const BU_3_A_1 = 20301;
    private const BU_4 = 204;
    private const BU_41 = 2041;
    private const BU_411 = 20411;
    private const ORG_1 = 301;
    private const ORG_2 = 302;
    private const ORG_3 = 303;
    private const ORG_4 = 304;

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

    #[\Override]
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
        $this->metadataProvider->getCacheMock()
            ->expects(self::any())
            ->method('get')
            ->willReturn(true);

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

    private function buildTestTree(): void
    {
        /**
         * owners structure:
         * ORG_1  ORG_2     ORG_3         ORG_4
         *                  |             |
         *  BU_1   BU_2     +-BU_3        +-BU_4
         *         |        | |             |
         *         |        | +-BU_31       |
         *         |        | | |           +-BU_41-->--+
         *         |        | | +-USER_31   |           | BU_41 have BU_411 as subordinate BU_siness unit
         *         |        | |             |           V (looped owners between BU_4, BU_411 and BU_41)
         *  USER_1 +-USER_2 | +-USER_3      +-BU_411--<-+
         *                  |               |  |
         *                  +-BU_3_A        |  +-USER_411
         *                    |             |  +-USER_411_1
         *                    +-BU_3_A_1    |  +-USER_411_2
         *                                  +-USER_4
         * user access:
         *                      +--------+--------+--------+---------+--------+----------+------------+------------+
         * users                | USER_1 | USER_2 | USER_3 | USER_31 | USER_4 | USER_411 | USER_411_1 | USER_411_2 |
         *                      +--------+--------+--------+---------+--------+----------+------------+------------+
         * user organizations   | ORG_1  | ORG_2  | ORG_3  | ORG_3   | ORG_4  | ORG_4    | ORG_4      | ORG_4      |
         *                      | ORG_2  |        | ORG_2  |         |        |          |            |            |
         *                      |        |        |        |         |        |          |            |            |
         * user business units  | BU_1   | BU_2   | BU_3   | BU_31   | BU_4   | BU_411   | BU_411     | BU_411     |
         *                      | BU_2   |        | BU_2   |         |        |          |            |            |
         *                      +--------+--------+--------+---------+--------+----------+------------+------------+
         */
        $this->tree->addBusinessUnit(self::BU_1, null);
        $this->tree->addBusinessUnit(self::BU_2, null);
        $this->tree->addBusinessUnit(self::BU_3, self::ORG_3);
        $this->tree->addBusinessUnit(self::BU_31, self::ORG_3);
        $this->tree->addBusinessUnit(self::BU_3_A, self::ORG_3);
        $this->tree->addBusinessUnit(self::BU_3_A_1, self::ORG_3);
        $this->tree->addBusinessUnit(self::BU_4, self::ORG_4);
        $this->tree->addBusinessUnit(self::BU_41, self::ORG_4);
        $this->tree->addBusinessUnit(self::BU_411, self::ORG_4);

        $this->tree->setSubordinateBusinessUnitIds(self::BU_3, [self::BU_31]);
        $this->tree->setSubordinateBusinessUnitIds(self::BU_3_A, [self::BU_3_A_1]);
        $this->tree->setSubordinateBusinessUnitIds(self::BU_41, [self::BU_411]);
        $this->tree->setSubordinateBusinessUnitIds(self::BU_4, [self::BU_41, self::BU_411]);

        $this->tree->addUser(self::USER_1, null);
        $this->tree->addUser(self::USER_2, self::BU_2);
        $this->tree->addUser(self::USER_3, self::BU_3);
        $this->tree->addUser(self::USER_31, self::BU_31);
        $this->tree->addUser(self::USER_4, self::BU_4);
        $this->tree->addUser(self::USER_41, self::BU_41);
        $this->tree->addUser(self::USER_411, self::BU_411);
        $this->tree->addUser(self::USER_411_1, self::BU_411);
        $this->tree->addUser(self::USER_411_2, self::BU_411);

        $this->tree->addUserOrganization(self::USER_1, self::ORG_1);
        $this->tree->addUserOrganization(self::USER_1, self::ORG_2);
        $this->tree->addUserOrganization(self::USER_2, self::ORG_2);
        $this->tree->addUserOrganization(self::USER_3, self::ORG_2);
        $this->tree->addUserOrganization(self::USER_3, self::ORG_3);
        $this->tree->addUserOrganization(self::USER_31, self::ORG_3);
        $this->tree->addUserOrganization(self::USER_4, self::ORG_4);
        $this->tree->addUserOrganization(self::USER_411, self::ORG_4);
        $this->tree->addUserOrganization(self::USER_411_1, self::ORG_4);
        $this->tree->addUserOrganization(self::USER_411_2, self::ORG_4);

        $this->tree->addUserBusinessUnit(self::USER_1, self::ORG_1, self::BU_1);
        $this->tree->addUserBusinessUnit(self::USER_1, self::ORG_2, self::BU_2);
        $this->tree->addUserBusinessUnit(self::USER_2, self::ORG_2, self::BU_2);
        $this->tree->addUserBusinessUnit(self::USER_3, self::ORG_3, self::BU_3);
        $this->tree->addUserBusinessUnit(self::USER_3, self::ORG_2, self::BU_2);
        $this->tree->addUserBusinessUnit(self::USER_31, self::ORG_3, self::BU_31);
        $this->tree->addUserBusinessUnit(self::USER_4, self::ORG_4, self::BU_4);
        $this->tree->addUserBusinessUnit(self::USER_411, self::ORG_4, self::BU_411);
        $this->tree->addUserBusinessUnit(self::USER_411_1, self::ORG_4, self::BU_411);
        $this->tree->addUserBusinessUnit(self::USER_411_2, self::ORG_4, self::BU_411);
    }

    /**
     * @dataProvider buildFilterConstraintProvider
     */
    public function testGetAclConditionData(
        int|null $userId,
        int|null $organizationId,
        bool $isGranted,
        ?int $accessLevel,
        ?string $ownerType,
        string $targetEntityClassName,
        ?array $expectedConstraint,
        string $expectedGroup = ''
    ): void {
        $this->buildTestTree();

        if ($ownerType !== null) {
            $this->metadataProvider->setMetadata(
                self::TEST_ENTITY,
                new OwnershipMetadata($ownerType, 'owner', 'owner_id', 'organization', 'organization_id')
            );
        }

        $this->aclVoter->expects($this->any())
            ->method('addOneShotIsGrantedObserver')
            ->willReturnCallback(function (OneShotIsGrantedObserver $observer) use ($accessLevel) {
                $observer->setAccessLevel($accessLevel);
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

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public static function buildFilterConstraintProvider(): array
    {
        return [
            'for the TEST entity without userId, grant, ownerType; with NONE ACL' => [
                null, null, false, AccessLevel::NONE_LEVEL, null, self::TEST_ENTITY, []
            ],
            'for the stdClass entity without userId, grant, ownerType; with NONE ACL' => [
                null, null, false, AccessLevel::NONE_LEVEL, null,
                \stdClass::class, []
            ],
            'for the stdClass entity without userId, ownerType; with grant and NONE ACL' => [
                null, null, true, AccessLevel::NONE_LEVEL, null,
                \stdClass::class, []
            ],
            'for the TEST entity without ownerType; with SYSTEM ACL, userId, grant' => [
                self::USER_4, null, true, AccessLevel::SYSTEM_LEVEL, null, self::TEST_ENTITY, []
            ],
            'for the TEST entity with SYSTEM ACL, userId, grant and ORGANIZATION ownerType' => [
                self::USER_4, null, true, AccessLevel::SYSTEM_LEVEL, 'ORGANIZATION', self::TEST_ENTITY, []
            ],
            'for the TEST entity with SYSTEM ACL, userId, grant and BUSINESS_UNIT ownerType' => [
                self::USER_4, null, true, AccessLevel::SYSTEM_LEVEL, 'BUSINESS_UNIT', self::TEST_ENTITY, []
            ],
            'for the TEST entity with SYSTEM ACL, userId, grant and USER ownerType' => [
                self::USER_4, null, true, AccessLevel::SYSTEM_LEVEL, 'USER', self::TEST_ENTITY, []
            ],
            'for the TEST entity without ownerType; with GLOBAL ACL, userId, grant' => [
                self::USER_4, null, true, AccessLevel::GLOBAL_LEVEL, null, self::TEST_ENTITY, []
            ],
            'for the TEST entity with GLOBAL ACL, userId, grant and ORGANIZATION ownerType' => [
                self::USER_4,
                self::ORG_4,
                true,
                AccessLevel::GLOBAL_LEVEL,
                'ORGANIZATION',
                self::TEST_ENTITY,
                [
                    null,
                    null,
                    'organization',
                    self::ORG_4,
                    true
                ]
            ],
            'for the TEST entity with GLOBAL ACL, userId, grant and BUSINESS_UNIT ownerType' => [
                self::USER_4,
                self::ORG_4,
                true,
                AccessLevel::GLOBAL_LEVEL,
                'BUSINESS_UNIT',
                self::TEST_ENTITY,
                [null, null, 'organization', self::ORG_4, true]
            ],
            'for the TEST entity with GLOBAL ACL, userId, grant and USER ownerType' => [
                self::USER_4,
                self::ORG_4,
                true,
                AccessLevel::GLOBAL_LEVEL,
                'USER',
                self::TEST_ENTITY,
                [null, null, 'organization', self::ORG_4, true]
            ],
            'for the ORGANIZATION entity without ownerType; with GLOBAL ACL, userId, grant' => [
                self::USER_4,
                self::ORG_4,
                true,
                AccessLevel::GLOBAL_LEVEL,
                null,
                self::ORGANIZATION,
                [
                    'id',
                    [self::ORG_4],
                    null,
                    null,
                    false
                ]
            ],
            'for the TEST entity without ownerType; with DEEP ACL, userId, grant' => [
                self::USER_4, null, true, AccessLevel::DEEP_LEVEL, null, self::TEST_ENTITY, []
            ],
            'for the TEST entity with DEEP ACL, userId, grant and ORGANIZATION ownerType' => [
                self::USER_4, null, true, AccessLevel::DEEP_LEVEL, 'ORGANIZATION', self::TEST_ENTITY, null
            ],
            'for the TEST entity with DEEP ACL, userId, grant and BUSINESS_UNIT ownerType' => [
                self::USER_4,
                self::ORG_4,
                true,
                AccessLevel::DEEP_LEVEL,
                'BUSINESS_UNIT',
                self::TEST_ENTITY,
                [
                    'owner',
                    [self::BU_4, self::BU_41, self::BU_411],
                    'organization',
                    self::ORG_4,
                    false
                ]
            ],
            'for the TEST entity with DEEP ACL, userId, grant and USER ownerType' => [
                self::USER_4,
                self::ORG_4,
                true,
                AccessLevel::DEEP_LEVEL,
                'USER',
                self::TEST_ENTITY,
                [
                    'owner',
                    [self::USER_4, self::USER_411, self::USER_411_1, self::USER_411_2],
                    'organization',
                    self::ORG_4,
                    false
                ]
            ],
            'for the TEST entity with LOCAL ACL for user411_1 user, grant and USER ownerType' => [
                self::USER_411_1,
                self::ORG_4,
                true,
                AccessLevel::LOCAL_LEVEL,
                'USER',
                self::TEST_ENTITY,
                [
                    'owner',
                    [0 => self::USER_411_1, 1 => self::USER_411, 2 => self::USER_411_2],
                    'organization',
                    self::ORG_4,
                    false
                ]
            ],
            'for the TEST entity with DEEP ACL for user411_1 user, grant and USER ownerType' => [
                self::USER_411_1,
                self::ORG_4,
                true,
                AccessLevel::DEEP_LEVEL,
                'USER',
                self::TEST_ENTITY,
                [
                    'owner',
                    [0 => self::USER_411_1, 1 => self::USER_411, 2 => self::USER_411_2],
                    'organization',
                    self::ORG_4,
                    false
                ]
            ],
            'for the BUSINESS entity without ownerType; with DEEP ACL, userId, grant' => [
                self::USER_4,
                self::ORG_4,
                true,
                AccessLevel::DEEP_LEVEL,
                null,
                self::BUSINESS_UNIT,
                [
                    'id',
                    [self::BU_4, self::BU_41, self::BU_411],
                    null,
                    null,
                    false
                ]
            ],
            'for the TEST entity without ownerType; with LOCAL ACL, userId, grant' => [
                self::USER_4, null, true, AccessLevel::LOCAL_LEVEL, null, self::TEST_ENTITY, []
            ],
            'for the TEST entity with LOCAL ACL, userId, grant and ORGANIZATION ownerType' => [
                self::USER_4, null, true, AccessLevel::LOCAL_LEVEL, 'ORGANIZATION', self::TEST_ENTITY, null
            ],
            'for the TEST entity with LOCAL ACL, userId, grant and BUSINESS_UNIT ownerType' => [
                self::USER_4,
                self::ORG_4,
                true,
                AccessLevel::LOCAL_LEVEL,
                'BUSINESS_UNIT',
                self::TEST_ENTITY,
                [
                    'owner',
                    [self::BU_4],
                    'organization',
                    self::ORG_4,
                    false
                ]
            ],
            'for the TEST entity with LOCAL ACL, userId, grant and USER ownerType' => [
                self::USER_4,
                self::ORG_4,
                true,
                AccessLevel::LOCAL_LEVEL,
                'USER',
                self::TEST_ENTITY,
                [
                    'owner',
                    [self::USER_4],
                    'organization',
                    self::ORG_4,
                    false
                ]
            ],
            'for the BUSINESS entity without ownerType; with LOCAL ACL, userId, grant' => [
                self::USER_4,
                self::ORG_4,
                true,
                AccessLevel::LOCAL_LEVEL,
                null,
                self::BUSINESS_UNIT,
                [
                    'id',
                    [self::BU_4],
                    null,
                    null,
                    false
                ]
            ],
            'for the TEST entity without ownerType; with BASIC ACL, userId, grant' => [
                self::USER_4, null, true, AccessLevel::BASIC_LEVEL, null, self::TEST_ENTITY, []
            ],
            'for the TEST entity with BASIC ACL, userId, grant and ORGANIZATION ownerType' => [
                self::USER_4, null, true, AccessLevel::BASIC_LEVEL, 'ORGANIZATION', self::TEST_ENTITY, null
            ],
            'for the TEST entity with BASIC ACL, userId, grant and BUSINESS_UNIT ownerType' => [
                self::USER_4, null, true, AccessLevel::BASIC_LEVEL, 'BUSINESS_UNIT', self::TEST_ENTITY, null
            ],
            'for the TEST entity with BASIC ACL, userId, grant and USER ownerType' => [
                self::USER_4,
                self::ORG_4,
                true,
                AccessLevel::BASIC_LEVEL,
                'USER',
                self::TEST_ENTITY,
                [
                    'owner',
                    self::USER_4,
                    'organization',
                    self::ORG_4,
                    false
                ]
            ],
            'for the USER entity without ownerType; with BASIC ACL, userId, grant' => [
                self::USER_4,
                self::ORG_4,
                true,
                AccessLevel::BASIC_LEVEL,
                null,
                self::USER,
                [
                    'id',
                    self::USER_4,
                    null,
                    null,
                    false
                ]
            ],
            'TEST entity with BASIC ACL, user1, grant and BUSINESS_UNIT ownerType' => [
                self::USER_1, null, true, AccessLevel::BASIC_LEVEL, 'BUSINESS_UNIT', self::TEST_ENTITY, null
            ],
            'TEST entity with LOCAL ACL, user1, grant and BUSINESS_UNIT ownerType' => [
                self::USER_1, null, true, AccessLevel::LOCAL_LEVEL, 'BUSINESS_UNIT', self::TEST_ENTITY, [
                    'owner',
                    [self::BU_1, self::BU_2],
                    null,
                    null,
                    false
                ]
            ],
            'BUSINESS entity with LOCAL ACL, user1, grant, BUSINESS_UNIT ownerType' => [
                self::USER_1,
                self::ORG_1, true, AccessLevel::LOCAL_LEVEL, 'BUSINESS_UNIT', self::BUSINESS_UNIT, [
                    'id',
                    [self::BU_1],
                    null,
                    null,
                    false
                ]
            ],
            'USER entity with LOCAL ACL, user1, grant, BUSINESS_UNIT ownerType' => [
                self::USER_1, null, true, AccessLevel::LOCAL_LEVEL, 'BUSINESS_UNIT', self::USER, [
                    'owner',
                    [self::BU_1, self::BU_2],
                    null,
                    null,
                    false
                ]
            ],
            'TEST entity with DEEP ACL, user1, grant and BUSINESS_UNIT ownerType' => [
                self::USER_1,
                self::ORG_2, true, AccessLevel::DEEP_LEVEL, 'BUSINESS_UNIT', self::TEST_ENTITY, [
                    'owner',
                    [self::BU_2],
                    'organization',
                    self::ORG_2,
                    false
                ]
            ],
            'TEST entity with GLOBAL ACL, user1, grant and BUSINESS_UNIT ownerType' => [
                self::USER_1, null, true, AccessLevel::GLOBAL_LEVEL, 'BUSINESS_UNIT', self::TEST_ENTITY, null
            ],
            'TEST entity with GLOBAL ACL, user1, grant and BUSINESS_UNIT ownerType WITH custom group' => [
                self::USER_1,
                null,
                true,
                AccessLevel::GLOBAL_LEVEL,
                'BUSINESS_UNIT',
                self::TEST_ENTITY,
                null,
                'custom_group'
            ],
            'access denied' => [
                self::USER_4,
                self::ORG_4,
                false,
                null,
                null,
                self::TEST_ENTITY,
                [null, null, null, null, false]
            ],
        ];
    }
}
