<?php

namespace Oro\Bundle\SecurityBundle\Tests\Functional\Entity\Repository;

use Oro\Bundle\SecurityBundle\Entity\Permission;
use Oro\Bundle\SecurityBundle\Entity\Repository\PermissionRepository;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class PermissionRepositoryTest extends WebTestCase
{
    /**
     * @var PermissionRepository
     */
    protected $repository;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->initClient();

        $this->loadFixtures([
            'Oro\Bundle\SecurityBundle\Tests\Functional\DataFixtures\LoadPermissionData'
        ]);

        $this->repository = $this->getContainer()->get('doctrine')->getRepository('OroSecurityBundle:Permission');
    }

    protected function tearDown()
    {
        $this->getContainer()->get('oro_security.cache.provider.permission')->deleteAll();

        parent::tearDown();
    }

    /**
     * @param mixed $inputData
     * @param mixed $expectedData
     *
     * @dataProvider findByEntityClassAndIdsProvider
     */
    public function testFindByEntityClassAndIds($inputData, $expectedData)
    {
        $permissions = $this->repository->findByEntityClassAndIds(
            $inputData['class'],
            $this->getPermissionsIds($inputData['ids'])
        );

        $this->assertEquals($expectedData, $this->getPermissionsNames($permissions, $inputData['permissions']));
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     * @return array
     */
    public function findByEntityClassAndIdsProvider()
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
     * @return array
     */
    protected function getPermissionsNames(array $permissions, array $validNames)
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
     * @param array $names
     * @return array|null
     */
    protected function getPermissionsIds(array $names = null)
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
