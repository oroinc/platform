<?php

namespace Oro\Component\EntitySerializer\Tests\Unit;

use Oro\Component\EntitySerializer\DataTransformerInterface;
use Oro\Component\EntitySerializer\Tests\Unit\Fixtures\Entity;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Form\DataTransformerInterface as FormDataTransformerInterface;

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

        $context = ['key' => 'context value'];
        $transformer = $this->createMock(DataTransformerInterface::class);
        $transformer->expects($this->once())
            ->method('transform')
            ->with(
                Entity\Group::class,
                'name',
                'group_name',
                ['data_transformer' => ['data_transformer_service_id']],
                $context
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
            ],
            $context
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

        $transformer = $this->createMock(FormDataTransformerInterface::class);
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

    public function testDataTransformerAsClosure()
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

        $context = ['key' => 'context value'];
        $result = $this->serializer->serialize(
            $qb,
            [
                'exclusion_policy' => 'all',
                'fields'           => [
                    'id'   => null,
                    'name' => [
                        'data_transformer' => function ($class, $property, $value, array $config, array $context) {
                            return sprintf('transformed_group_name[%s]', $context['key']);
                        }
                    ],
                ],
            ],
            $context
        );

        $this->assertArrayEquals(
            [
                [
                    'id'   => 1,
                    'name' => 'transformed_group_name[context value]',
                ]
            ],
            $result
        );
    }

    public function testDataTransformerForRenamedField()
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

        $transformer = $this->createMock(DataTransformerInterface::class);
        $transformer->expects($this->once())
            ->method('transform')
            ->with(
                Entity\Group::class,
                'newName',
                'group_name',
                ['data_transformer' => ['data_transformer_service_id'], 'property_path' => 'name'],
                []
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

        $transformer = $this->createMock(DataTransformerInterface::class);
        $transformer->expects($this->once())
            ->method('transform')
            ->with(
                Entity\Product::class,
                'ownerName',
                'user_name',
                ['data_transformer' => ['data_transformer_service_id'], 'property_path' => 'owner.name'],
                []
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
                        'data_transformer' => 'data_transformer_service_id',
                        'property_path'    => 'owner.name'
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
