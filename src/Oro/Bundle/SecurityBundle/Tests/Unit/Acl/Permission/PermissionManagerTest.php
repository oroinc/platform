<?php

namespace Oro\Bundle\SecurityBundle\Tests\Unit\Acl\Permission;

use Doctrine\Common\Cache\CacheProvider;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;

use Oro\Bundle\SecurityBundle\Acl\Permission\PermissionManager;
use Oro\Bundle\SecurityBundle\Configuration\PermissionConfigurationBuilder;
use Oro\Bundle\SecurityBundle\Configuration\PermissionConfigurationProvider;
use Oro\Bundle\SecurityBundle\Entity\Permission;
use Oro\Bundle\SecurityBundle\Entity\PermissionEntity;
use Oro\Bundle\SecurityBundle\Entity\Repository\PermissionRepository;

class PermissionManagerTest extends \PHPUnit_Framework_TestCase
{
    /** @var PermissionManager */
    protected $manager;

    /** @var DoctrineHelper|\PHPUnit_Framework_MockObject_MockObject */
    protected $doctrineHelper;

    /** @var PermissionRepository|\PHPUnit_Framework_MockObject_MockObject */
    protected $entityRepository;

    /** @var PermissionConfigurationProvider|\PHPUnit_Framework_MockObject_MockObject */
    protected $configurationProvider;

    /** @var PermissionConfigurationBuilder|\PHPUnit_Framework_MockObject_MockObject */
    protected $configurationBuilder;

    /** @var CacheProvider|\PHPUnit_Framework_MockObject_MockObject */
    protected $cacheProvider;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
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

        $this->configurationProvider = $this
            ->getMockBuilder('Oro\Bundle\SecurityBundle\Configuration\PermissionConfigurationProvider')
            ->disableOriginalConstructor()
            ->getMock();

        $this->configurationBuilder = $this
            ->getMockBuilder('Oro\Bundle\SecurityBundle\Configuration\PermissionConfigurationBuilder')
            ->disableOriginalConstructor()
            ->getMock();

        $this->cacheProvider = $this->getMockBuilder('Doctrine\Common\Cache\CacheProvider')
            ->setMethods(['fetch', 'saveMultiple', 'flushAll'])
            ->getMockForAbstractClass();

        $this->manager = new PermissionManager(
            $this->doctrineHelper,
            $this->configurationProvider,
            $this->configurationBuilder,
            $this->cacheProvider
        );
    }

    /**
     * @param array $inputData
     * @param array $expectedData
     *
     * @dataProvider getPermissionsMapProvider
     */
    public function testGetPermissionsMap(array $inputData, array $expectedData)
    {
        $this->entityRepository->expects($inputData['cache'] ? $this->never() : $this->once())
            ->method('findAll')
            ->willReturn($inputData['permissions']);

        $this->cacheProvider->expects($this->once())
            ->method('fetch')
            ->with($inputData['cacheKey'])
            ->willReturn($inputData['cache']);

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
     * @dataProvider buildCacheProvider
     */
    public function testBuildCache(array $inputData, array $expectedData)
    {
        $this->entityRepository->expects($this->once())
            ->method('findAll')
            ->willReturn($inputData);

        $this->cacheProvider->expects($this->once())
            ->method('flushAll');

        $this->cacheProvider->expects($this->once())
            ->method('saveMultiple')
            ->with($expectedData);

        $this->assertEquals($expectedData, $this->manager->buildCache());
    }

    /**
     * @return array
     */
    public function getPermissionsMapProvider()
    {
        $cache = [
            PermissionManager::CACHE_PERMISSIONS => ['PERMISSION1' => 1, 'PERMISSION2' => 2, 'PERMISSION3' => 3],
            PermissionManager::CACHE_GROUPS => [
                'group1' => ['PERMISSION1' => 1, 'PERMISSION2' => 2],
                'group2' => ['PERMISSION3' => 3],
            ],
        ];

        $permissions = [
            $this->getPermission(1, 'PERMISSION1', true, ['entity1', 'entity2'], ['entity10', 'entity11'], ['group1']),
            $this->getPermission(2, 'PERMISSION2', false, ['entity2', 'entity3'], ['entity11', 'entity12'], ['group1']),
            $this->getPermission(3, 'PERMISSION3', true, ['entity3', 'entity4'], ['entity12', 'entity13'], ['group2']),
        ];

        return [
            'get permissions with no cache and no permissions' => [
                'input' => [
                    'group' => '',
                    'cacheKey' => PermissionManager::CACHE_PERMISSIONS,
                    'cache' => false,
                    'permissions' => [],
                ],
                'expected' => [],
            ],
            'get permissions with no cache' => [
                'input' => [
                    'group' => '',
                    'cacheKey' => PermissionManager::CACHE_PERMISSIONS,
                    'cache' => false,
                    'permissions' => $permissions,
                ],
                'expected' => ['PERMISSION1' => 1, 'PERMISSION2' => 2, 'PERMISSION3' => 3],
            ],
            'get permissions with cache and no permissions' => [
                'input' => [
                    'group' => '',
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
                    'group' => '',
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
            'entity1 and unknown group1' => [
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
                        $permissions[3],
                    ],
                ],
                'expected' => [
                    $permissions[3],
                ],
            ],
        ];
    }

    /**
     * @return array
     */
    public function buildCacheProvider()
    {
        $permissions = [
            $this->getPermission(1, 'PERMISSION1', true, ['entity1', 'entity2'], ['entity10', 'entity11'], ['group1']),
            $this->getPermission(2, 'PERMISSION2', false, ['entity2', 'entity3'], ['entity11', 'entity12'], ['group1']),
            $this->getPermission(3, 'PERMISSION3', true, ['entity3', 'entity4'], ['entity12', 'entity13'], ['group2']),
        ];

        return [
            [
                'input' => $permissions,
                'expected' => [
                    'permissions' => ['PERMISSION1' => 1, 'PERMISSION2' => 2, 'PERMISSION3' => 3],
                    'groups' => [
                        'group1' => ['PERMISSION1' => 1, 'PERMISSION2' => 2],
                        'group2' => ['PERMISSION3' => 3],
                    ],
                ],
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
     * @return Permission|\PHPUnit_Framework_MockObject_MockObject
     */
    protected function getPermission($id, $name, $applyToAll, $applyEntities, $excludeEntities, $groups)
    {
        /* @var $permission Permission|\PHPUnit_Framework_MockObject_MockObject */
        $permission = $this->getMockBuilder('Oro\Bundle\SecurityBundle\Entity\Permission')
            ->setMethods(['getId'])
            ->getMock();

        $permission->expects($this->any())
            ->method('getId')
            ->willReturn($id);

        $permission
            ->setName($name)
            ->setApplyToAll($applyToAll)
            ->setGroupNames($groups);

        foreach ($applyEntities as $entity) {
            $permission->addApplyToEntities($this->getPermissionEntity($entity));
        }

        foreach ($excludeEntities as $entity) {
            $permission->addExcludeEntities($this->getPermissionEntity($entity));
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

        $entity->setName($name);

        return $entity;
    }
}
