<?php

namespace Oro\Bundle\FormBundle\Tests\Unit\EventListener;

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\Driver\AnnotationDriver;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\BatchBundle\Event\CountQueryOptimizationEvent;
use Oro\Bundle\BatchBundle\ORM\QueryBuilder\CountQueryBuilderOptimizer;
use Oro\Bundle\FormBundle\EventListener\CountQueryOptimizationListener;
use Oro\Bundle\FormBundle\Tests\Unit\Fixtures\Entity\Contact;
use Oro\Component\Testing\Unit\ORM\OrmTestCase;
use Symfony\Component\EventDispatcher\EventDispatcher;

class CountQueryOptimizationListenerTest extends OrmTestCase
{
    private EntityManagerInterface $em;

    protected function setUp(): void
    {
        $this->em = $this->getTestEntityManager();
        $this->em->getConfiguration()->setMetadataDriverImpl(new AnnotationDriver(new AnnotationReader()));
    }

    /**
     * @dataProvider getCountQueryBuilderDataProvider
     */
    public function testGetCountQueryBuilder(callable $queryBuilder, string $expectedDql)
    {
        $listener = new CountQueryOptimizationListener();
        $eventDispatcher = new EventDispatcher();
        $eventDispatcher->addListener(CountQueryOptimizationEvent::EVENT_NAME, [$listener, 'onOptimize']);

        $optimizer = new CountQueryBuilderOptimizer();
        $optimizer->setEventDispatcher($eventDispatcher);
        $countQb = $optimizer->getCountQueryBuilder($queryBuilder($this->em));

        $this->assertInstanceOf(QueryBuilder::class, $countQb);
        // Check for expected DQL
        $this->assertEquals($expectedDql, $countQb->getQuery()->getDQL());
        // Check that Optimized DQL can be converted to SQL
        $this->assertNotEmpty($countQb->getQuery()->getSQL());
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function getCountQueryBuilderDataProvider(): array
    {
        return [
            'primary_left_join_value=true'                   => [
                'queryBuilder' => function ($em) {
                    return (new QueryBuilder($em))
                        ->from(Contact::class, 'c')
                        ->leftJoin('c.emails', 'e', Join::WITH, 'e.primary = true')
                        ->select(['c.id']);
                },
                'expectedDQL'  => 'SELECT c.id FROM ' . Contact::class . ' c',
            ],
            'primary_inner_join_value=true'                  => [
                'queryBuilder' => function ($em) {
                    return (new QueryBuilder($em))
                        ->from(Contact::class, 'c')
                        ->innerJoin('c.emails', 'e', Join::WITH, 'e.primary = true')
                        ->select(['c.id']);
                },
                'expectedDQL'  => 'SELECT c.id FROM ' . Contact::class . ' c '
                    . 'INNER JOIN c.emails e WITH e.primary = true',
            ],
            'primary_left_join_value=1'                      => [
                'queryBuilder' => function ($em) {
                    return (new QueryBuilder($em))
                        ->from(Contact::class, 'c')
                        ->leftJoin('c.emails', 'e', Join::WITH, 'e.primary = 1')
                        ->select(['c.id']);
                },
                'expectedDQL'  => 'SELECT c.id FROM ' . Contact::class . ' c',
            ],
            'primary_left_join_value=named_parameter=true'   => [
                'queryBuilder' => function ($em) {
                    return (new QueryBuilder($em))
                        ->from(Contact::class, 'c')
                        ->leftJoin('c.emails', 'e', Join::WITH, 'e.primary = :primaryValue')
                        ->setParameter('primaryValue', true)
                        ->select(['c.id']);
                },
                'expectedDQL'  => 'SELECT c.id FROM ' . Contact::class . ' c',
            ],
            'primary_left_join_value=named_parameter=1'      => [
                'queryBuilder' => function ($em) {
                    return (new QueryBuilder($em))
                        ->from(Contact::class, 'c')
                        ->leftJoin('c.emails', 'e', Join::WITH, 'e.primary = :primaryValue')
                        ->setParameter('primaryValue', 1)
                        ->select(['c.id']);
                },
                'expectedDQL'  => 'SELECT c.id FROM ' . Contact::class . ' c',
            ],
            'primary_left_join_value=indexed_parameter=true' => [
                'queryBuilder' => function ($em) {
                    return (new QueryBuilder($em))
                        ->from(Contact::class, 'c')
                        ->leftJoin('c.emails', 'e', Join::WITH, 'e.primary = ?0')
                        ->setParameter(0, true)
                        ->select(['c.id']);
                },
                'expectedDQL'  => 'SELECT c.id FROM ' . Contact::class . ' c',
            ],
            'primary_left_join_value=indexed_parameter=1'    => [
                'queryBuilder' => function ($em) {
                    return (new QueryBuilder($em))
                        ->from(Contact::class, 'c')
                        ->leftJoin('c.emails', 'e', Join::WITH, 'e.primary = ?0')
                        ->setParameter(0, 1)
                        ->select(['c.id']);
                },
                'expectedDQL'  => 'SELECT c.id FROM ' . Contact::class . ' c',
            ],
            'primary_left_join_without_condition'            => [
                'queryBuilder' => function ($em) {
                    return (new QueryBuilder($em))
                        ->from(Contact::class, 'c')
                        ->leftJoin('c.emails', 'e')
                        ->select(['c.id']);
                },
                'expectedDQL'  => 'SELECT c.id FROM ' . Contact::class . ' c '
                    . 'LEFT JOIN c.emails e',
            ],
            'primary_left_join_complex_condition'            => [
                'queryBuilder' => function ($em) {
                    return (new QueryBuilder($em))
                        ->from(Contact::class, 'c')
                        ->leftJoin('c.emails', 'e', Join::WITH, 'e.primary = true AND e.id = 1')
                        ->select(['c.id']);
                },
                'expectedDQL'  => 'SELECT c.id FROM ' . Contact::class . ' c '
                    . 'LEFT JOIN c.emails e WITH e.primary = true AND e.id = 1',
            ],
            'primary_left_join_value=false'                  => [
                'queryBuilder' => function ($em) {
                    return (new QueryBuilder($em))
                        ->from(Contact::class, 'c')
                        ->leftJoin('c.emails', 'e', Join::WITH, 'e.primary = false')
                        ->select(['c.id']);
                },
                'expectedDQL'  => 'SELECT c.id FROM ' . Contact::class . ' c '
                    . 'LEFT JOIN c.emails e WITH e.primary = false',
            ],
            'primary_left_join_value=named_parameter=false'  => [
                'queryBuilder' => function ($em) {
                    return (new QueryBuilder($em))
                        ->from(Contact::class, 'c')
                        ->leftJoin('c.emails', 'e', Join::WITH, 'e.primary = :primaryValue')
                        ->setParameter('primaryValue', false)
                        ->select(['c.id']);
                },
                'expectedDQL'  => 'SELECT c.id FROM ' . Contact::class . ' c '
                    . 'LEFT JOIN c.emails e WITH e.primary = :primaryValue',
            ],
            'primary_left_join_value=true_where_by_email'    => [
                'queryBuilder' => function ($em) {
                    return (new QueryBuilder($em))
                        ->from(Contact::class, 'c')
                        ->leftJoin('c.emails', 'e', Join::WITH, 'e.primary = true')
                        ->where('e.email = :email')
                        ->setParameter('email', 'test@example.com')
                        ->select(['c.id']);
                },
                'expectedDQL'  => 'SELECT c.id FROM ' . Contact::class . ' c '
                    . 'LEFT JOIN c.emails e WITH e.primary = true '
                    . 'WHERE e.email = :email',
            ],
        ];
    }
}
