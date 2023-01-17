<?php

namespace Oro\Component\EntitySerializer\Tests\Unit;

use Oro\Component\EntitySerializer\DataTransformerInterface;
use Oro\Component\EntitySerializer\Tests\Unit\Fixtures\Entity\Group;
use Oro\Component\EntitySerializer\Tests\Unit\Fixtures\Entity\Product;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Form\DataTransformerInterface as FormDataTransformerInterface;

class DataTransformerEntitySerializerTest extends EntitySerializerTestCase
{
    public function testUndefinedDataTransformerService(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Undefined data transformer service "data_transformer_service_id".');

        $qb = $this->em->getRepository(Group::class)->createQueryBuilder('e')
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

    public function testInvalidDataTransformerType(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(sprintf(
            'Unexpected type of data transformer "stdClass". Expected "%s", "%s" or "callable".',
            DataTransformerInterface::class,
            FormDataTransformerInterface::class
        ));

        $qb = $this->em->getRepository(Group::class)->createQueryBuilder('e')
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

    public function testDataTransformer(): void
    {
        $qb = $this->em->getRepository(Group::class)->createQueryBuilder('e')
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

    public function testFormDataTransformer(): void
    {
        $qb = $this->em->getRepository(Group::class)->createQueryBuilder('e')
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

    public function testDataTransformerAsClosure(): void
    {
        $qb = $this->em->getRepository(Group::class)->createQueryBuilder('e')
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
                        'data_transformer' => function ($value, array $config, array $context) {
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

    public function testDataTransformerForRenamedField(): void
    {
        $qb = $this->em->getRepository(Group::class)->createQueryBuilder('e')
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

    public function testDataTransformerForMovedField(): void
    {
        $qb = $this->em->getRepository(Product::class)->createQueryBuilder('e')
            ->where('e.id = :id')
            ->setParameter('id', 1);

        $this->setQueryExpectation(
            $this->getDriverConnectionMock($this->em),
            'SELECT p0_.id AS id_0, p0_.name AS name_1,'
            . ' u1_.id AS id_2, u1_.name AS name_3'
            . ' FROM product_table p0_'
            . ' LEFT JOIN user_table u1_ ON p0_.owner_id = u1_.id'
            . ' WHERE p0_.id = ?',
            [
                [
                    'id_0'   => 1,
                    'name_1' => 'product_name',
                    'id_2'   => 10,
                    'name_3' => 'user_name'
                ]
            ],
            [1 => 1],
            [1 => \PDO::PARAM_INT]
        );

        $transformer = $this->createMock(DataTransformerInterface::class);
        $transformer->expects($this->once())
            ->method('transform')
            ->with(
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
