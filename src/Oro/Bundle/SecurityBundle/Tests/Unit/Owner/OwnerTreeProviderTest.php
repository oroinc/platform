<?php

namespace Oro\Bundle\SecurityBundle\Tests\Unit\Owner;

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\Cache\CacheProvider;
use Doctrine\DBAL\Platforms\MySqlPlatform;
use Doctrine\ORM\Mapping\Driver\AnnotationDriver;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\EntityBundle\Tools\DatabaseChecker;
use Oro\Bundle\SecurityBundle\Owner\Metadata\OwnershipMetadataProviderInterface;
use Oro\Bundle\SecurityBundle\Owner\OwnerTree;
use Oro\Bundle\SecurityBundle\Owner\OwnerTreeProvider;
use Oro\Bundle\SecurityBundle\Test\OwnerTreeWrappingPropertiesAccessor;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Component\Testing\ReflectionUtil;
use Oro\Component\TestUtils\ORM\Mocks\ConnectionMock;
use Oro\Component\TestUtils\ORM\Mocks\DriverMock;
use Oro\Component\TestUtils\ORM\Mocks\EntityManagerMock;
use Oro\Component\TestUtils\ORM\OrmTestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

/**
 * @SuppressWarnings(PHPMD)
 */
class OwnerTreeProviderTest extends OrmTestCase
{
    private const ENTITY_NAMESPACE = 'Oro\Bundle\SecurityBundle\Tests\Unit\Owner\Fixtures\Entity';

    private const ORG_1 = 1;
    private const ORG_2 = 2;

    private const MAIN_BU_1 = 10;
    private const MAIN_BU_2 = 20;
    private const BU_1 = 30;
    private const BU_1_1 = 40;
    private const BU_2 = 50;
    private const BU_2_1 = 60;
    private const BU_2_2 = 70;

    private const USER_1 = 100;
    private const USER_2 = 200;
    private const USER_3 = 300;
    private const USER_4 = 400;

    /** @var EntityManagerMock */
    private $em;

    /** @var DatabaseChecker|\PHPUnit\Framework\MockObject\MockObject */
    private $databaseChecker;

    /** @var CacheProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $cache;

    /** @var OwnershipMetadataProviderInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $ownershipMetadataProvider;

    /** @var TokenStorageInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $tokenStorage;

    /** @var LoggerInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $logger;

    /** @var OwnerTreeProvider */
    private $treeProvider;

    protected function setUp(): void
    {
        $conn = new ConnectionMock([], new DriverMock());
        $conn->setDatabasePlatform(new MySqlPlatform());
        $this->em = $this->getTestEntityManager($conn);
        $this->em->getConfiguration()->setMetadataDriverImpl(new AnnotationDriver(
            new AnnotationReader(),
            self::ENTITY_NAMESPACE
        ));
        $this->em->getConfiguration()->setEntityNamespaces([
            'Test' => self::ENTITY_NAMESPACE
        ]);

        $doctrine = $this->createMock(ManagerRegistry::class);
        $doctrine->expects($this->any())
            ->method('getManagerForClass')
            ->willReturn($this->em);

        $this->databaseChecker = $this->createMock(DatabaseChecker::class);

        $this->cache = $this->createMock(CacheProvider::class);
        $this->cache->expects($this->any())
            ->method('fetch')
            ->willReturn(false);

        $this->ownershipMetadataProvider = $this->createMock(OwnershipMetadataProviderInterface::class);
        $this->ownershipMetadataProvider->expects($this->any())
            ->method('getUserClass')
            ->willReturn(self::ENTITY_NAMESPACE . '\TestUser');
        $this->ownershipMetadataProvider->expects($this->any())
            ->method('getBusinessUnitClass')
            ->willReturn(self::ENTITY_NAMESPACE . '\TestBusinessUnit');

        $this->tokenStorage = $this->createMock(TokenStorageInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->treeProvider = new OwnerTreeProvider(
            $doctrine,
            $this->databaseChecker,
            $this->cache,
            $this->ownershipMetadataProvider,
            $this->tokenStorage
        );
        $this->treeProvider->setLogger($this->logger);
    }

    public function testSupportsForSupportedUser()
    {
        $token = $this->createMock(TokenInterface::class);
        $this->tokenStorage->expects(self::once())
            ->method('getToken')
            ->willReturn($token);
        $token->expects(self::once())
            ->method('getUser')
            ->willReturn(new User());

        $this->assertTrue($this->treeProvider->supports());
    }

    public function testSupportsForNotSupportedUser()
    {
        $token = $this->createMock(TokenInterface::class);
        $this->tokenStorage->expects(self::once())
            ->method('getToken')
            ->willReturn($token);
        $token->expects(self::once())
            ->method('getUser')
            ->willReturn(new \stdClass());

        $this->assertFalse($this->treeProvider->supports());
    }

    public function testSupportsWhenNoSecurityToken()
    {
        $this->tokenStorage->expects(self::once())
            ->method('getToken')
            ->willReturn(null);

        $this->assertFalse($this->treeProvider->supports());
    }

    private function addGetBusinessUnitsExpectation(array $businessUnits)
    {
        $queryResult = [];
        foreach ($businessUnits as $item) {
            $queryResult[] = [
                'id_0'   => $item['buId'],
                'sclr_1' => $item['orgId'],
                'sclr_2' => $item['parentBuId'],
            ];
        }
        $this->addQueryExpectation(
            'SELECT t0_.id AS id_0, t0_.organization_id AS sclr_1, t0_.parent_id AS sclr_2,'
            . ' (CASE WHEN t0_.parent_id IS NULL THEN 0 ELSE 1 END) AS sclr_3'
            . ' FROM tbl_business_unit t0_'
            . ' ORDER BY sclr_3 ASC, sclr_2 ASC',
            $queryResult
        );
    }

    private function addGetUsersExpectation(array $users)
    {
        $queryResult = [];
        foreach ($users as $item) {
            $queryResult[] = [
                'id_0'   => $item['userId'], // user id
                'sclr_2' => $item['owningBuId'], // bu id (owner for a user)
                'id_1'   => $item['orgId'], // org id (from user->organizations)
                'id_3'   => $item['buId'], // bu id (from user->businessUnits)
            ];
        }
        $this->addQueryExpectation(
            'SELECT t0_.id AS id_0, t1_.id AS id_1, t0_.owner_id AS sclr_2, t2_.id AS id_3'
            . ' FROM tbl_user t0_'
            . ' INNER JOIN tbl_user_to_organization t3_ ON t0_.id = t3_.user_id'
            . ' INNER JOIN tbl_organization t1_ ON t1_.id = t3_.organization_id'
            . ' LEFT JOIN tbl_user_to_business_unit t4_ ON t0_.id = t4_.user_id'
            . ' LEFT JOIN tbl_business_unit t2_ ON t2_.id = t4_.business_unit_id'
            . ' ORDER BY id_1 ASC',
            $queryResult
        );
    }

    private function assertOwnerTreeEquals(array $expected, OwnerTree $actual)
    {
        $a = new OwnerTreeWrappingPropertiesAccessor($actual);
        self::assertEqualsCanonicalizing(
            $expected['userOwningOrganizationId'],
            $a->xgetUserOwningOrganizationId()
        );
        self::assertEqualsCanonicalizing(
            $expected['userOrganizationIds'],
            $a->xgetUserOrganizationIds()
        );
        self::assertEqualsCanonicalizing(
            $expected['userOwningBusinessUnitId'],
            $a->xgetUserOwningBusinessUnitId()
        );
        self::assertEqualsCanonicalizing(
            $expected['userBusinessUnitIds'],
            $a->xgetUserBusinessUnitIds()
        );
        self::assertEqualsCanonicalizing(
            $expected['userOrganizationBusinessUnitIds'],
            $a->xgetUserOrganizationBusinessUnitIds()
        );
        self::assertEqualsCanonicalizing(
            $expected['businessUnitOwningOrganizationId'],
            $a->xgetBusinessUnitOwningOrganizationId()
        );
        self::assertEqualsCanonicalizing(
            $expected['assignedBusinessUnitUserIds'],
            $a->xgetAssignedBusinessUnitUserIds()
        );
        self::assertEqualsCanonicalizing(
            $expected['subordinateBusinessUnitIds'],
            $a->xgetSubordinateBusinessUnitIds()
        );
        self::assertEqualsCanonicalizing(
            $expected['organizationBusinessUnitIds'],
            $a->xgetOrganizationBusinessUnitIds()
        );
    }

    public function testBusinessUnitsWithoutOrganization()
    {
        $this->databaseChecker->expects(self::once())
            ->method('checkDatabase')
            ->willReturn(true);

        // the business units without parent should be at the top,
        // rest business units are sorted by parent id
        $this->addGetBusinessUnitsExpectation(
            [
                [
                    'orgId'      => self::ORG_1,
                    'parentBuId' => null,
                    'buId'       => self::MAIN_BU_1,
                ],
                [
                    'orgId'      => null,
                    'parentBuId' => self::MAIN_BU_1,
                    'buId'       => self::BU_2,
                ],
                [
                    'orgId'      => self::ORG_1,
                    'parentBuId' => self::MAIN_BU_1,
                    'buId'       => self::BU_1,
                ],
                [
                    'orgId'      => self::ORG_1,
                    'parentBuId' => self::BU_2,
                    'buId'       => self::BU_2_1,
                ],
            ]
        );
        // should be sorted by organization id
        $this->addGetUsersExpectation(
            [
                [
                    'orgId'      => self::ORG_1,
                    'userId'     => self::USER_1,
                    'owningBuId' => self::MAIN_BU_1,
                    'buId'       => self::MAIN_BU_1,
                ],
                [
                    'orgId'      => self::ORG_1,
                    'userId'     => self::USER_1,
                    'owningBuId' => self::MAIN_BU_1,
                    'buId'       => self::BU_1,
                ],
            ]
        );
        $this->applyQueryExpectations($this->getDriverConnectionMock($this->em));

        /** @var OwnerTree $tree */
        $tree = $this->treeProvider->getTree();

        $this->assertOwnerTreeEquals(
            [
                'userOwningOrganizationId'         => [
                    self::USER_1 => self::ORG_1
                ],
                'userOrganizationIds'              => [
                    self::USER_1 => [self::ORG_1]
                ],
                'userOwningBusinessUnitId'         => [
                    self::USER_1 => self::MAIN_BU_1
                ],
                'userBusinessUnitIds'              => [
                    self::USER_1 => [self::MAIN_BU_1, self::BU_1]
                ],
                'userOrganizationBusinessUnitIds'  => [
                    self::USER_1 => [self::ORG_1 => [self::MAIN_BU_1, self::BU_1]]
                ],
                'businessUnitOwningOrganizationId' => [
                    self::MAIN_BU_1 => self::ORG_1,
                    self::BU_1      => self::ORG_1,
                    self::BU_2_1    => self::ORG_1,
                ],
                'assignedBusinessUnitUserIds'      => [
                    self::MAIN_BU_1 => [self::USER_1],
                    self::BU_1      => [self::USER_1],
                ],
                'subordinateBusinessUnitIds'       => [
                    self::MAIN_BU_1 => [self::BU_1],
                ],
                'organizationBusinessUnitIds'      => [
                    self::ORG_1 => [self::MAIN_BU_1, self::BU_1, self::BU_2_1]
                ],
            ],
            $tree
        );
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testBusinessUnitTree()
    {
        $this->databaseChecker->expects(self::once())
            ->method('checkDatabase')
            ->willReturn(true);

        // the business units without parent should be at the top,
        // rest business units are sorted by parent id
        $this->addGetBusinessUnitsExpectation(
            [
                [
                    'orgId'      => self::ORG_1,
                    'parentBuId' => self::MAIN_BU_1,
                    'buId'       => self::BU_2,
                ],
                [
                    'orgId'      => self::ORG_1,
                    'parentBuId' => null,
                    'buId'       => self::MAIN_BU_1,
                ],
                [
                    'orgId'      => self::ORG_1,
                    'parentBuId' => self::MAIN_BU_1,
                    'buId'       => self::BU_1,
                ],
                [
                    'orgId'      => self::ORG_1,
                    'parentBuId' => self::BU_2,
                    'buId'       => self::BU_2_1,
                ],
            ]
        );
        // should be sorted by organization id
        $this->addGetUsersExpectation(
            [
                [
                    'orgId'      => self::ORG_1,
                    'userId'     => self::USER_1,
                    'owningBuId' => self::MAIN_BU_1,
                    'buId'       => self::MAIN_BU_1,
                ],
                [
                    'orgId'      => self::ORG_1,
                    'userId'     => self::USER_1,
                    'owningBuId' => self::MAIN_BU_1,
                    'buId'       => self::BU_1,
                ],
            ]
        );
        $this->applyQueryExpectations($this->getDriverConnectionMock($this->em));

        /** @var OwnerTree $tree */
        $tree = $this->treeProvider->getTree();

        $this->assertOwnerTreeEquals(
            [
                'userOwningOrganizationId'         => [
                    self::USER_1 => self::ORG_1
                ],
                'userOrganizationIds'              => [
                    self::USER_1 => [self::ORG_1]
                ],
                'userOwningBusinessUnitId'         => [
                    self::USER_1 => self::MAIN_BU_1
                ],
                'userBusinessUnitIds'              => [
                    self::USER_1 => [self::MAIN_BU_1, self::BU_1]
                ],
                'userOrganizationBusinessUnitIds'  => [
                    self::USER_1 => [self::ORG_1 => [self::MAIN_BU_1, self::BU_1]]
                ],
                'businessUnitOwningOrganizationId' => [
                    self::MAIN_BU_1 => self::ORG_1,
                    self::BU_1      => self::ORG_1,
                    self::BU_2      => self::ORG_1,
                    self::BU_2_1    => self::ORG_1,
                ],
                'assignedBusinessUnitUserIds'      => [
                    self::MAIN_BU_1 => [self::USER_1],
                    self::BU_1      => [self::USER_1],
                ],
                'subordinateBusinessUnitIds'       => [
                    self::MAIN_BU_1 => [self::BU_2, self::BU_2_1, self::BU_1],
                    self::BU_2      => [self::BU_2_1],
                ],
                'organizationBusinessUnitIds'      => [
                    self::ORG_1 => [self::MAIN_BU_1, self::BU_2, self::BU_1, self::BU_2_1]
                ],
            ],
            $tree
        );
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testBusinessUnitTreeWhenChildBusinessUnitAreLoadedBeforeParentBusinessUnit()
    {
        $this->databaseChecker->expects(self::once())
            ->method('checkDatabase')
            ->willReturn(true);

        // the business units without parent should be at the top,
        // rest business units are sorted by parent id
        $this->addGetBusinessUnitsExpectation(
            [
                [
                    'orgId'      => self::ORG_1,
                    'parentBuId' => null,
                    'buId'       => self::MAIN_BU_1,
                ],
                [
                    'orgId'      => self::ORG_1,
                    'parentBuId' => self::BU_2,
                    'buId'       => self::BU_2_1,
                ],
                [
                    'orgId'      => self::ORG_1,
                    'parentBuId' => self::MAIN_BU_1,
                    'buId'       => self::BU_1,
                ],
                [
                    'orgId'      => self::ORG_1,
                    'parentBuId' => self::MAIN_BU_1,
                    'buId'       => self::BU_2,
                ],
            ]
        );
        // should be sorted by organization id
        $this->addGetUsersExpectation(
            [
                [
                    'orgId'      => self::ORG_1,
                    'userId'     => self::USER_1,
                    'owningBuId' => self::MAIN_BU_1,
                    'buId'       => self::MAIN_BU_1,
                ],
                [
                    'orgId'      => self::ORG_1,
                    'userId'     => self::USER_1,
                    'owningBuId' => self::MAIN_BU_1,
                    'buId'       => self::BU_1,
                ],
            ]
        );
        $this->applyQueryExpectations($this->getDriverConnectionMock($this->em));

        /** @var OwnerTree $tree */
        $tree = $this->treeProvider->getTree();

        $this->assertOwnerTreeEquals(
            [
                'userOwningOrganizationId'         => [
                    self::USER_1 => self::ORG_1
                ],
                'userOrganizationIds'              => [
                    self::USER_1 => [self::ORG_1]
                ],
                'userOwningBusinessUnitId'         => [
                    self::USER_1 => self::MAIN_BU_1
                ],
                'userBusinessUnitIds'              => [
                    self::USER_1 => [self::MAIN_BU_1, self::BU_1]
                ],
                'userOrganizationBusinessUnitIds'  => [
                    self::USER_1 => [self::ORG_1 => [self::MAIN_BU_1, self::BU_1]]
                ],
                'businessUnitOwningOrganizationId' => [
                    self::MAIN_BU_1 => self::ORG_1,
                    self::BU_1      => self::ORG_1,
                    self::BU_2      => self::ORG_1,
                    self::BU_2_1    => self::ORG_1,
                ],
                'assignedBusinessUnitUserIds'      => [
                    self::MAIN_BU_1 => [self::USER_1],
                    self::BU_1      => [self::USER_1],
                ],
                'subordinateBusinessUnitIds'       => [
                    self::MAIN_BU_1 => [self::BU_1, self::BU_2, self::BU_2_1],
                    self::BU_2      => [self::BU_2_1],
                ],
                'organizationBusinessUnitIds'      => [
                    self::ORG_1 => [self::MAIN_BU_1, self::BU_2_1, self::BU_1, self::BU_2]
                ],
            ],
            $tree
        );
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testUserDoesNotHaveAssignedBusinessUnit()
    {
        $this->databaseChecker->expects(self::once())
            ->method('checkDatabase')
            ->willReturn(true);

        // the business units without parent should be at the top,
        // rest business units are sorted by parent id
        $this->addGetBusinessUnitsExpectation(
            [
                [
                    'orgId'      => self::ORG_1,
                    'parentBuId' => null,
                    'buId'       => self::MAIN_BU_1,
                ],
                [
                    'orgId'      => self::ORG_1,
                    'parentBuId' => self::MAIN_BU_1,
                    'buId'       => self::BU_1,
                ],
            ]
        );
        // should be sorted by organization id
        $this->addGetUsersExpectation(
            [
                [
                    'orgId'      => self::ORG_1,
                    'userId'     => self::USER_1,
                    'owningBuId' => self::MAIN_BU_1,
                    'buId'       => null,
                ],
            ]
        );
        $this->applyQueryExpectations($this->getDriverConnectionMock($this->em));

        /** @var OwnerTree $tree */
        $tree = $this->treeProvider->getTree();

        $this->assertOwnerTreeEquals(
            [
                'userOwningOrganizationId'         => [
                    self::USER_1 => self::ORG_1,
                ],
                'userOrganizationIds'              => [
                    self::USER_1 => [self::ORG_1],
                ],
                'userOwningBusinessUnitId'         => [
                    self::USER_1 => self::MAIN_BU_1,
                ],
                'userBusinessUnitIds'              => [],
                'userOrganizationBusinessUnitIds'  => [],
                'businessUnitOwningOrganizationId' => [
                    self::MAIN_BU_1 => self::ORG_1,
                    self::BU_1      => self::ORG_1,
                ],
                'assignedBusinessUnitUserIds'      => [],
                'subordinateBusinessUnitIds'       => [
                    self::MAIN_BU_1 => [self::BU_1],
                ],
                'organizationBusinessUnitIds'      => [
                    self::ORG_1 => [self::MAIN_BU_1, self::BU_1],
                ],
            ],
            $tree
        );
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testSeveralOrganizations()
    {
        $this->databaseChecker->expects(self::once())
            ->method('checkDatabase')
            ->willReturn(true);

        // the business units without parent should be at the top,
        // rest business units are sorted by parent id
        $this->addGetBusinessUnitsExpectation(
            [
                [
                    'orgId'      => self::ORG_1,
                    'parentBuId' => null,
                    'buId'       => self::MAIN_BU_1,
                ],
                [
                    'orgId'      => self::ORG_2,
                    'parentBuId' => null,
                    'buId'       => self::BU_2,
                ],
                [
                    'orgId'      => self::ORG_1,
                    'parentBuId' => self::MAIN_BU_1,
                    'buId'       => self::BU_1,
                ],
                [
                    'orgId'      => self::ORG_2,
                    'parentBuId' => self::BU_2,
                    'buId'       => self::BU_2_1,
                ],
            ]
        );
        // should be sorted by organization id
        $this->addGetUsersExpectation(
            [
                [
                    'orgId'      => self::ORG_1,
                    'userId'     => self::USER_1,
                    'owningBuId' => self::MAIN_BU_1,
                    'buId'       => self::MAIN_BU_1,
                ],
                [
                    'orgId'      => self::ORG_1,
                    'userId'     => self::USER_1,
                    'owningBuId' => self::MAIN_BU_1,
                    'buId'       => self::BU_1,
                ],
                [
                    'orgId'      => self::ORG_2,
                    'userId'     => self::USER_2,
                    'owningBuId' => self::BU_2,
                    'buId'       => self::BU_2_1,
                ],
            ]
        );
        $this->applyQueryExpectations($this->getDriverConnectionMock($this->em));

        /** @var OwnerTree $tree */
        $tree = $this->treeProvider->getTree();

        $this->assertOwnerTreeEquals(
            [
                'userOwningOrganizationId'         => [
                    self::USER_1 => self::ORG_1,
                    self::USER_2 => self::ORG_2,
                ],
                'userOrganizationIds'              => [
                    self::USER_1 => [self::ORG_1],
                    self::USER_2 => [self::ORG_2],
                ],
                'userOwningBusinessUnitId'         => [
                    self::USER_1 => self::MAIN_BU_1,
                    self::USER_2 => self::BU_2,
                ],
                'userBusinessUnitIds'              => [
                    self::USER_1 => [self::MAIN_BU_1, self::BU_1],
                    self::USER_2 => [self::BU_2_1],
                ],
                'userOrganizationBusinessUnitIds'  => [
                    self::USER_1 => [self::ORG_1 => [self::MAIN_BU_1, self::BU_1]],
                    self::USER_2 => [self::ORG_2 => [self::BU_2_1]],
                ],
                'businessUnitOwningOrganizationId' => [
                    self::MAIN_BU_1 => self::ORG_1,
                    self::BU_1      => self::ORG_1,
                    self::BU_2      => self::ORG_2,
                    self::BU_2_1    => self::ORG_2,
                ],
                'assignedBusinessUnitUserIds'      => [
                    self::MAIN_BU_1 => [self::USER_1],
                    self::BU_1      => [self::USER_1],
                    self::BU_2_1    => [self::USER_2],
                ],
                'subordinateBusinessUnitIds'       => [
                    self::MAIN_BU_1 => [self::BU_1],
                    self::BU_2      => [self::BU_2_1],
                ],
                'organizationBusinessUnitIds'      => [
                    self::ORG_1 => [self::MAIN_BU_1, self::BU_1],
                    self::ORG_2 => [self::BU_2, self::BU_2_1],
                ],
            ],
            $tree
        );
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testUsersAssignedToBusinessUnitsFromSeveralOrganizations()
    {
        $this->databaseChecker->expects(self::once())
            ->method('checkDatabase')
            ->willReturn(true);

        // the business units without parent should be at the top,
        // rest business units are sorted by parent id
        $this->addGetBusinessUnitsExpectation(
            [
                [
                    'orgId'      => self::ORG_1,
                    'parentBuId' => null,
                    'buId'       => self::MAIN_BU_1,
                ],
                [
                    'orgId'      => self::ORG_2,
                    'parentBuId' => null,
                    'buId'       => self::MAIN_BU_2,
                ],
                [
                    'orgId'      => self::ORG_1,
                    'parentBuId' => self::MAIN_BU_1,
                    'buId'       => self::BU_1,
                ],
                [
                    'orgId'      => self::ORG_1,
                    'parentBuId' => self::BU_1,
                    'buId'       => self::BU_1_1,
                ],
                [
                    'orgId'      => self::ORG_2,
                    'parentBuId' => self::MAIN_BU_2,
                    'buId'       => self::BU_2,
                ],
                [
                    'orgId'      => self::ORG_2,
                    'parentBuId' => self::BU_2,
                    'buId'       => self::BU_2_1,
                ],
                [
                    'orgId'      => self::ORG_2,
                    'parentBuId' => self::BU_2,
                    'buId'       => self::BU_2_2,
                ],
            ]
        );
        // should be sorted by organization id
        $this->addGetUsersExpectation(
            [
                [
                    'orgId'      => self::ORG_1,
                    'userId'     => self::USER_1,
                    'owningBuId' => self::MAIN_BU_1,
                    'buId'       => self::MAIN_BU_1,
                ],
                [
                    'orgId'      => self::ORG_1,
                    'userId'     => self::USER_1,
                    'owningBuId' => self::MAIN_BU_1,
                    'buId'       => self::BU_1_1,
                ],
                [
                    'orgId'      => self::ORG_2,
                    'userId'     => self::USER_1,
                    'owningBuId' => self::MAIN_BU_1,
                    'buId'       => self::BU_2_1,
                ],
                [
                    'orgId'      => self::ORG_2,
                    'userId'     => self::USER_2,
                    'owningBuId' => self::BU_2,
                    'buId'       => self::BU_2,
                ],
                [
                    'orgId'      => self::ORG_2,
                    'userId'     => self::USER_2,
                    'owningBuId' => self::BU_2,
                    'buId'       => self::BU_2_1,
                ],
                [
                    'orgId'      => self::ORG_2,
                    'userId'     => self::USER_3,
                    'owningBuId' => self::BU_2_2,
                    'buId'       => null,
                ],
                [
                    'orgId'      => self::ORG_2,
                    'userId'     => self::USER_4,
                    'owningBuId' => self::BU_2_2,
                    'buId'       => self::MAIN_BU_1,
                ],
            ]
        );
        $this->applyQueryExpectations($this->getDriverConnectionMock($this->em));

        /** @var OwnerTree $tree */
        $tree = $this->treeProvider->getTree();

        $this->assertOwnerTreeEquals(
            [
                'userOwningOrganizationId'         => [
                    self::USER_1 => self::ORG_1,
                    self::USER_2 => self::ORG_2,
                    self::USER_3 => self::ORG_2,
                    self::USER_4 => self::ORG_2,
                ],
                'userOrganizationIds'              => [
                    self::USER_1 => [self::ORG_1, self::ORG_2],
                    self::USER_2 => [self::ORG_2],
                    self::USER_3 => [self::ORG_2],
                    self::USER_4 => [self::ORG_2],
                ],
                'userOwningBusinessUnitId'         => [
                    self::USER_1 => self::MAIN_BU_1,
                    self::USER_2 => self::BU_2,
                    self::USER_3 => self::BU_2_2,
                    self::USER_4 => self::BU_2_2,
                ],
                'userBusinessUnitIds'              => [
                    self::USER_1 => [self::MAIN_BU_1, self::BU_1_1, self::BU_2_1],
                    self::USER_2 => [self::BU_2, self::BU_2_1],
                    self::USER_4 => [self::MAIN_BU_1],
                ],
                'userOrganizationBusinessUnitIds'  => [
                    self::USER_1 => [
                        self::ORG_1 => [self::MAIN_BU_1, self::BU_1_1],
                        self::ORG_2 => [self::BU_2_1]
                    ],
                    self::USER_2 => [self::ORG_2 => [self::BU_2, self::BU_2_1]],
                    self::USER_4 => [self::ORG_2 => [self::MAIN_BU_1]],
                ],
                'businessUnitOwningOrganizationId' => [
                    self::MAIN_BU_1 => self::ORG_1,
                    self::BU_1      => self::ORG_1,
                    self::BU_1_1    => self::ORG_1,
                    self::MAIN_BU_2 => self::ORG_2,
                    self::BU_2      => self::ORG_2,
                    self::BU_2_1    => self::ORG_2,
                    self::BU_2_2    => self::ORG_2,
                ],
                'assignedBusinessUnitUserIds'      => [
                    self::MAIN_BU_1 => [self::USER_1, self::USER_4],
                    self::BU_1_1    => [self::USER_1],
                    self::BU_2      => [self::USER_2],
                    self::BU_2_1    => [self::USER_1, self::USER_2],
                ],
                'subordinateBusinessUnitIds'       => [
                    self::MAIN_BU_1 => [self::BU_1, self::BU_1_1],
                    self::BU_1      => [self::BU_1_1],
                    self::MAIN_BU_2 => [self::BU_2, self::BU_2_1, self::BU_2_2],
                    self::BU_2      => [self::BU_2_1, self::BU_2_2],
                ],
                'organizationBusinessUnitIds'      => [
                    self::ORG_1 => [self::MAIN_BU_1, self::BU_1, self::BU_1_1],
                    self::ORG_2 => [self::MAIN_BU_2, self::BU_2, self::BU_2_1, self::BU_2_2],
                ],
            ],
            $tree
        );
    }

    /**
     * @dataProvider addBusinessUnitDirectCyclicRelationProvider
     */
    public function testDirectCyclicRelationshipBetweenBusinessUnits(
        array $src,
        array $expected,
        array $criticalMessageArguments
    ) {
        $this->logger->expects($this->once())
            ->method('critical')
            ->with(
                sprintf(
                    'Cyclic relationship in "%s" with problem id "%s"',
                    $criticalMessageArguments['businessUnitClass'],
                    $criticalMessageArguments['buId']
                )
            );

        /** @var OwnerTree $tree */
        $tree = $this->treeProvider->getTree();
        $businessUnitClass = $this->ownershipMetadataProvider->getBusinessUnitClass();
        $subordinateBusinessUnitIds = ReflectionUtil::callMethod(
            $this->treeProvider,
            'buildTree',
            [$src, $businessUnitClass]
        );

        foreach ($subordinateBusinessUnitIds as $parentBusinessUnit => $businessUnits) {
            $tree->setSubordinateBusinessUnitIds($parentBusinessUnit, $businessUnits);
        }

        foreach ($expected as $parentBusinessUnit => $businessUnits) {
            $this->assertEquals($businessUnits, $tree->getSubordinateBusinessUnitIds($parentBusinessUnit));
        }
    }

    /**
     * @dataProvider addBusinessUnitNotDirectCyclicRelationProvider
     */
    public function testNotDirectCyclicRelationshipBetweenBusinessUnits(
        array $src,
        array $expected,
        array $criticalMessageArguments
    ) {
        $this->logger->expects($this->exactly(count($criticalMessageArguments)))
            ->method('critical')
            ->withConsecutive(
                [sprintf(
                    'Cyclic relationship in "%s" with problem id "%s"',
                    $criticalMessageArguments[0]['businessUnitClass'],
                    $criticalMessageArguments[0]['buId']
                )],
                [sprintf(
                    'Cyclic relationship in "%s" with problem id "%s"',
                    $criticalMessageArguments[1]['businessUnitClass'],
                    $criticalMessageArguments[1]['buId']
                )],
                [sprintf(
                    'Cyclic relationship in "%s" with problem id "%s"',
                    $criticalMessageArguments[2]['businessUnitClass'],
                    $criticalMessageArguments[2]['buId']
                )]
            );

        /** @var OwnerTree $tree */
        $tree = $this->treeProvider->getTree();
        $businessUnitClass = $this->ownershipMetadataProvider->getBusinessUnitClass();
        $subordinateBusinessUnitIds = ReflectionUtil::callMethod(
            $this->treeProvider,
            'buildTree',
            [$src, $businessUnitClass]
        );

        foreach ($subordinateBusinessUnitIds as $parentBusinessUnit => $businessUnits) {
            $tree->setSubordinateBusinessUnitIds($parentBusinessUnit, $businessUnits);
        }

        foreach ($expected as $parentBusinessUnit => $businessUnits) {
            $this->assertEquals($businessUnits, $tree->getSubordinateBusinessUnitIds($parentBusinessUnit));
        }
    }

    public function addBusinessUnitDirectCyclicRelationProvider(): array
    {
        return [
            'direct cyclic relationship' => [
                [
                    2 => 4,
                    1 => null,
                    3 => 1,
                    4 => 2,
                    5 => 1,
                    6 => 5
                ],
                [
                    1 => [3, 5, 6],
                    5 => [6]
                ],
                [

                    'businessUnitClass' => self::ENTITY_NAMESPACE . '\TestBusinessUnit',
                    'buId' => 2

                ]
            ]
        ];
    }

    public function addBusinessUnitNotDirectCyclicRelationProvider(): array
    {
        return [
            'not direct cyclic relationship' => [
                [
                    1  => null,
                    3  => 1,
                    4  => 1,
                    5 => 7,
                    6 => 5,
                    7  => 6,
                    8 => 14,
                    11 =>8,
                    12 => 11,
                    13 => 12,
                    14 => 13
                ],
                [
                    1 => [3, 4]
                ],
                [
                    [
                        'businessUnitClass' => self::ENTITY_NAMESPACE . '\TestBusinessUnit',
                        'buId' => 5
                    ],
                    [
                        'businessUnitClass' => self::ENTITY_NAMESPACE . '\TestBusinessUnit',
                        'buId' => 8
                    ],
                    [
                        'businessUnitClass' => self::ENTITY_NAMESPACE . '\TestBusinessUnit',
                        'buId' => 12
                    ]
                ]
            ]
        ];
    }
}
