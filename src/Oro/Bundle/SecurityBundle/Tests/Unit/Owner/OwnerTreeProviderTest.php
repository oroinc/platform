<?php

namespace Oro\Bundle\SecurityBundle\Tests\Unit\Owner;

use Doctrine\Common\Cache\CacheProvider;
use Doctrine\ORM\EntityManager;

use Symfony\Component\DependencyInjection\ContainerInterface;

use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\SecurityBundle\SecurityFacade;
use Oro\Bundle\SecurityBundle\Owner\OwnerTree;
use Oro\Bundle\SecurityBundle\Owner\OwnerTreeProvider;
use Oro\Bundle\SecurityBundle\Tests\Unit\Stub\OwnershipMetadataProviderStub;

class OwnerTreeProviderTest extends \PHPUnit_Framework_TestCase
{
    /** @var OwnerTreeProvider */
    protected $treeProvider;

    /** @var \PHPUnit_Framework_MockObject_MockObject|EntityManager */
    protected $em;

    /** @var \PHPUnit_Framework_MockObject_MockObject|CacheProvider */
    protected $cache;

    /** @var \PHPUnit_Framework_MockObject_MockObject|ContainerInterface */
    protected $container;

    /** @var \PHPUnit_Framework_MockObject_MockObject|SecurityFacade */
    protected $securityFacade;

    protected function setUp()
    {
        $this->em = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();

        $managerRegistry = $this->getMock('Doctrine\Common\Persistence\ManagerRegistry');
        $managerRegistry->expects($this->any())->method('getManagerForClass')->willReturn($this->em);

        $this->cache = $this->getMockForAbstractClass('Doctrine\Common\Cache\CacheProvider');
        $this->cache->expects($this->any())->method('fetch')->will($this->returnValue(false));
        $this->cache->expects($this->any())->method('save');

        $this->securityFacade = $this->getMockBuilder('Oro\Bundle\SecurityBundle\SecurityFacade')
            ->disableOriginalConstructor()
            ->getMock();

        $this->container = $this->getMock('Symfony\Component\DependencyInjection\ContainerInterface');
        $this->container->expects($this->any())
            ->method('get')
            ->will(
                $this->returnValueMap(
                    [
                        [
                            'oro_security.ownership_tree_provider.cache',
                            ContainerInterface::EXCEPTION_ON_INVALID_REFERENCE,
                            $this->cache,
                        ],
                        [
                            'oro_security.owner.ownership_metadata_provider',
                            ContainerInterface::EXCEPTION_ON_INVALID_REFERENCE,
                            new OwnershipMetadataProviderStub(
                                $this,
                                [
                                    'user' => 'Oro\Bundle\UserBundle\Entity\User',
                                    'business_unit' => 'Oro\Bundle\OrganizationBundle\Entity\BusinessUnit',
                                ]
                            ),
                        ],
                        [
                            'doctrine',
                            ContainerInterface::EXCEPTION_ON_INVALID_REFERENCE,
                            $managerRegistry,
                        ],
                        [
                            'oro_security.security_facade',
                            ContainerInterface::EXCEPTION_ON_INVALID_REFERENCE,
                            $this->securityFacade,
                        ],
                    ]
                )
            );

        $this->treeProvider = new OwnerTreeProvider($this->em, $this->cache);
        $this->treeProvider->setContainer($this->container);
    }

    protected function tearDown()
    {
        unset($this->cache, $this->container, $this->treeProvider, $this->em);
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testGetTree()
    {
        $userRepo = $this->getMockBuilder('Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->getMock();

        $buRepo = $this->getMockBuilder('Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->getMock();

        $this->em->expects($this->any())
            ->method('getRepository')
            ->will(
                $this->returnValueMap(
                    [
                        ['Oro\Bundle\UserBundle\Entity\User', $userRepo],
                        ['Oro\Bundle\OrganizationBundle\Entity\BusinessUnit', $buRepo],
                    ]
                )
            );

        list($users, $bUnits) = $this->getTestData();

        $qb = $this->getMockBuilder('\Doctrine\ORM\QueryBuilder')
            ->disableOriginalConstructor()
            ->getMock();

        $query = $this->getMockBuilder('\Doctrine\ORM\AbstractQuery')
            ->setMethods(['setParameter', 'getArrayResult'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $userRepo
            ->expects($this->once())
            ->method('createQueryBuilder')
            ->will($this->returnValue($qb));
        $qb
            ->expects($this->any())
            ->method('leftJoin')
            ->will($this->returnValue($qb));
        $qb
            ->expects($this->once())
            ->method('select')
            ->will($this->returnValue($qb));
        $qb
            ->expects($this->once())
            ->method('getQuery')
            ->will($this->returnValue($query));
        $query
            ->expects($this->once())
            ->method('getArrayResult')
            ->will($this->returnValue($users));

        $qb = $this->getMockBuilder('\Doctrine\ORM\QueryBuilder')
            ->disableOriginalConstructor()
            ->getMock();
        $query = $this->getMockBuilder('\Doctrine\ORM\AbstractQuery')
            ->setMethods(['setParameter', 'getArrayResult'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $buRepo
            ->expects($this->once())
            ->method('createQueryBuilder')
            ->will($this->returnValue($qb));
        $qb
            ->expects($this->once())
            ->method('select')
            ->will($this->returnValue($qb));
        $qb
            ->expects($this->once())
            ->method('addSelect')
            ->will($this->returnValue($qb));
        $qb
            ->expects($this->once())
            ->method('addOrderBy')
            ->will($this->returnValue($qb));
        $qb
            ->expects($this->once())
            ->method('getQuery')
            ->will($this->returnValue($query));
        $query
            ->expects($this->once())
            ->method('getArrayResult')
            ->will($this->returnValue($bUnits));

        $metadata = $this->getMockBuilder('Doctrine\ORM\Mapping\ClassMetadata')
            ->disableOriginalConstructor()
            ->getMock();
        $this->em->expects($this->any())
            ->method('getClassMetadata')
            ->will($this->returnValue($metadata));
        $metadata->expects($this->any())
            ->method('getTableName')
            ->will($this->returnValue('test'));
        $connection = $this->getMockBuilder('Doctrine\DBAL\Connection')
            ->disableOriginalConstructor()
            ->getMock();
        $this->em->expects($this->any())
            ->method('getConnection')
            ->will($this->returnValue($connection));
        $schemaManager = $this->getMockBuilder('Doctrine\DBAL\Schema\MySqlSchemaManager')
            ->disableOriginalConstructor()
            ->getMock();
        $connection->expects($this->any())
            ->method('getSchemaManager')
            ->will($this->returnValue($schemaManager));
        $schemaManager->expects($this->any())
            ->method('tablesExist')
            ->with('test')
            ->will($this->returnValue(true));

        $this->treeProvider->warmUpCache();

        /** @var OwnerTree $tree */
        $tree = $this->treeProvider->getTree();
        $this->assertEquals(1, $tree->getBusinessUnitOrganizationId(1));
        $this->assertEquals([1], $tree->getUserOrganizationIds(1));
        $this->assertEquals([1], $tree->getUsersAssignedToBU(1));
        $this->assertEquals([1, 2, 3], $tree->getBusinessUnitsIdByUserOrganizations(3));
        $this->assertEquals([3, 4], $tree->getUsersAssignedToBU(3));
    }

    /**
     * @param object $object
     * @param mixed $value
     */
    protected function setId($object, $value)
    {
        $reflection = new \ReflectionClass($object);
        $property = $reflection->getProperty('id');
        $property->setAccessible(true);
        $property->setValue($object, $value);
    }

    protected function getTestData()
    {
        $organization = [
            'id' => 1
        ];

        $mainBu = [
            'id'            => 1,
            'organization'  => $organization
        ];


        $bu2 = [
            'id'            => 2,
            'organization'  => $organization
        ];

        $childBu = [
            'id'            => 3,
            'organization'  => $organization,
            'owner'         => $mainBu
        ];

        $user1 = [
            'id'            => 1,
            'owner'         => $mainBu,
            'businessUnits' => [$mainBu],
            'organizations' => [$organization]
        ];

        $user2 = [
            'id'            => 2,
            'owner'         => $bu2,
            'businessUnits' => [$bu2],
            'organizations' => [$organization]
        ];

        $user3 = [
            'id'            => 3,
            'owner'         => $childBu,
            'businessUnits' => [$childBu, $bu2],
            'organizations' => [$organization]
        ];

        $user4 = [
            'id'            => 4,
            'owner'         => null,
            'businessUnits' => [$childBu],
            'organizations' => [$organization]
        ];

        return [
            [
                $user1,
                $user2,
                $user3,
                $user4
            ],
            [
                [
                    'id' => 1,
                    'organization' => 1,
                    'owner' => null
                ],
                [
                    'id' => 2,
                    'organization' => 1,
                    'owner' => null
                ],
                [
                    'id' => 3,
                    'organization' => 1,
                    'owner' => 1
                ]
            ]
        ];
    }

    /**
     * @param object $user
     * @param bool $expected
     *
     * @dataProvider supportsDataProvider
     */
    public function testSupports($user, $expected)
    {
        $this->securityFacade->expects($this->once())->method('getLoggedUser')->willReturn($user);

        $this->assertEquals($expected, $this->treeProvider->supports());
    }

    /**
     * @return array
     */
    public function supportsDataProvider()
    {
        return [
            [null, false],
            [new \stdClass(), false],
            [new User(), true],
        ];
    }
}
