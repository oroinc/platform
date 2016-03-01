<?php

namespace Oro\Component\EntitySerializer\Tests\Unit;

use Symfony\Component\DependencyInjection\ContainerInterface;

class DataTransformerEntitySerializerTest extends EntitySerializerTestCase
{
    // @codingStandardsIgnoreStart
    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Undefined data transformer service "data_transformer_service_id". Class: Oro\Component\EntitySerializer\Tests\Unit\Fixtures\Entity\Group. Property: name.
     */
    // @codingStandardsIgnoreEnd
    public function testUndefinedDataTransformerService()
    {
        $qb = $this->em->getRepository('Test:Group')->createQueryBuilder('e')
            ->where('e.id = :id')
            ->setParameter('id', 1);

        $this->setQueryExpectation(
            $this->getDriverConnectionMock($this->em),
            'SELECT g0_.id AS id_0, g0_.name AS name_1'
            . ' FROM group_table g0_'
            . ' WHERE g0_.id = ?',
            [
                [
                    'id_0'   => 1,
                    'name_1' => 'group_name',
                ]
            ],
            [1 => 1],
            [1 => \PDO::PARAM_INT]
        );

        $this->container->expects($this->once())
            ->method('get')
            ->with('data_transformer_service_id', ContainerInterface::NULL_ON_INVALID_REFERENCE)
            ->willReturn(null);

        $this->serializer->serialize(
            $qb,
            [
                'exclusion_policy' => 'all',
                'fields'           => [
                    'id'   => null,
                    'name' => [
                        'data_transformer' => 'data_transformer_service_id'
                    ],
                ],
            ]
        );
    }

    // @codingStandardsIgnoreStart
    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Unexpected type of data transformer "stdClass". Expected "Oro\Component\EntitySerializer\DataTransformerInterface", "Symfony\Component\Form\DataTransformerInterface" or "callable". Class: Oro\Component\EntitySerializer\Tests\Unit\Fixtures\Entity\Group. Property: name.
     */
    // @codingStandardsIgnoreEnd
    public function testInvalidDataTransformerType()
    {
        $qb = $this->em->getRepository('Test:Group')->createQueryBuilder('e')
            ->where('e.id = :id')
            ->setParameter('id', 1);

        $this->setQueryExpectation(
            $this->getDriverConnectionMock($this->em),
            'SELECT g0_.id AS id_0, g0_.name AS name_1'
            . ' FROM group_table g0_'
            . ' WHERE g0_.id = ?',
            [
                [
                    'id_0'   => 1,
                    'name_1' => 'group_name',
                ]
            ],
            [1 => 1],
            [1 => \PDO::PARAM_INT]
        );

        $this->container->expects($this->once())
            ->method('get')
            ->with('data_transformer_service_id', ContainerInterface::NULL_ON_INVALID_REFERENCE)
            ->willReturn(new \stdClass());

        $this->serializer->serialize(
            $qb,
            [
                'exclusion_policy' => 'all',
                'fields'           => [
                    'id'   => null,
                    'name' => [
                        'data_transformer' => 'data_transformer_service_id'
                    ],
                ],
            ]
        );
    }

    public function testDataTransformer()
    {
        $qb = $this->em->getRepository('Test:Group')->createQueryBuilder('e')
            ->where('e.id = :id')
            ->setParameter('id', 1);

        $this->setQueryExpectation(
            $this->getDriverConnectionMock($this->em),
            'SELECT g0_.id AS id_0, g0_.name AS name_1'
            . ' FROM group_table g0_'
            . ' WHERE g0_.id = ?',
            [
                [
                    'id_0'   => 1,
                    'name_1' => 'group_name',
                ]
            ],
            [1 => 1],
            [1 => \PDO::PARAM_INT]
        );

        $transformer = $this->getMock('Oro\Component\EntitySerializer\DataTransformerInterface');
        $transformer->expects($this->once())
            ->method('transform')
            ->with(
                'Oro\Component\EntitySerializer\Tests\Unit\Fixtures\Entity\Group',
                'name',
                'group_name',
                ['data_transformer' => ['data_transformer_service_id']]
            )
            ->willReturn('transformed_group_name');

        $this->container->expects($this->once())
            ->method('get')
            ->with('data_transformer_service_id', ContainerInterface::NULL_ON_INVALID_REFERENCE)
            ->willReturn($transformer);

        $result = $this->serializer->serialize(
            $qb,
            [
                'exclusion_policy' => 'all',
                'fields'           => [
                    'id'   => null,
                    'name' => [
                        'data_transformer' => 'data_transformer_service_id'
                    ],
                ],
            ]
        );

        $this->assertArrayEquals(
            [
                [
                    'id'   => 1,
                    'name' => 'transformed_group_name',
                ]
            ],
            $result
        );
    }

    public function testFormDataTransformer()
    {
        $qb = $this->em->getRepository('Test:Group')->createQueryBuilder('e')
            ->where('e.id = :id')
            ->setParameter('id', 1);

        $this->setQueryExpectation(
            $this->getDriverConnectionMock($this->em),
            'SELECT g0_.id AS id_0, g0_.name AS name_1'
            . ' FROM group_table g0_'
            . ' WHERE g0_.id = ?',
            [
                [
                    'id_0'   => 1,
                    'name_1' => 'group_name',
                ]
            ],
            [1 => 1],
            [1 => \PDO::PARAM_INT]
        );

        $transformer = $this->getMock('Symfony\Component\Form\DataTransformerInterface');
        $transformer->expects($this->once())
            ->method('transform')
            ->with('group_name')
            ->willReturn('transformed_group_name');

        $this->container->expects($this->once())
            ->method('get')
            ->with('data_transformer_service_id', ContainerInterface::NULL_ON_INVALID_REFERENCE)
            ->willReturn($transformer);

        $result = $this->serializer->serialize(
            $qb,
            [
                'exclusion_policy' => 'all',
                'fields'           => [
                    'id'   => null,
                    'name' => [
                        'data_transformer' => 'data_transformer_service_id'
                    ],
                ],
            ]
        );

        $this->assertArrayEquals(
            [
                [
                    'id'   => 1,
                    'name' => 'transformed_group_name',
                ]
            ],
            $result
        );
    }

    public function testDataTransformerForRenamingField()
    {
        $qb = $this->em->getRepository('Test:Group')->createQueryBuilder('e')
            ->where('e.id = :id')
            ->setParameter('id', 1);

        $this->setQueryExpectation(
            $this->getDriverConnectionMock($this->em),
            'SELECT g0_.id AS id_0, g0_.name AS name_1'
            . ' FROM group_table g0_'
            . ' WHERE g0_.id = ?',
            [
                [
                    'id_0'   => 1,
                    'name_1' => 'group_name',
                ]
            ],
            [1 => 1],
            [1 => \PDO::PARAM_INT]
        );

        $transformer = $this->getMock('Oro\Component\EntitySerializer\DataTransformerInterface');
        $transformer->expects($this->once())
            ->method('transform')
            ->with(
                'Oro\Component\EntitySerializer\Tests\Unit\Fixtures\Entity\Group',
                'name',
                'group_name',
                ['data_transformer' => ['data_transformer_service_id']]
            )
            ->willReturn('transformed_group_name');

        $this->container->expects($this->once())
            ->method('get')
            ->with('data_transformer_service_id', ContainerInterface::NULL_ON_INVALID_REFERENCE)
            ->willReturn($transformer);

        $result = $this->serializer->serialize(
            $qb,
            [
                'exclusion_policy' => 'all',
                'fields'           => [
                    'id'      => null,
                    'newName' => [
                        'data_transformer' => 'data_transformer_service_id',
                        'property_path'    => 'name'
                    ],
                ],
            ]
        );

        $this->assertArrayEquals(
            [
                [
                    'id'      => 1,
                    'newName' => 'transformed_group_name',
                ]
            ],
            $result
        );
    }

    public function testDataTransformerForMovedField()
    {
        $qb = $this->em->getRepository('Test:Product')->createQueryBuilder('e')
            ->where('e.id = :id')
            ->setParameter('id', 1);

        $this->setQueryExpectation(
            $this->getDriverConnectionMock($this->em),
            'SELECT p0_.id AS id_0, p0_.name AS name_1,'
            . ' u1_.id AS id_2, u1_.name AS name_3,'
            . ' p0_.category_name AS category_name_4, p0_.owner_id AS owner_id_5,'
            . ' u1_.category_name AS category_name_6'
            . ' FROM product_table p0_'
            . ' LEFT JOIN user_table u1_ ON p0_.owner_id = u1_.id'
            . ' WHERE p0_.id = ?',
            [
                [
                    'id_0'            => 1,
                    'name_1'          => 'product_name',
                    'id_2'            => 10,
                    'name_3'          => 'user_name',
                    'category_name_4' => 'category_name',
                    'owner_id_5'      => 10,
                    'category_name_6' => 'user_category_name',
                ]
            ],
            [1 => 1],
            [1 => \PDO::PARAM_INT]
        );

        $transformer = $this->getMock('Oro\Component\EntitySerializer\DataTransformerInterface');
        $transformer->expects($this->once())
            ->method('transform')
            ->with(
                'Oro\Component\EntitySerializer\Tests\Unit\Fixtures\Entity\User',
                'name',
                'user_name',
                ['data_transformer' => ['data_transformer_service_id']]
            )
            ->willReturn('transformed_user_name');

        $this->container->expects($this->once())
            ->method('get')
            ->with('data_transformer_service_id', ContainerInterface::NULL_ON_INVALID_REFERENCE)
            ->willReturn($transformer);

        $result = $this->serializer->serialize(
            $qb,
            [
                'exclusion_policy' => 'all',
                'fields'           => [
                    'id'        => null,
                    'name'      => null,
                    'ownerName' => [
                        'property_path'    => 'owner.name',
                        'data_transformer' => 'data_transformer_service_id'
                    ],
                    'owner'     => [
                        'fields' => 'id'
                    ]
                ]
            ]
        );

        $this->assertArrayEquals(
            [
                [
                    'id'        => 1,
                    'name'      => 'product_name',
                    'ownerName' => 'transformed_user_name',
                    'owner'     => 10
                ]
            ],
            $result
        );
    }
}
