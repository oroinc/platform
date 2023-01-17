<?php

namespace Oro\Component\EntitySerializer\Tests\Unit;

use Oro\Component\EntitySerializer\Tests\Unit\Fixtures\Entity\Buyer;
use Oro\Component\EntitySerializer\Tests\Unit\Fixtures\Entity\Department;
use Oro\Component\EntitySerializer\Tests\Unit\Fixtures\Entity\Employee;

class InheritanceEntitySerializerTest extends EntitySerializerTestCase
{
    public function testManyToOne(): void
    {
        $qb = $this->em->getRepository(Department::class)->createQueryBuilder('e')
            ->where('e.id = :id')
            ->setParameter('id', 1);

        $this->setQueryExpectation(
            $this->getDriverConnectionMock($this->em),
            'SELECT d0_.id AS id_0, d0_.name AS name_1,'
            . ' p1_.id AS id_2, p1_.name AS name_3, p1_.type AS type_4'
            . ' FROM department_table d0_'
            . ' LEFT JOIN person_table p1_ ON d0_.manager_id = p1_.id AND p1_.type IN (\'employee\', \'buyer\')'
            . ' WHERE d0_.id = ?',
            [
                [
                    'id_0'   => 1,
                    'name_1' => 'department_name',
                    'id_2'   => 10,
                    'name_3' => 'person_name',
                    'type_4' => 'employee'
                ]
            ],
            [1 => 1],
            [1 => \PDO::PARAM_INT]
        );

        $result = $this->serializer->serialize(
            $qb,
            [
                'exclusion_policy' => 'all',
                'fields'           => [
                    'id'      => null,
                    'name'    => null,
                    'manager' => [
                        'exclusion_policy' => 'all',
                        'fields'           => [
                            'type' => ['property_path' => '__class__'],
                            'id'   => null,
                            'name' => null
                        ]
                    ]
                ]
            ]
        );

        $this->assertArrayEquals(
            [
                [
                    'id'      => 1,
                    'name'    => 'department_name',
                    'manager' => [
                        'type' => Employee::class,
                        'id'   => 10,
                        'name' => 'person_name'
                    ]
                ]
            ],
            $result
        );
    }

    public function testManyToOneWithDisabledPartialLoad(): void
    {
        $qb = $this->em->getRepository(Department::class)->createQueryBuilder('e')
            ->where('e.id = :id')
            ->setParameter('id', 1);

        $this->setQueryExpectation(
            $this->getDriverConnectionMock($this->em),
            'SELECT d0_.id AS id_0, d0_.name AS name_1,'
            . ' p1_.id AS id_2, p1_.name AS name_3, p1_.position AS position_4,'
            . ' d0_.manager_id AS manager_id_5, d0_.owner_id AS owner_id_6,'
            . ' p1_.type AS type_7, p1_.department_id AS department_id_8, p1_.owner_id AS owner_id_9'
            . ' FROM department_table d0_'
            . ' LEFT JOIN person_table p1_ ON d0_.manager_id = p1_.id AND p1_.type IN (\'employee\', \'buyer\')'
            . ' WHERE d0_.id = ?',
            [
                [
                    'id_0'            => 1,
                    'name_1'          => 'department_name',
                    'id_2'            => 10,
                    'name_3'          => 'person_name',
                    'position_4'      => 'person_position1',
                    'manager_id_5'    => 50,
                    'owner_id_6'      => 100,
                    'type_7'          => 'employee',
                    'department_id_8' => 1,
                    'owner_id_9'      => 100
                ]
            ],
            [1 => 1],
            [1 => \PDO::PARAM_INT]
        );

        $result = $this->serializer->serialize(
            $qb,
            [
                'disable_partial_load' => true,
                'exclusion_policy'     => 'all',
                'fields'               => [
                    'id'      => null,
                    'name'    => null,
                    'manager' => [
                        'disable_partial_load' => true,
                        'exclusion_policy'     => 'all',
                        'fields'               => [
                            'type' => ['property_path' => '__class__'],
                            'id'   => null,
                            'name' => null
                        ]
                    ]
                ]
            ]
        );

        $this->assertArrayEquals(
            [
                [
                    'id'      => 1,
                    'name'    => 'department_name',
                    'manager' => [
                        'type' => Employee::class,
                        'id'   => 10,
                        'name' => 'person_name'
                    ]
                ]
            ],
            $result
        );
    }

    public function testManyToMany(): void
    {
        $qb = $this->em->getRepository(Department::class)->createQueryBuilder('e')
            ->where('e.id IN (:ids)')
            ->setParameter('ids', [123, 456]);

        $this->addQueryExpectation(
            'SELECT d0_.id AS id_0, d0_.name AS name_1'
            . ' FROM department_table d0_'
            . ' WHERE d0_.id IN (?, ?)',
            [
                [
                    'id_0'   => 123,
                    'name_1' => 'department_name1'
                ],
                [
                    'id_0'   => 456,
                    'name_1' => 'department_name2'
                ]
            ],
            [1 => 123, 2 => 456],
            [1 => \PDO::PARAM_INT, 2 => \PDO::PARAM_INT]
        );
        $this->addQueryExpectation(
            'SELECT d0_.id AS id_0, p1_.id AS id_1, p1_.name AS name_2, p1_.type AS type_3'
            . ' FROM person_table p1_'
            . ' INNER JOIN department_table d0_ ON p1_.department_id = d0_.id'
            . ' WHERE (d0_.id IN (?, ?)) AND p1_.type IN (\'employee\', \'buyer\')',
            [
                [
                    'id_0'   => 123,
                    'id_1'   => 10,
                    'name_2' => 'person_name1',
                    'type_3' => 'employee'
                ],
                [
                    'id_0'   => 123,
                    'id_1'   => 20,
                    'name_2' => 'person_name2',
                    'type_3' => 'buyer'
                ]
            ],
            [1 => 123, 2 => 456],
            [1 => \PDO::PARAM_INT, 2 => \PDO::PARAM_INT]
        );
        $this->applyQueryExpectations($this->getDriverConnectionMock($this->em));

        $result = $this->serializer->serialize(
            $qb,
            [
                'exclusion_policy' => 'all',
                'fields'           => [
                    'id'    => null,
                    'name'  => null,
                    'staff' => [
                        'exclusion_policy' => 'all',
                        'fields'           => [
                            'type' => ['property_path' => '__class__'],
                            'id'   => null,
                            'name' => null
                        ]
                    ]
                ]
            ]
        );

        $this->assertArrayEquals(
            [
                [
                    'id'    => 123,
                    'name'  => 'department_name1',
                    'staff' => [
                        [
                            'type' => Employee::class,
                            'id'   => 10,
                            'name' => 'person_name1'
                        ],
                        [
                            'type' => Buyer::class,
                            'id'   => 20,
                            'name' => 'person_name2'
                        ]
                    ]
                ],
                [
                    'id'    => 456,
                    'name'  => 'department_name2',
                    'staff' => []
                ]
            ],
            $result
        );
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testManyToManyWithDisabledPartialLoad(): void
    {
        $qb = $this->em->getRepository(Department::class)->createQueryBuilder('e')
            ->where('e.id IN (:ids)')
            ->setParameter('ids', [123, 456]);

        $this->addQueryExpectation(
            'SELECT d0_.id AS id_0, d0_.name AS name_1,'
            . ' d0_.manager_id AS manager_id_2, d0_.owner_id AS owner_id_3'
            . ' FROM department_table d0_'
            . ' WHERE d0_.id IN (?, ?)',
            [
                [
                    'id_0'         => 123,
                    'name_1'       => 'department_name1',
                    'manager_id_2' => 50,
                    'owner_id_3'   => 100
                ],
                [
                    'id_0'         => 456,
                    'name_1'       => 'department_name2',
                    'manager_id_2' => 50,
                    'owner_id_3'   => 100
                ]
            ],
            [1 => 123, 2 => 456],
            [1 => \PDO::PARAM_INT, 2 => \PDO::PARAM_INT]
        );
        $this->addQueryExpectation(
            'SELECT t0.id AS id_1, t0.name AS name_2,'
            . ' t0.department_id AS department_id_3, t0.owner_id AS owner_id_4,'
            . ' t0.type, t0.position AS position_5'
            . ' FROM person_table t0'
            . ' WHERE t0.id = ? AND t0.type IN (\'employee\', \'buyer\')',
            [
                [
                    'id_0'            => 123,
                    'id_1'            => 50,
                    'name_2'          => 'person_name50',
                    'department_id_3' => 123,
                    'owner_id_4'      => 100,
                    'type'            => 'employee',
                    'position_5'      => 'person_position50'
                ]
            ],
            [1 => 50],
            [1 => \PDO::PARAM_INT]
        );
        $this->addQueryExpectation(
            'SELECT d0_.id AS id_0, p1_.id AS id_1, p1_.name AS name_2,'
            . ' p1_.position AS position_3, p1_.type AS type_4,'
            . ' p1_.department_id AS department_id_5, p1_.owner_id AS owner_id_6'
            . ' FROM person_table p1_'
            . ' INNER JOIN department_table d0_ ON p1_.department_id = d0_.id'
            . ' WHERE (d0_.id IN (?, ?)) AND p1_.type IN (\'employee\', \'buyer\')',
            [
                [
                    'id_0'            => 123,
                    'id_1'            => 10,
                    'name_2'          => 'person_name1',
                    'position_3'      => 'person_position1',
                    'type_4'          => 'employee',
                    'department_id_5' => 123,
                    'owner_id_6'      => 100
                ],
                [
                    'id_0'            => 123,
                    'id_1'            => 20,
                    'name_2'          => 'person_name2',
                    'position_3'      => null,
                    'type_4'          => 'buyer',
                    'department_id_5' => 123,
                    'owner_id_6'      => 100
                ]
            ],
            [1 => 123, 2 => 456],
            [1 => \PDO::PARAM_INT, 2 => \PDO::PARAM_INT]
        );
        $this->applyQueryExpectations($this->getDriverConnectionMock($this->em));

        $result = $this->serializer->serialize(
            $qb,
            [
                'disable_partial_load' => true,
                'exclusion_policy'     => 'all',
                'fields'               => [
                    'id'    => null,
                    'name'  => null,
                    'staff' => [
                        'disable_partial_load' => true,
                        'exclusion_policy'     => 'all',
                        'fields'               => [
                            'type' => ['property_path' => '__class__'],
                            'id'   => null,
                            'name' => null
                        ]
                    ]
                ]
            ]
        );

        $this->assertArrayEquals(
            [
                [
                    'id'    => 123,
                    'name'  => 'department_name1',
                    'staff' => [
                        [
                            'type' => Employee::class,
                            'id'   => 10,
                            'name' => 'person_name1'
                        ],
                        [
                            'type' => Buyer::class,
                            'id'   => 20,
                            'name' => 'person_name2'
                        ]
                    ]
                ],
                [
                    'id'    => 456,
                    'name'  => 'department_name2',
                    'staff' => []
                ]
            ],
            $result
        );
    }
}
