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
            'SELECT o0_.id AS id0, o0_.name AS name1, o0_.label AS label2, o0_.public AS public3'
            . ' FROM oro_test_serializer_group o0_'
            . ' WHERE o0_.id = ?',
            [
                [
                    'id0'     => 1,
                    'name1'   => 'test_name',
                    'label2'  => 'test_label',
                    'public3' => 1,
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
            'SELECT o0_.id AS id0, o0_.label AS label1, o0_.public AS public2'
            . ' FROM oro_test_serializer_group o0_'
            . ' WHERE o0_.id = ?',
            [
                [
                    'id0'     => 1,
                    'label1'  => 'test_label',
                    'public2' => 1,
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
            'SELECT o0_.id AS id0, o0_.name AS name1, o0_.label AS label2, o0_.public AS public3'
            . ' FROM oro_test_serializer_group o0_'
            . ' WHERE o0_.id = ?',
            [
                [
                    'id0'     => 1,
                    'name1'   => 'test_name',
                    'label2'  => null,
                    'public3' => 0,
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
            'SELECT o0_.id AS id0, o0_.name AS name1'
            . ' FROM oro_test_serializer_group o0_'
            . ' WHERE o0_.id = ?',
            [
                [
                    'id0'   => 1,
                    'name1' => 'test_name',
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
            'SELECT o0_.id AS id0'
            . ' FROM oro_test_serializer_group o0_'
            . ' WHERE o0_.id = ?',
            [
                [
                    'id0' => 1,
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
            'SELECT o0_.id AS id0, o0_.name AS name1, o0_.label AS label2, o0_.public AS public3'
            . ' FROM oro_test_serializer_group o0_'
            . ' WHERE o0_.id = ?',
            [
                [
                    'id0'     => 1,
                    'name1'   => 'test_name',
                    'label2'  => 'test_label',
                    'public3' => 1,
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
