<?php

namespace Oro\Bundle\FormBundle\Tests\Unit\EventListener;

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\Driver\AnnotationDriver;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;

use Symfony\Component\EventDispatcher\EventDispatcher;

use Oro\Bundle\BatchBundle\Event\CountQueryOptimizationEvent;
use Oro\Bundle\FormBundle\EventListener\CountQueryOptimizationListener;
use Oro\Component\TestUtils\ORM\Mocks\EntityManagerMock;
use Oro\Component\TestUtils\ORM\OrmTestCase;
use Oro\Bundle\BatchBundle\ORM\QueryBuilder\CountQueryBuilderOptimizer;

class CountQueryOptimizationListenerTest extends OrmTestCase
{
    /** @var EntityManagerMock */
    private $em;

    protected function setUp()
    {
        $metadataDriver = new AnnotationDriver(
            new AnnotationReader(),
            __DIR__ . '/../Fixtures/Entity'
        );

        $this->em = $this->getTestEntityManager();
        $this->em->getConfiguration()->setMetadataDriverImpl($metadataDriver);
        $this->em->getConfiguration()->setEntityNamespaces(
            [
                'Test' => 'Oro\Bundle\FormBundle\Tests\Unit\Fixtures\Entity'
            ]
        );
    }

    /**
     * @dataProvider getCountQueryBuilderDataProvider
     *
     * @param callback $queryBuilder
     * @param string   $expectedDql
     */
    public function testGetCountQueryBuilder($queryBuilder, $expectedDql)
    {
        $listener        = new CountQueryOptimizationListener();
        $eventDispatcher = new EventDispatcher();
        $eventDispatcher->addListener(CountQueryOptimizationEvent::EVENT_NAME, [$listener, 'onOptimize']);

        $optimizer = new CountQueryBuilderOptimizer();
        $optimizer->setEventDispatcher($eventDispatcher);
        $countQb = $optimizer->getCountQueryBuilder(call_user_func($queryBuilder, $this->em));

        $this->assertInstanceOf('Doctrine\ORM\QueryBuilder', $countQb);
        // Check for expected DQL
        $this->assertEquals($expectedDql, $countQb->getQuery()->getDQL());
        // Check that Optimized DQL can be converted to SQL
        $this->assertNotEmpty($countQb->getQuery()->getSQL());
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     * @return array
     */
    public function getCountQueryBuilderDataProvider()
    {
        return [
            'primary_left_join_value=true'                   => [
                'queryBuilder' => function ($em) {
                    return self::createQueryBuilder($em)
                        ->from('Test:Contact', 'c')
                        ->leftJoin('c.emails', 'e', Join::WITH, 'e.primary = true')
                        ->select(['c.id']);
                },
                'expectedDQL'  => 'SELECT c.id FROM Test:Contact c',
            ],
            'primary_inner_join_value=true'                  => [
                'queryBuilder' => function ($em) {
                    return self::createQueryBuilder($em)
                        ->from('Test:Contact', 'c')
                        ->innerJoin('c.emails', 'e', Join::WITH, 'e.primary = true')
                        ->select(['c.id']);
                },
                'expectedDQL'  => 'SELECT c.id FROM Test:Contact c '
                    . 'INNER JOIN c.emails e WITH e.primary = true',
            ],
            'primary_left_join_value=1'                      => [
                'queryBuilder' => function ($em) {
                    return self::createQueryBuilder($em)
                        ->from('Test:Contact', 'c')
                        ->leftJoin('c.emails', 'e', Join::WITH, 'e.primary = 1')
                        ->select(['c.id']);
                },
                'expectedDQL'  => 'SELECT c.id FROM Test:Contact c',
            ],
            'primary_left_join_value=named_parameter=true'   => [
                'queryBuilder' => function ($em) {
                    return self::createQueryBuilder($em)
                        ->from('Test:Contact', 'c')
                        ->leftJoin('c.emails', 'e', Join::WITH, 'e.primary = :primaryValue')
                        ->setParameter('primaryValue', true)
                        ->select(['c.id']);
                },
                'expectedDQL'  => 'SELECT c.id FROM Test:Contact c',
            ],
            'primary_left_join_value=named_parameter=1'      => [
                'queryBuilder' => function ($em) {
                    return self::createQueryBuilder($em)
                        ->from('Test:Contact', 'c')
                        ->leftJoin('c.emails', 'e', Join::WITH, 'e.primary = :primaryValue')
                        ->setParameter('primaryValue', 1)
                        ->select(['c.id']);
                },
                'expectedDQL'  => 'SELECT c.id FROM Test:Contact c',
            ],
            'primary_left_join_value=indexed_parameter=true' => [
                'queryBuilder' => function ($em) {
                    return self::createQueryBuilder($em)
                        ->from('Test:Contact', 'c')
                        ->leftJoin('c.emails', 'e', Join::WITH, 'e.primary = ?0')
                        ->setParameter(0, true)
                        ->select(['c.id']);
                },
                'expectedDQL'  => 'SELECT c.id FROM Test:Contact c',
            ],
            'primary_left_join_value=indexed_parameter=1'    => [
                'queryBuilder' => function ($em) {
                    return self::createQueryBuilder($em)
                        ->from('Test:Contact', 'c')
                        ->leftJoin('c.emails', 'e', Join::WITH, 'e.primary = ?0')
                        ->setParameter(0, 1)
                        ->select(['c.id']);
                },
                'expectedDQL'  => 'SELECT c.id FROM Test:Contact c',
            ],
            'primary_left_join_without_condition'            => [
                'queryBuilder' => function ($em) {
                    return self::createQueryBuilder($em)
                        ->from('Test:Contact', 'c')
                        ->leftJoin('c.emails', 'e')
                        ->select(['c.id']);
                },
                'expectedDQL'  => 'SELECT c.id FROM Test:Contact c '
                    . 'LEFT JOIN c.emails e',
            ],
            'primary_left_join_complex_condition'            => [
                'queryBuilder' => function ($em) {
                    return self::createQueryBuilder($em)
                        ->from('Test:Contact', 'c')
                        ->leftJoin('c.emails', 'e', Join::WITH, 'e.primary = true AND e.id = 1')
                        ->select(['c.id']);
                },
                'expectedDQL'  => 'SELECT c.id FROM Test:Contact c '
                    . 'LEFT JOIN c.emails e WITH e.primary = true AND e.id = 1',
            ],
            'primary_left_join_value=false'                  => [
                'queryBuilder' => function ($em) {
                    return self::createQueryBuilder($em)
                        ->from('Test:Contact', 'c')
                        ->leftJoin('c.emails', 'e', Join::WITH, 'e.primary = false')
                        ->select(['c.id']);
                },
                'expectedDQL'  => 'SELECT c.id FROM Test:Contact c '
                    . 'LEFT JOIN c.emails e WITH e.primary = false',
            ],
            'primary_left_join_value=named_parameter=false'  => [
                'queryBuilder' => function ($em) {
                    return self::createQueryBuilder($em)
                        ->from('Test:Contact', 'c')
                        ->leftJoin('c.emails', 'e', Join::WITH, 'e.primary = :primaryValue')
                        ->setParameter('primaryValue', false)
                        ->select(['c.id']);
                },
                'expectedDQL'  => 'SELECT c.id FROM Test:Contact c '
                    . 'LEFT JOIN c.emails e WITH e.primary = :primaryValue',
            ],
            'primary_left_join_value=true_where_by_email'    => [
                'queryBuilder' => function ($em) {
                    return self::createQueryBuilder($em)
                        ->from('Test:Contact', 'c')
                        ->leftJoin('c.emails', 'e', Join::WITH, 'e.primary = true')
                        ->where('e.email = :email')
                        ->setParameter('email', 'test@example.com')
                        ->select(['c.id']);
                },
                'expectedDQL'  => 'SELECT c.id FROM Test:Contact c '
                    . 'LEFT JOIN c.emails e WITH e.primary = true '
                    . 'WHERE e.email = :email',
            ],
        ];
    }

    /**
     * @param EntityManager $entityManager
     *
     * @return QueryBuilder
     */
    public static function createQueryBuilder(EntityManager $entityManager)
    {
        return new QueryBuilder($entityManager);
    }
}
