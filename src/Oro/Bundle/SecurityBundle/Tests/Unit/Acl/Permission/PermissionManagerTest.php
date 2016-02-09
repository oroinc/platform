<?php

namespace Oro\Bundle\SecurityBundle\Tests\Unit\Acl\Permission;

use Doctrine\Common\Cache\CacheProvider;
use Doctrine\ORM\EntityRepository;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;

use Oro\Bundle\SecurityBundle\Acl\Permission\PermissionManager;
use Oro\Bundle\SecurityBundle\Configuration\PermissionConfigurationBuilder;
use Oro\Bundle\SecurityBundle\Configuration\PermissionConfigurationProvider;
use Oro\Bundle\SecurityBundle\Entity\Permission;
use Oro\Bundle\SecurityBundle\Entity\PermissionEntity;

class PermissionManagerTest extends \PHPUnit_Framework_TestCase
{
    /** @var PermissionManager */
    protected $manager;

    /** @var DoctrineHelper|\PHPUnit_Framework_MockObject_MockObject */
    protected $doctrineHelper;

    /** @var EntityRepository|\PHPUnit_Framework_MockObject_MockObject */
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
        $this->entityRepository = $this->getMockBuilder('Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->getMock();

        $this->doctrineHelper = $this->getMockBuilder('Oro\Bundle\EntityBundle\ORM\DoctrineHelper')
            ->disableOriginalConstructor()
            ->getMock();

        $this->doctrineHelper->expects($this->any())
            ->method('getEntityRepository')
            ->with('OroSecurityBundle:Permission')
            ->willReturn($this->entityRepository);

        $this->configurationProvider = $this->getMockBuilder(
                'Oro\Bundle\SecurityBundle\Configuration\PermissionConfigurationProvider'
            )
            ->disableOriginalConstructor()
            ->getMock();

        $this->configurationBuilder = $this->getMockBuilder(
                'Oro\Bundle\SecurityBundle\Configuration\PermissionConfigurationBuilder'
            )
            ->disableOriginalConstructor()
            ->getMock();

        $this->cacheProvider = $this->getMock('Doctrine\Common\Cache\CacheProvider');

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
        $this->doctrineHelper->expects($this->any())
            ->method('getEntityRepository')
            ->with('OroSecurityBundle:Permission')
            ->willReturn($this->entityRepository);

        $this->entityRepository->expects($this->any())
            ->method('findAll')
            ->willReturn($inputData['permissions']);

        $this->cacheProvider->expects($this->once())
            ->method('fetch')
            ->with($inputData['cacheKey'])
            ->willReturn($inputData['cache']);

        $this->assertSame($expectedData, $this->manager->getPermissionsMap($inputData['group']));
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

        $this->manager->buildCache();
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
                    'permissions' => [
                        $this->getPermission(1, 'PERMISSION1', true, ['entity1', 'entity2'], ['entity10', 'entity11'], ['group1']),
                        $this->getPermission(2, 'PERMISSION2', false, ['entity2', 'entity3'], ['entity11', 'entity12'], ['group1']),
                        $this->getPermission(3, 'PERMISSION3', true, ['entity3', 'entity4'], ['entity12', 'entity13'], ['group2']),
                    ],
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
                    'permissions' => [
                        $this->getPermission(1, 'PERMISSION1', true, ['entity1', 'entity2'], ['entity10', 'entity11'], ['group1']),
                        $this->getPermission(2, 'PERMISSION2', false, ['entity2', 'entity3'], ['entity11', 'entity12'], ['group1']),
                        $this->getPermission(3, 'PERMISSION3', true, ['entity3', 'entity4'], ['entity12', 'entity13'], ['group2']),
                    ],
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
    public function buildCacheProvider()
    {
        return [
            [
                'input' => [
                    $this->getPermission(1, 'PERMISSION1', true, ['entity1', 'entity2'], ['entity10', 'entity11'], ['group1']),
                    $this->getPermission(2, 'PERMISSION2', false, ['entity2', 'entity3'], ['entity11', 'entity12'], ['group1']),
                    $this->getPermission(3, 'PERMISSION3', true, ['entity3', 'entity4'], ['entity12', 'entity13'], ['group2']),
                ],
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
