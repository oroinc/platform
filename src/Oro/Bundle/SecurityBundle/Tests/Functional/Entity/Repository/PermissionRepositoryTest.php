<?php

namespace Oro\Bundle\SecurityBundle\Tests\Functional\Entity\Repository;

use Oro\Bundle\SecurityBundle\Entity\Permission;
use Oro\Bundle\SecurityBundle\Entity\Repository\PermissionRepository;
use Oro\Bundle\SecurityBundle\Tests\Functional\DataFixtures\LoadPermissionData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class PermissionRepositoryTest extends WebTestCase
{
    protected function setUp(): void
    {
        $this->initClient();
        $this->loadFixtures([LoadPermissionData::class]);
    }

    protected function tearDown(): void
    {
        $this->getContainer()->get('oro_security.cache.provider.permission')->clear();

        parent::tearDown();
    }

    private function getRepository(): PermissionRepository
    {
        return self::getContainer()->get('doctrine')->getRepository(Permission::class);
    }

    /**
     * @dataProvider findByEntityClassAndIdsProvider
     */
    public function testFindByEntityClassAndIds(array $inputData, array $expectedData)
    {
        $permissions = $this->getRepository()->findByEntityClassAndIds(
            $inputData['class'],
            $this->getPermissionsIds($inputData['ids'])
        );

        $this->assertEquals($expectedData, $this->getPermissionsNames($permissions, $inputData['permissions']));
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function findByEntityClassAndIdsProvider(): array
    {
        $permissions = ['TEST_PERMISSION1', 'TEST_PERMISSION2', 'TEST_PERMISSION3', 'TEST_PERMISSION4'];

        return [
            'empty class' => [
                'input' => [
                    'class' => '',
                    'ids' => null,
                    'permissions' => $permissions,
                ],
                'expected' => [],
            ],
            'empty ids' => [
                'input' => [
                    'class' => 'TestEntity1',
                    'ids' => [],
                    'permissions' => $permissions,
                ],
                'expected' => [],
            ],

            'UnknownEntity' => [
                'input' => [
                    'class' => 'UnknownEntity',
                    'ids' => null,
                    'permissions' => $permissions,
                ],
                'expected' => ['TEST_PERMISSION1', 'TEST_PERMISSION4'],
            ],
            'UnknownEntity and ids' => [
                'input' => [
                    'class' => 'UnknownEntity',
                    'ids' => ['TEST_PERMISSION1', 'TEST_PERMISSION2'],
                    'permissions' => $permissions,
                ],
                'expected' => ['TEST_PERMISSION1'],
            ],
            'TestEntity1' => [
                'input' => [
                    'class' => 'TestEntity1',
                    'ids' => null,
                    'permissions' => $permissions,
                ],
                'expected' => ['TEST_PERMISSION1', 'TEST_PERMISSION4'],
            ],
            'TestEntity2' => [
                'input' => [
                    'class' => 'TestEntity2',
                    'ids' => null,
                    'permissions' => $permissions,
                ],
                'expected' => ['TEST_PERMISSION1', 'TEST_PERMISSION2', 'TEST_PERMISSION4'],
            ],
            'TestEntity3' => [
                'input' => [
                    'class' => 'TestEntity3',
                    'ids' => null,
                    'permissions' => $permissions,
                ],
                'expected' => ['TEST_PERMISSION1', 'TEST_PERMISSION2', 'TEST_PERMISSION3', 'TEST_PERMISSION4'],
            ],
            'TestEntity4' => [
                'input' => [
                    'class' => 'TestEntity4',
                    'ids' => null,
                    'permissions' => $permissions,
                ],
                'expected' => ['TEST_PERMISSION1', 'TEST_PERMISSION3', 'TEST_PERMISSION4'],
            ],

            'TestEntity10' => [
                'input' => [
                    'class' => 'TestEntity10',
                    'ids' => null,
                    'permissions' => $permissions,
                ],
                'expected' => ['TEST_PERMISSION4'],
            ],
            'TestEntity11' => [
                'input' => [
                    'class' => 'TestEntity11',
                    'ids' => null,
                    'permissions' => $permissions,
                ],
                'expected' => ['TEST_PERMISSION4'],
            ],
            'TestEntity12' => [
                'input' => [
                    'class' => 'TestEntity12',
                    'ids' => null,
                    'permissions' => $permissions,
                ],
                'expected' => ['TEST_PERMISSION1', 'TEST_PERMISSION4'],
            ],
            'TestEntity13' => [
                'input' => [
                    'class' => 'TestEntity13',
                    'ids' => null,
                    'permissions' => $permissions,
                ],
                'expected' => ['TEST_PERMISSION1', 'TEST_PERMISSION4'],
            ],
        ];
    }

    /**
     * @param Permission[] $permissions
     * @param array $validNames
     *
     * @return string[]
     */
    private function getPermissionsNames(array $permissions, array $validNames): array
    {
        $result = [];
        foreach ($permissions as $permission) {
            if (!in_array($permission->getName(), $validNames, true)) {
                continue;
            }
            $result[] = $permission->getName();
        }

        sort($result);

        return $result;
    }

    /**
     * @param string[] $names
     *
     * @return int[]|null
     */
    private function getPermissionsIds(array $names = null): ?array
    {
        if (null === $names) {
            return null;
        }

        $ids = [];
        foreach ($names as $name) {
            $ids[] = $this->getReference($name)->getId();
        }

        return $ids;
    }
}
