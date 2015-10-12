<?php

namespace Oro\Bundle\SecurityBundle\Tests\Unit\Owner;

use Doctrine\Common\Cache\CacheProvider;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManager;

use Symfony\Component\DependencyInjection\ContainerInterface;

use Oro\Bundle\OrganizationBundle\Entity\BusinessUnit;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\SecurityBundle\SecurityFacade;
use Oro\Bundle\SecurityBundle\Owner\OwnerTree;
use Oro\Bundle\SecurityBundle\Owner\OwnerTreeProvider;
use Oro\Bundle\SecurityBundle\Tests\Unit\Stub\OwnershipMetadataProviderStub;
use Oro\Bundle\UserBundle\Entity\User;

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

        $userRepo->expects($this->any())
            ->method('findAll')
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
        $connection->expects($this->any())
            ->method('isConnected')
            ->will($this->returnValue(true));
        $schemaManager = $this->getMockBuilder('Doctrine\DBAL\Schema\MySqlSchemaManager')
            ->disableOriginalConstructor()
            ->getMock();
        $connection->expects($this->any())
            ->method('getSchemaManager')
            ->will($this->returnValue($schemaManager));
        $schemaManager->expects($this->any())
            ->method('listTableNames')
            ->will($this->returnValue(['test']));

        $this->treeProvider->warmUpCache();

        /** @var OwnerTree $tree */
        $tree = $this->treeProvider->getTree();
        $this->assertEquals(1, $tree->getBusinessUnitOrganizationId(1));
        $this->assertEquals([1], $tree->getUserOrganizationIds(1));
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
        $organization = new Organization();
        $this->setId($organization, 1);

        $mainBu = new BusinessUnit();
        $this->setId($mainBu, 1);
        $mainBu->setOrganization($organization);

        $bu2 = new BusinessUnit();
        $this->setId($bu2, 2);
        $bu2->setOrganization($organization);

        $childBu = new BusinessUnit();
        $this->setId($childBu, 3);
        $childBu->setOrganization($organization);
        $childBu->setOwner($mainBu);

        $user1 = new User();
        $this->setId($user1, 1);
        $user1->setOwner($mainBu);
        $user1->addBusinessUnit($mainBu);
        $user1->setOrganizations(new ArrayCollection([$organization]));

        $user2 = new User();
        $this->setId($user2, 2);
        $user2->setOwner($bu2);
        $user2->addBusinessUnit($bu2);
        $user2->setOrganizations(new ArrayCollection([$organization]));

        $user3 = new User();
        $this->setId($user3, 3);
        $user3->setOwner($childBu);
        $user3->addBusinessUnit($childBu);
        $user3->setOrganizations(new ArrayCollection([$organization]));

        $user3 = new User();
        $this->setId($user3, 4);
        $user3->addBusinessUnit($childBu);
        $user3->setOrganizations(new ArrayCollection([$organization]));

        return [
            [
                $user1,
                $user2,
                $user3
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
