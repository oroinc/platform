<?php

namespace Oro\Bundle\SoapBundle\Tests\Unit\Serializer;

class SimpleEntitySerializerTest extends EntitySerializerTestCase
{
    public function testSimpleEntityWithoutConfig()
    {
        $qb = $this->em->getRepository('Test:Group')->createQueryBuilder('e')
            ->where('e.id = :id')
            ->setParameter('id', 1);

        $this->setQueryExpectation(
            $this->getDriverConnectionMock($this->em),
            'SELECT o0_.id AS id_0, o0_.name AS name_1, o0_.label AS label_2, o0_.public AS public_3'
            . ' FROM oro_test_serializer_group o0_'
            . ' WHERE o0_.id = ?',
            [
                [
                    'id_0'     => 1,
                    'name_1'   => 'test_name',
                    'label_2'  => 'test_label',
                    'public_3' => 1,
                ]
            ],
            [1 => 1],
            [1 => \PDO::PARAM_INT]
        );

        $result = $this->serializer->serialize($qb, []);

        $this->assertArrayEquals(
            [
                [
                    'id'     => 1,
                    'name'   => 'test_name',
                    'label'  => 'test_label',
                    'public' => true
                ]
            ],
            $result
        );
    }

    public function testSimpleEntityWithExclusion()
    {
        $qb = $this->em->getRepository('Test:Group')->createQueryBuilder('e')
            ->where('e.id = :id')
            ->setParameter('id', 1);

        $this->setQueryExpectation(
            $this->getDriverConnectionMock($this->em),
            'SELECT o0_.id AS id_0, o0_.label AS label_1, o0_.public AS public_2'
            . ' FROM oro_test_serializer_group o0_'
            . ' WHERE o0_.id = ?',
            [
                [
                    'id_0'     => 1,
                    'label_1'  => 'test_label',
                    'public_2' => 1,
                ]
            ],
            [1 => 1],
            [1 => \PDO::PARAM_INT]
        );

        $result = $this->serializer->serialize(
            $qb,
            [
                'excluded_fields' => ['name'],
            ]
        );

        $this->assertArrayEquals(
            [
                [
                    'id'     => 1,
                    'label'  => 'test_label',
                    'public' => true
                ]
            ],
            $result
        );
    }

    public function testSimpleEntityWithSpecifiedFieldsButNoExclusionPolicy()
    {
        $qb = $this->em->getRepository('Test:Group')->createQueryBuilder('e')
            ->where('e.id = :id')
            ->setParameter('id', 1);

        $this->setQueryExpectation(
            $this->getDriverConnectionMock($this->em),
            'SELECT o0_.id AS id_0, o0_.name AS name_1, o0_.label AS label_2, o0_.public AS public_3'
            . ' FROM oro_test_serializer_group o0_'
            . ' WHERE o0_.id = ?',
            [
                [
                    'id_0'     => 1,
                    'name_1'   => 'test_name',
                    'label_2'  => null,
                    'public_3' => 0,
                ]
            ],
            [1 => 1],
            [1 => \PDO::PARAM_INT]
        );

        $result = $this->serializer->serialize(
            $qb,
            [
                'fields' => [
                    'id'   => null,
                    'name' => null,
                ],
            ]
        );

        $this->assertArrayEquals(
            [
                [
                    'id'     => 1,
                    'name'   => 'test_name',
                    'label'  => null,
                    'public' => false,
                ]
            ],
            $result
        );
    }

    public function testSimpleEntityWithSpecifiedFieldsOnly()
    {
        $qb = $this->em->getRepository('Test:Group')->createQueryBuilder('e')
            ->where('e.id = :id')
            ->setParameter('id', 1);

        $this->setQueryExpectation(
            $this->getDriverConnectionMock($this->em),
            'SELECT o0_.id AS id_0, o0_.name AS name_1'
            . ' FROM oro_test_serializer_group o0_'
            . ' WHERE o0_.id = ?',
            [
                [
                    'id_0'   => 1,
                    'name_1' => 'test_name',
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
                    'id'   => null,
                    'name' => null,
                ],
            ]
        );

        $this->assertArrayEquals(
            [
                [
                    'id'   => 1,
                    'name' => 'test_name',
                ]
            ],
            $result
        );
    }

    public function testSimpleEntityWithSpecifiedFieldsAndExclusions()
    {
        $qb = $this->em->getRepository('Test:Group')->createQueryBuilder('e')
            ->where('e.id = :id')
            ->setParameter('id', 1);

        $this->setQueryExpectation(
            $this->getDriverConnectionMock($this->em),
            'SELECT o0_.id AS id_0'
            . ' FROM oro_test_serializer_group o0_'
            . ' WHERE o0_.id = ?',
            [
                [
                    'id_0' => 1,
                ]
            ],
            [1 => 1],
            [1 => \PDO::PARAM_INT]
        );

        $result = $this->serializer->serialize(
            $qb,
            [
                'exclusion_policy' => 'all',
                'excluded_fields'  => ['name'],
                'fields'           => [
                    'id'   => null,
                    'name' => null,
                ],
            ]
        );

        $this->assertArrayEquals(
            [
                [
                    'id' => 1,
                ]
            ],
            $result
        );
    }

    public function testSimpleEntityWithPostAction()
    {
        $qb = $this->em->getRepository('Test:Group')->createQueryBuilder('e')
            ->where('e.id = :id')
            ->setParameter('id', 1);

        $this->setQueryExpectation(
            $this->getDriverConnectionMock($this->em),
            'SELECT o0_.id AS id_0, o0_.name AS name_1, o0_.label AS label_2, o0_.public AS public_3'
            . ' FROM oro_test_serializer_group o0_'
            . ' WHERE o0_.id = ?',
            [
                [
                    'id_0'     => 1,
                    'name_1'   => 'test_name',
                    'label_2'  => 'test_label',
                    'public_3' => 1,
                ]
            ],
            [1 => 1],
            [1 => \PDO::PARAM_INT]
        );

        $result = $this->serializer->serialize(
            $qb,
            [
                'post_serialize' => function (array &$result) {
                    $result['additional'] = $result['name'] . '_additional';
                }
            ]
        );

        $this->assertArrayEquals(
            [
                [
                    'id'         => 1,
                    'name'       => 'test_name',
                    'label'      => 'test_label',
                    'public'     => true,
                    'additional' => 'test_name_additional'
                ]
            ],
            $result
        );
    }
}
