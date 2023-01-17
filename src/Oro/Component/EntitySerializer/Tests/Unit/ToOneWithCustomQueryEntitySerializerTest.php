<?php

namespace Oro\Component\EntitySerializer\Tests\Unit;

use Doctrine\ORM\Query\Expr\Join;
use Oro\Component\EntitySerializer\AssociationQuery;
use Oro\Component\EntitySerializer\Tests\Unit\Fixtures\Entity;

class ToOneWithCustomQueryEntitySerializerTest extends EntitySerializerTestCase
{
    public function hasLimitDataProvider(): array
    {
        return [[false], [true]];
    }

    /**
     * @dataProvider hasLimitDataProvider
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testToOneCustomAssociationWhenOnlyIdentifierFieldIsRequested(bool $hasLimit): void
    {
        $qb = $this->em->getRepository(Entity\Group::class)->createQueryBuilder('e')
            ->where('e.id IN (:ids)')
            ->setParameter('ids', [123, 456]);

        $this->addQueryExpectation(
            'SELECT g0_.id AS id_0, g0_.name AS name_1'
            . ' FROM group_table g0_'
            . ' WHERE g0_.id IN (?, ?)',
            [
                [
                    'id_0'   => 123,
                    'name_1' => 'group_name1'
                ],
                [
                    'id_0'   => 456,
                    'name_1' => 'group_name2'
                ]
            ],
            [1 => 123, 2 => 456],
            [1 => \PDO::PARAM_INT, 2 => \PDO::PARAM_INT]
        );
        $this->addQueryExpectation(
            'SELECT g0_.id AS id_0, c1_.name AS name_1'
            . ' FROM group_table g0_'
            . ' INNER JOIN user_table u2_ ON (EXISTS ('
            . 'SELECT 1 FROM rel_user_to_group_table r3_'
            . ' WHERE r3_.user_id = u2_.id AND r3_.user_group_id IN (g0_.id)))'
            . ' INNER JOIN category_table c1_ ON u2_.category_name = c1_.name'
            . ' WHERE g0_.id IN (?, ?)',
            [
                [
                    'id_0'   => 123,
                    'name_1' => 'category1'
                ]
            ],
            [1 => 123, 2 => 456],
            [1 => \PDO::PARAM_INT, 2 => \PDO::PARAM_INT]
        );
        $this->applyQueryExpectations($this->getDriverConnectionMock($this->em));

        $associationQb = $this->em->getRepository(Entity\Group::class)
            ->createQueryBuilder('e')
            ->innerJoin(Entity\User::class, 'u', Join::WITH, 'e MEMBER OF u.groups')
            ->innerJoin('u.category', 'r');

        $config = [
            'exclusion_policy' => 'all',
            'fields'           => [
                'id'       => null,
                'name'     => null,
                'category' => [
                    'exclusion_policy'  => 'all',
                    'association_query' => new AssociationQuery($associationQb, Entity\Category::class, false),
                    'fields'            => [
                        'name' => null
                    ]
                ]
            ]
        ];
        if ($hasLimit) {
            $config['fields']['category']['max_results'] = 5;
        }
        $result = $this->serializer->serialize($qb, $config);

        $this->assertArrayEquals(
            [
                [
                    'id'       => 123,
                    'name'     => 'group_name1',
                    'category' => [
                        'name' => 'category1'
                    ]
                ],
                [
                    'id'       => 456,
                    'name'     => 'group_name2',
                    'category' => null
                ]
            ],
            $result
        );
    }

    /**
     * @dataProvider hasLimitDataProvider
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testToOneCustomAssociationWhenOnlyScalarFieldsAreRequested(bool $hasLimit): void
    {
        $qb = $this->em->getRepository(Entity\Group::class)->createQueryBuilder('e')
            ->where('e.id IN (:ids)')
            ->setParameter('ids', [123, 456]);

        $this->addQueryExpectation(
            'SELECT g0_.id AS id_0, g0_.name AS name_1'
            . ' FROM group_table g0_'
            . ' WHERE g0_.id IN (?, ?)',
            [
                [
                    'id_0'   => 123,
                    'name_1' => 'group_name1'
                ],
                [
                    'id_0'   => 456,
                    'name_1' => 'group_name2'
                ]
            ],
            [1 => 123, 2 => 456],
            [1 => \PDO::PARAM_INT, 2 => \PDO::PARAM_INT]
        );
        $this->addQueryExpectation(
            'SELECT g0_.id AS id_0, c1_.name AS name_1, c1_.label AS label_2'
            . ' FROM group_table g0_'
            . ' INNER JOIN user_table u2_ ON (EXISTS ('
            . 'SELECT 1 FROM rel_user_to_group_table r3_'
            . ' WHERE r3_.user_id = u2_.id AND r3_.user_group_id IN (g0_.id)))'
            . ' INNER JOIN category_table c1_ ON u2_.category_name = c1_.name'
            . ' WHERE g0_.id IN (?, ?)',
            [
                [
                    'id_0'    => 123,
                    'name_1'  => 'category1',
                    'label_2' => 'Category 1'
                ]
            ],
            [1 => 123, 2 => 456],
            [1 => \PDO::PARAM_INT, 2 => \PDO::PARAM_INT]
        );
        $this->applyQueryExpectations($this->getDriverConnectionMock($this->em));

        $associationQb = $this->em->getRepository(Entity\Group::class)
            ->createQueryBuilder('e')
            ->innerJoin(Entity\User::class, 'u', Join::WITH, 'e MEMBER OF u.groups')
            ->innerJoin('u.category', 'r');

        $config = [
            'exclusion_policy' => 'all',
            'fields'           => [
                'id'       => null,
                'name'     => null,
                'category' => [
                    'exclusion_policy'  => 'all',
                    'association_query' => new AssociationQuery($associationQb, Entity\Category::class, false),
                    'fields'            => [
                        'name'  => null,
                        'label' => null
                    ]
                ]
            ]
        ];
        if ($hasLimit) {
            $config['fields']['category']['max_results'] = 5;
        }
        $result = $this->serializer->serialize($qb, $config);

        $this->assertArrayEquals(
            [
                [
                    'id'       => 123,
                    'name'     => 'group_name1',
                    'category' => [
                        'name'  => 'category1',
                        'label' => 'Category 1'
                    ]
                ],
                [
                    'id'       => 456,
                    'name'     => 'group_name2',
                    'category' => null
                ]
            ],
            $result
        );
    }
}
