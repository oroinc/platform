<?php

namespace Oro\Bundle\SecurityBundle\Tests\Unit\Acl\Permission;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManager;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\SecurityBundle\Acl\Permission\PermissionManager;
use Oro\Bundle\SecurityBundle\Entity\Permission;
use Oro\Bundle\SecurityBundle\Entity\PermissionEntity;
use Oro\Bundle\SecurityBundle\Entity\Repository\PermissionRepository;
use Oro\Bundle\SecurityBundle\Tests\Unit\Fixtures\Bundles\TestBundle1\TestBundle1;
use Oro\Bundle\SecurityBundle\Tests\Unit\Fixtures\Bundles\TestBundle2\TestBundle2;
use Oro\Component\Config\CumulativeResourceManager;
use Oro\Component\Testing\ReflectionUtil;
use Oro\Component\Testing\TempDirExtension;
use Symfony\Component\Cache\Adapter\AbstractAdapter;
use Symfony\Contracts\Cache\ItemInterface;

class PermissionManagerTest extends \PHPUnit\Framework\TestCase
{
    use TempDirExtension;

    /** @var DoctrineHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $doctrineHelper;

    /** @var PermissionRepository|\PHPUnit\Framework\MockObject\MockObject */
    private $entityRepository;

    /** @var EntityManager|\PHPUnit\Framework\MockObject\MockObject */
    private $entityManager;

    /** @var AbstractAdapter|\PHPUnit\Framework\MockObject\MockObject */
    private $cacheProvider;

    /** @var PermissionManager */
    private $manager;

    protected function setUp(): void
    {
        $bundle1 = new TestBundle1();
        $bundle2 = new TestBundle2();
        CumulativeResourceManager::getInstance()
            ->clear()
            ->setBundles([
                $bundle1->getName() => get_class($bundle1),
                $bundle2->getName() => get_class($bundle2)
            ]);

        $this->entityRepository = $this->createMock(PermissionRepository::class);

        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);

        $this->doctrineHelper->expects($this->any())
            ->method('getEntityRepositoryForClass')
            ->with(Permission::class)
            ->willReturn($this->entityRepository);

        $this->entityManager = $this->createMock(EntityManager::class);

        $this->doctrineHelper->expects($this->any())
            ->method('getEntityManagerForClass')
            ->with(Permission::class)
            ->willReturn($this->entityManager);

        $this->cacheProvider = $this->createMock(AbstractAdapter::class);

        $this->manager = new PermissionManager(
            $this->doctrineHelper,
            $this->cacheProvider
        );
    }

    public function testProcessPermissions(): void
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
     * @dataProvider getPermissionsMapProvider
     */
    public function testGetPermissionsMap(array $inputData, array $expectedData, array $expectedCacheData = []): void
    {
        $this->entityRepository->expects($inputData['cache'] ? $this->never() : $this->any())
            ->method('findBy')
            ->with([], ['id' => 'ASC'])
            ->willReturn($inputData['permissions']);

        if ($inputData['cache'] === false) {
            $this->cacheProvider->expects($this->any())
                ->method('get')
                ->with($inputData['cacheKey'])
                ->willReturnCallback(function ($cacheKey, $callback) {
                    $item = $this->createMock(ItemInterface::class);
                    return $callback($item);
                });
        } else {
            $this->cacheProvider->expects($this->any())
                ->method('get')
                ->with($inputData['cacheKey'])
                ->willReturn($inputData['cache']);
        }

        $this->assertEquals($expectedData, $this->manager->getPermissionsMap($inputData['group']));
    }

    /**
     * @dataProvider getPermissionsForEntityProvider
     */
    public function testGetPermissionsForEntity(array $inputData, array $expectedData): void
    {
        $this->cacheProvider->expects($inputData['group'] ? $this->once() : $this->never())
            ->method('get')
            ->with('groups')
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
     * @dataProvider getPermissionsForGroupProvider
     */
    public function testGetPermissionsForGroup(array $inputData, array $expectedData): void
    {
        $this->cacheProvider->expects($this->once())
            ->method('get')
            ->with('groups')
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
     * @dataProvider getPermissionByNameProvider
     */
    public function testGetPermissionByName(array $inputData, ?Permission $expectedData): void
    {
        $this->cacheProvider->expects($this->once())
            ->method('get')
            ->with('permissions')
            ->willReturn($inputData['cache']);

        $this->entityManager->expects($inputData['permission'] ? $this->once() : $this->never())
            ->method('getReference')
            ->with(Permission::class, $inputData['id'])
            ->willReturn($inputData['permission']);

        $this->assertEquals($expectedData, $this->manager->getPermissionByName($inputData['name']));

        // data from local cache
        $this->assertEquals($expectedData, $this->manager->getPermissionByName($inputData['name']));
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function getPermissionsMapProvider(): array
    {
        $cache = [
            'permissions' => ['PERMISSION1' => 1, 'PERMISSION2' => 2, 'PERMISSION3' => 3],
            'groups' => [
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
                    'cacheKey' => 'permissions',
                    'cache' => false,
                    'permissions' => [],
                ],
                'expected' => [],
            ],
            'get permissions with no cache' => [
                'input' => [
                    'group' => null,
                    'cacheKey' => 'permissions',
                    'cache' => false,
                    'permissions' => $permissions,
                ],
                'expected' => ['PERMISSION1' => 1, 'PERMISSION2' => 2, 'PERMISSION3' => 3],
                'expectedCacheData' => $expectedCacheData
            ],
            'get permissions with cache and no permissions' => [
                'input' => [
                    'group' => null,
                    'cacheKey' => 'permissions',
                    'cache' => $cache['permissions'],
                    'permissions' => [],
                ],
                'expected' => ['PERMISSION1' => 1, 'PERMISSION2' => 2, 'PERMISSION3' => 3],
            ],
            'get unknown group with no cache and no permissions' => [
                'input' => [
                    'group' => 'unknown',
                    'cacheKey' => 'groups',
                    'cache' => false,
                    'permissions' => [],
                ],
                'expected' => [],
            ],
            'get group with no cache' => [
                'input' => [
                    'group' => 'group1',
                    'cacheKey' => 'groups',
                    'cache' => false,
                    'permissions' => $permissions,
                ],
                'expected' => ['PERMISSION1' => 1, 'PERMISSION2' => 2],
                'expectedCacheData' => $expectedCacheData
            ],
            'get group with cache and no permissions' => [
                'input' => [
                    'group' => 'group1',
                    'cacheKey' => 'groups',
                    'cache' => $cache['groups'],
                    'permissions' => [],
                ],
                'expected' => ['PERMISSION1' => 1, 'PERMISSION2' => 2],
            ],
            'default group with cache and no permissions' => [
                'input' => [
                    'group' => '',
                    'cacheKey' => 'groups',
                    'cache' => $cache['groups'],
                    'permissions' => [],
                ],
                'expected' => ['PERMISSION2' => 2, 'PERMISSION3' => 3],
            ],
        ];
    }

    public function getPermissionsForEntityProvider(): array
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

    public function getPermissionsForGroupProvider(): array
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

    public function getPermissionByNameProvider(): array
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

    private function getPermission(
        string $id,
        string $name,
        bool $applyToAll,
        array $applyEntities,
        array $excludeEntities,
        array $groups
    ): Permission {
        $permission = new Permission();
        ReflectionUtil::setId($permission, $id);
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

    private function getPermissionEntity(string $name): PermissionEntity
    {
        $entity = new PermissionEntity();

        return $entity->setName($name);
    }
}
