<?php

namespace Oro\Bundle\SecurityBundle\Tests\Functional\Entity\Repository;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Query\Parameter;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

use Oro\Bundle\SecurityBundle\Entity\Repository\PermissionRepository;

/**
 * @dbIsolation
 */
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

        $this->repository = $this->getContainer()->get('doctrine')
            ->getRepository('OroSecurityBundle:Permission');

        $this->loadFixtures([
            'Oro\Bundle\SecurityBundle\Tests\Functional\DataFixtures\LoadPermissionData'
        ]);
    }

    /**
     * @param mixed $inputData
     * @param mixed $expectedData
     *
     * @dataProvider findByIdsProvider
     */
    public function testFindByIds($inputData, $expectedData)
    {
        $this->prepareData($inputData);
        $this->prepareData($expectedData);

        $this->assertEquals($expectedData, $this->repository->findByIds($inputData));
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
     * @param array $inputData
     * @param string $expectedData
     *
     * @dataProvider addFindByIdsCriteriaProvider
     */
    public function testAddFindByIdsCriteria(array $inputData, $expectedData)
    {
        $queryBuilder = $this->repository->createQueryBuilder($inputData['alias']);

        $this->repository->addFindByIdsCriteria($queryBuilder, $inputData['ids']);

        $this->assertEquals($expectedData, $queryBuilder->getQuery()->getDQL());
    }

    /**
     * @param array $inputData
     * @param string $expectedData
     *
     * @dataProvider addFindByEntityClassCriteriaProvider
     */
    public function testAddFindByEntityClassCriteria(array $inputData, $expectedData)
    {
        $queryBuilder = $this->repository->createQueryBuilder($inputData['alias']);

        $this->repository->addFindByEntityClassCriteria($queryBuilder, $inputData['class']);

        $this->assertEquals($expectedData['dql'], $queryBuilder->getQuery()->getDQL());
        $this->assertEquals($expectedData['parameters'], $queryBuilder->getQuery()->getParameters());
    }

    /**
     * @return array
     */
    public function findByIdsProvider()
    {
        return [
            'empty ids' => [
                'input' => [],
                'expected' => [],
            ],
            'bad ids' => [
                'input' => [
                    0,
                ],
                'expected' => [],
            ],
            'valid ids' => [
                'input' => function() {
                    return [
                        $this->getReference('TEST_PERMISSION1')->getId(),
                        $this->getReference('TEST_PERMISSION2')->getId(),
                    ];
                },
                'expected' => function() {
                    return [
                        $this->getReference('TEST_PERMISSION1'),
                        $this->getReference('TEST_PERMISSION2'),
                    ];
                },
            ],
        ];
    }

    /**
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
     * @return array
     */
    public function addFindByIdsCriteriaProvider()
    {
        return [
            [
                'input' => [
                    'alias' => 'ps',
                    'ids' => [],
                ],
                'expected' => 'SELECT ps FROM Oro\Bundle\SecurityBundle\Entity\Permission ps WHERE ps.id IN()',
            ],
            [
                'input' => [
                    'alias' => 'ps',
                    'ids' => [1, 2, 3],
                ],
                'expected' => 'SELECT ps FROM Oro\Bundle\SecurityBundle\Entity\Permission ps WHERE ps.id IN(1, 2, 3)',
            ],
        ];
    }

    /**
     * @return array
     */
    public function addFindByEntityClassCriteriaProvider()
    {
        return [
            [
                'input' => [
                    'alias' => 'ps',
                    'class' => 'Entity1',
                ],
                'expected' => [
                    'parameters' => new ArrayCollection([
                        new Parameter('class', 'Entity1'),
                    ]),
                    'dql' =>  'SELECT ps FROM Oro\Bundle\SecurityBundle\Entity\Permission ps ' .
                        'LEFT JOIN ps.applyToEntities ae WITH ae.name = :class ' .
                        'LEFT JOIN ps.excludeEntities ee WITH ee.name = :class ' .
                        'GROUP BY ps.id ' .
                        'HAVING ' .
                            '(ps.applyToAll = true AND COUNT(ee) = 0) ' .
                            'OR ' .
                            '(ps.applyToAll = false AND COUNT(ae) > 0)',
                ],
            ],
        ];
    }

    /**
     * @param mixed $data
     */
    protected function prepareData(&$data)
    {
        if (is_callable($data)) {
            $data = $data();
        }
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
            if (!in_array($permission->getName(), $validNames)) {
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
            return;
        }

        $ids = [];
        foreach ($names as $name) {
            $ids[] = $this->getReference($name)->getId();
        }

        return $ids;
    }
}
