<?php

namespace Oro\Bundle\SecurityBundle\Tests\Unit\Acl\Permission;

use Doctrine\Common\Cache\CacheProvider;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManager;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\SecurityBundle\Acl\Permission\PermissionManager;
use Oro\Bundle\SecurityBundle\Configuration\PermissionConfigurationBuilder;
use Oro\Bundle\SecurityBundle\Configuration\PermissionConfigurationProvider;
use Oro\Bundle\SecurityBundle\Configuration\PermissionListConfiguration;
use Oro\Bundle\SecurityBundle\Entity\Permission;
use Oro\Bundle\SecurityBundle\Entity\PermissionEntity;
use Oro\Bundle\SecurityBundle\Entity\Repository\PermissionRepository;
use Oro\Bundle\SecurityBundle\Tests\Unit\Configuration\Stub\TestBundle1\TestBundle1;
use Oro\Bundle\SecurityBundle\Tests\Unit\Configuration\Stub\TestBundle2\TestBundle2;
use Oro\Component\Config\CumulativeResourceManager;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class PermissionManagerTest extends \PHPUnit\Framework\TestCase
{
    /** @var PermissionManager */
    protected $manager;

    /** @var DoctrineHelper|\PHPUnit\Framework\MockObject\MockObject */
    protected $doctrineHelper;

    /** @var PermissionRepository|\PHPUnit\Framework\MockObject\MockObject */
    protected $entityRepository;

    /** @var EntityManager|\PHPUnit\Framework\MockObject\MockObject */
    protected $entityManager;

    /** @var PermissionConfigurationProvider|\PHPUnit\Framework\MockObject\MockObject */
    protected $configurationProvider;

    /** @var PermissionConfigurationBuilder */
    protected $configurationBuilder;

    /** @var CacheProvider|\PHPUnit\Framework\MockObject\MockObject */
    protected $cacheProvider;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $bundle1  = new TestBundle1();
        $bundle2  = new TestBundle2();
        $bundles = [$bundle1->getName() => get_class($bundle1), $bundle2->getName() => get_class($bundle2)];

        CumulativeResourceManager::getInstance()->clear()->setBundles($bundles);

        $this->configurationProvider = $this->getMockBuilder(PermissionConfigurationProvider::class)
            ->setConstructorArgs([new PermissionListConfiguration(), $bundles])
            ->setMethods(['getConfigPath'])
            ->getMock();

        $this->entityRepository = $this
            ->getMockBuilder('Oro\Bundle\SecurityBundle\Entity\Repository\PermissionRepository')
            ->disableOriginalConstructor()
            ->getMock();

        $this->doctrineHelper = $this->getMockBuilder('Oro\Bundle\EntityBundle\ORM\DoctrineHelper')
            ->disableOriginalConstructor()
            ->getMock();

        $this->doctrineHelper->expects($this->any())
            ->method('getEntityRepository')
            ->with('OroSecurityBundle:Permission')
            ->willReturn($this->entityRepository);

        $this->doctrineHelper->expects($this->any())
            ->method('getEntityRepositoryForClass')
            ->with('OroSecurityBundle:PermissionEntity')
            ->willReturn($this->entityRepository);

        $this->entityManager = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();

        $this->doctrineHelper->expects($this->any())
            ->method('getEntityManagerForClass')
            ->with('OroSecurityBundle:Permission')
            ->willReturn($this->entityManager);

        /** @var \PHPUnit\Framework\MockObject\MockObject|ValidatorInterface $validator */
        $validator = $this->createMock('Symfony\Component\Validator\Validator\ValidatorInterface');
        $validator->expects($this->any())
            ->method('validate')
            ->with($this->isInstanceOf('Oro\Bundle\SecurityBundle\Entity\Permission'))
            ->willReturn(new ConstraintViolationList());

        $this->configurationBuilder = new PermissionConfigurationBuilder($this->doctrineHelper, $validator);

        $this->cacheProvider = $this->getMockBuilder('Doctrine\Common\Cache\CacheProvider')
            ->setMethods(['fetch', 'save', 'deleteAll'])
            ->getMockForAbstractClass();

        $this->manager = new PermissionManager(
            $this->doctrineHelper,
            $this->configurationProvider,
            $this->configurationBuilder,
            $this->cacheProvider
        );
    }

    public function testGetPermissionsFromConfig()
    {
        $this->assertEmpty($this->manager->getPermissionsFromConfig());

        $this->configurationProvider->expects($this->any())
            ->method('getConfigPath')->willReturn('permissions.yml');

        $permissionNames = [];

        foreach ($this->manager->getPermissionsFromConfig() as $permission) {
            $permissionNames[] = $permission->getName();
        }

        $this->assertEquals(['PERMISSION1', 'PERMISSION2', 'PERMISSION3'], $permissionNames);
    }

    public function testProcessPermissions()
    {
        $permissionOld = $this->getPermission(
            1,
            'PERMISSION1',
            false,
            ['entity1', 'entity2'],
            ['entity10', 'entity11'],
            ['group1']
        );
        $permissions = new ArrayCollection([
            $this->getPermission(2, 'PERMISSION1', true, ['entity1', 'entity2'], ['entity10', 'entity11'], ['group1']),
            $this->getPermission(3, 'PERMISSION2', false, ['entity2', 'entity3'], ['entity11', 'entity12'], ['group1']),
            $this->getPermission(4, 'PERMISSION3', true, ['entity3', 'entity4'], ['entity12', 'entity13'], ['group2']),
        ]);

        $this->entityRepository->expects($this->once())
            ->method('findBy')
            ->with([], ['id' => 'ASC'])
            ->willReturn($permissions);

        $this->entityRepository->expects($this->exactly(count($permissions)))
            ->method('findOneBy')
            ->willReturnMap([
                [['name' => 'PERMISSION1'], null, $permissionOld],
                [['name' => 'PERMISSION2'], null],
                [['name' => 'PERMISSION3'], null],
            ]);

        $permissionsProcessed = $this->manager->processPermissions($permissions);

        $this->assertNotEquals($permissionsProcessed[0], $permissions[0]);
        $this->assertEquals([$permissionsProcessed[1], $permissionsProcessed[2]], [$permissions[1], $permissions[2]]);
        $this->assertEquals($permissionOld->getId(), $permissionsProcessed[0]->getId());
        $this->assertEquals($permissionOld->isApplyToAll(), $permissions[0]->isApplyToAll());
    }

    /**
     * @param array $inputData
     * @param array $expectedData
     * @param array $expectedCacheData
     *
     * @dataProvider getPermissionsMapProvider
     */
    public function testGetPermissionsMap(array $inputData, array $expectedData, array $expectedCacheData = [])
    {
        $this->entityRepository->expects($inputData['cache'] ? $this->never() : $this->once())
            ->method('findBy')
            ->with([], ['id' => 'ASC'])
            ->willReturn($inputData['permissions']);

        $this->cacheProvider->expects($this->once())
            ->method('fetch')
            ->with($inputData['cacheKey'])
            ->willReturn($inputData['cache']);

        if ($expectedCacheData) {
            $this->cacheProvider->expects($this->once())->method('deleteAll');
            $this->cacheProvider->expects($this->exactly(count($expectedCacheData)))
                ->method('save')
                ->willReturnMap($expectedCacheData);
        }

        $this->assertEquals($expectedData, $this->manager->getPermissionsMap($inputData['group']));
    }

    /**
     * @param array $inputData
     * @param array $expectedData
     *
     * @dataProvider getPermissionsForEntityProvider
     */
    public function testGetPermissionsForEntity(array $inputData, array $expectedData)
    {
        $this->cacheProvider->expects($inputData['group'] ? $this->once() : $this->never())
            ->method('fetch')
            ->with(PermissionManager::CACHE_GROUPS)
            ->willReturn($inputData['cache']);

        $this->doctrineHelper->expects($this->once())
            ->method('getEntityClass')
            ->with($inputData['entity'])
            ->willReturn($inputData['entity']);

        $this->entityRepository->expects($this->once())
            ->method('findByEntityClassAndIds')
            ->with($inputData['entity'], $inputData['ids'])
            ->willReturn($inputData['permissions']);

        $this->assertEquals(
            $expectedData,
            $this->manager->getPermissionsForEntity($inputData['entity'], $inputData['group'])
        );
    }

    /**
     * @param array $inputData
     * @param array $expectedData
     *
     * @dataProvider getPermissionsForGroupProvider
     */
    public function testGetPermissionsForGroup(array $inputData, array $expectedData)
    {
        $this->cacheProvider->expects($this->once())
            ->method('fetch')
            ->with(PermissionManager::CACHE_GROUPS)
            ->willReturn($inputData['cache']);

        $this->entityRepository->expects($expectedData ? $this->once() : $this->never())
            ->method('findBy')
            ->with(['id' => $inputData['ids']], ['id' => 'ASC'])
            ->willReturn($inputData['permissions']);

        $this->assertEquals(
            $expectedData,
            $this->manager->getPermissionsForGroup($inputData['group'])
        );
    }

    /**
     * @param array $inputData
     * @param mixed $expectedData
     *
     * @dataProvider getPermissionByNameProvider
     */
    public function testGetPermissionByName(array $inputData, $expectedData)
    {
        $this->cacheProvider->expects($this->once())
            ->method('fetch')
            ->with(PermissionManager::CACHE_PERMISSIONS)
            ->willReturn($inputData['cache']);

        $this->entityManager->expects($inputData['permission'] ? $this->once() : $this->never())
            ->method('getReference')
            ->with('OroSecurityBundle:Permission', $inputData['id'])
            ->willReturn($inputData['permission']);

        $this->assertEquals($expectedData, $this->manager->getPermissionByName($inputData['name']));

        // data from local cache
        $this->assertEquals($expectedData, $this->manager->getPermissionByName($inputData['name']));
    }

    /**
     * @return array
     *
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function getPermissionsMapProvider()
    {
        $cache = [
            PermissionManager::CACHE_PERMISSIONS => ['PERMISSION1' => 1, 'PERMISSION2' => 2, 'PERMISSION3' => 3],
            PermissionManager::CACHE_GROUPS => [
                'group1' => ['PERMISSION1' => 1, 'PERMISSION2' => 2],
                'group2' => ['PERMISSION3' => 3],
                'default' => ['PERMISSION2' => 2, 'PERMISSION3' => 3],
            ],
        ];

        $permissions = [
            $this->getPermission(1, 'PERMISSION1', true, ['entity1', 'entity2'], ['entity10', 'entity11'], ['group1']),
            $this->getPermission(
                2,
                'PERMISSION2',
                false,
                ['entity2', 'entity3'],
                ['entity11', 'entity12'],
                ['', 'group1']
            ),
            $this->getPermission(
                3,
                'PERMISSION3',
                true,
                ['entity3', 'entity4'],
                ['entity12', 'entity13'],
                ['', 'group2']
            ),
        ];

        $expectedCacheData = [
            [
                'permissions',
                ['PERMISSION1' => 1, 'PERMISSION2' => 2, 'PERMISSION3' => 3]
            ],
            [
                'groups',
                [
                    'group1' => ['PERMISSION1' => 1, 'PERMISSION2' => 2],
                    'group2' => ['PERMISSION3' => 3],
                    '' => ['PERMISSION2' => 2, 'PERMISSION3' => 3]
                ]
            ]
        ];

        return [
            'get permissions with no cache and no permissions' => [
                'input' => [
                    'group' => null,
                    'cacheKey' => PermissionManager::CACHE_PERMISSIONS,
                    'cache' => false,
                    'permissions' => [],
                ],
                'expected' => [],
            ],
            'get permissions with no cache' => [
                'input' => [
                    'group' => null,
                    'cacheKey' => PermissionManager::CACHE_PERMISSIONS,
                    'cache' => false,
                    'permissions' => $permissions,
                ],
                'expected' => ['PERMISSION1' => 1, 'PERMISSION2' => 2, 'PERMISSION3' => 3],
                'expectedCacheData' => $expectedCacheData
            ],
            'get permissions with cache and no permissions' => [
                'input' => [
                    'group' => null,
                    'cacheKey' => PermissionManager::CACHE_PERMISSIONS,
                    'cache' => $cache[PermissionManager::CACHE_PERMISSIONS],
                    'permissions' => [],
                ],
                'expected' => ['PERMISSION1' => 1, 'PERMISSION2' => 2, 'PERMISSION3' => 3],
            ],
            'get unknown group with no cache and no permissions' => [
                'input' => [
                    'group' => 'unknown',
                    'cacheKey' => PermissionManager::CACHE_GROUPS,
                    'cache' => false,
                    'permissions' => [],
                ],
                'expected' => [],
            ],
            'get group with no cache' => [
                'input' => [
                    'group' => 'group1',
                    'cacheKey' => PermissionManager::CACHE_GROUPS,
                    'cache' => false,
                    'permissions' => $permissions,
                ],
                'expected' => ['PERMISSION1' => 1, 'PERMISSION2' => 2],
                'expectedCacheData' => $expectedCacheData
            ],
            'get group with cache and no permissions' => [
                'input' => [
                    'group' => 'group1',
                    'cacheKey' => PermissionManager::CACHE_GROUPS,
                    'cache' => $cache[PermissionManager::CACHE_GROUPS],
                    'permissions' => [],
                ],
                'expected' => ['PERMISSION1' => 1, 'PERMISSION2' => 2],
            ],
            'default group with cache and no permissions' => [
                'input' => [
                    'group' => '',
                    'cacheKey' => PermissionManager::CACHE_GROUPS,
                    'cache' => $cache[PermissionManager::CACHE_GROUPS],
                    'permissions' => [],
                ],
                'expected' => ['PERMISSION2' => 2, 'PERMISSION3' => 3],
            ],
        ];
    }

    /**
     * @return array
     */
    public function getPermissionsForEntityProvider()
    {
        $cache = [
            'group1' => ['PERMISSION1' => 1, 'PERMISSION2' => 2],
            'group2' => ['PERMISSION3' => 3],
        ];

        $permissions = [
            $this->getPermission(1, 'PERMISSION1', true, ['entity1', 'entity2'], ['entity10', 'entity11'], ['group1']),
            $this->getPermission(2, 'PERMISSION2', false, ['entity2', 'entity3'], ['entity11', 'entity12'], ['group1']),
            $this->getPermission(3, 'PERMISSION3', true, ['entity3', 'entity4'], ['entity12', 'entity13'], ['group2']),
        ];

        return [
            'entity1 and empty group' => [
                'input' => [
                    'entity' => 'entity1',
                    'group' => null,
                    'cache' => null,
                    'ids' => null,
                    'permissions' => [
                        $permissions[0],
                    ],
                ],
                'expected' => [
                    $permissions[0],
                ],
            ],
            'entity1 and unknown group' => [
                'input' => [
                    'entity' => 'entity1',
                    'group' => 'unknown',
                    'cache' => $cache,
                    'ids' => [],
                    'permissions' => [],
                ],
                'expected' => [],
            ],
            'entity1 and group1' => [
                'input' => [
                    'entity' => 'entity1',
                    'group' => 'group1',
                    'cache' => $cache,
                    'ids' => ['PERMISSION1' => 1, 'PERMISSION2' => 2],
                    'permissions' => [
                        $permissions[0],
                        $permissions[1],
                    ],
                ],
                'expected' => [
                    $permissions[0],
                    $permissions[1],
                ],
            ],
            'entity1 and group2' => [
                'input' => [
                    'entity' => 'entity1',
                    'group' => 'group2',
                    'cache' => $cache,
                    'ids' => ['PERMISSION3' => 3],
                    'permissions' => [
                        $permissions[2],
                    ],
                ],
                'expected' => [
                    $permissions[2],
                ],
            ],
        ];
    }

    /**
     * @return array
     */
    public function getPermissionsForGroupProvider()
    {
        $cache = [
            'group1' => ['PERMISSION1' => 1, 'PERMISSION2' => 2],
            'group2' => ['PERMISSION3' => 3],
        ];

        $permissions = [
            $this->getPermission(1, 'PERMISSION1', true, ['entity1', 'entity2'], ['entity10', 'entity11'], ['group1']),
            $this->getPermission(2, 'PERMISSION2', false, ['entity2', 'entity3'], ['entity11', 'entity12'], ['group1']),
            $this->getPermission(3, 'PERMISSION3', true, ['entity3', 'entity4'], ['entity12', 'entity13'], ['group2']),
        ];

        return [
            'empty group' => [
                'input' => [
                    'group' => null,
                    'cache' => [],
                    'ids' => [],
                    'permissions' => [],
                ],
                'expected' => [],
            ],
            'unknown group' => [
                'input' => [
                    'group' => 'unknown',
                    'cache' => $cache,
                    'ids' => [],
                    'permissions' => [],
                ],
                'expected' => [],
            ],
            'group1' => [
                'input' => [
                    'group' => 'group1',
                    'cache' => $cache,
                    'ids' => ['PERMISSION1' => 1, 'PERMISSION2' => 2],
                    'permissions' => [
                        $permissions[0],
                        $permissions[1],
                    ],
                ],
                'expected' => [
                    $permissions[0],
                    $permissions[1],
                ],
            ],
            'group2' => [
                'input' => [
                    'group' => 'group2',
                    'cache' => $cache,
                    'ids' => ['PERMISSION3' => 3],
                    'permissions' => [
                        $permissions[2],
                    ],
                ],
                'expected' => [
                    $permissions[2],
                ],
            ],
        ];
    }

    /**
     * @return array
     */
    public function getPermissionByNameProvider()
    {
        $cache = ['PERMISSION1' => 1, 'PERMISSION2' => 2, 'PERMISSION3' => 3];

        $permission = $this->getPermission(
            1,
            'PERMISSION1',
            true,
            ['entity1', 'entity2'],
            ['entity10', 'entity11'],
            ['group1']
        );

        return [
            'empty cache' => [
                'input' => [
                    'cache' => [],
                    'name' => 'name1',
                    'id' => null,
                    'permission' => null,
                ],
                'expected' => null,
            ],
            'unknown name' => [
                'input' => [
                    'cache' => $cache,
                    'name' => 'unknown name',
                    'id' => null,
                    'permission' => null,
                ],
                'expected' => null,
            ],
            'PERMISSION1' => [
                'input' => [
                    'cache' => $cache,
                    'name' => 'PERMISSION1',
                    'id' => 1,
                    'permission' => $permission,
                ],
                'expected' => $permission,
            ],
        ];
    }

    /**
     * @param string $id
     * @param string $name
     * @param bool $applyToAll
     * @param array $applyEntities
     * @param array $excludeEntities
     * @param array $groups
     * @return Permission
     */
    protected function getPermission($id, $name, $applyToAll, $applyEntities, $excludeEntities, $groups)
    {
        $permission = new Permission();

        $reflection = new \ReflectionClass('Oro\Bundle\SecurityBundle\Entity\Permission');
        $reflectionProperty = $reflection->getProperty('id');
        $reflectionProperty->setAccessible(true);
        $reflectionProperty->setValue($permission, $id);

        $permission
            ->setName($name)
            ->setApplyToAll($applyToAll)
            ->setGroupNames($groups);

        foreach ($applyEntities as $entity) {
            $permission->addApplyToEntity($this->getPermissionEntity($entity));
        }

        foreach ($excludeEntities as $entity) {
            $permission->addExcludeEntity($this->getPermissionEntity($entity));
        }

        return $permission;
    }

    /**
     * @param string $name
     * @return PermissionEntity
     */
    protected function getPermissionEntity($name)
    {
        $entity = new PermissionEntity();

        return $entity->setName($name);
    }
}
