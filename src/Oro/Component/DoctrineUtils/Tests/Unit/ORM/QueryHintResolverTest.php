<?php

namespace Oro\Component\DoctrineUtils\Tests\Unit\ORM;

use Doctrine\ORM\Query;
use Oro\Component\DoctrineUtils\ORM\QueryHintResolver;

class QueryHintResolverTest extends \PHPUnit_Framework_TestCase
{
    /** @var QueryHintResolver */
    protected $queryHintResolver;

    protected function setUp()
    {
        $this->queryHintResolver = new QueryHintResolver();
    }

    public function testAddHint()
    {
        $query = $this->getQuery();

        $added = $this->queryHintResolver->addHint(
            $query,
            'test',
            true
        );

        $this->assertTrue($added);
        $this->assertEquals(
            [
                'test' => true
            ],
            $query->getHints()
        );
    }

    public function testAddAlreadyExistingHint()
    {
        $query = $this->getQuery();
        $query->setHint('test', true);

        $added = $this->queryHintResolver->addHint(
            $query,
            'test',
            true
        );

        $this->assertTrue($added);
        $this->assertEquals(
            [
                'test' => true
            ],
            $query->getHints()
        );
    }

    public function testAddHintCustomOutputWalker()
    {
        $query = $this->getQuery();

        $added = $this->queryHintResolver->addHint(
            $query,
            Query::HINT_CUSTOM_OUTPUT_WALKER,
            'walker_class'
        );

        $this->assertTrue($added);
        $this->assertEquals(
            [
                Query::HINT_CUSTOM_OUTPUT_WALKER => 'walker_class'
            ],
            $query->getHints()
        );
    }

    public function testAddAlreadyExistingHintCustomOutputWalker()
    {
        $query = $this->getQuery();
        $query->setHint(Query::HINT_CUSTOM_OUTPUT_WALKER, 'walker_class');

        $added = $this->queryHintResolver->addHint(
            $query,
            Query::HINT_CUSTOM_OUTPUT_WALKER,
            'walker_class'
        );

        $this->assertFalse($added);
        $this->assertEquals(
            [
                Query::HINT_CUSTOM_OUTPUT_WALKER => 'walker_class'
            ],
            $query->getHints()
        );
    }

    public function testAddHintCustomTreeWalker()
    {
        $query = $this->getQuery();

        $added = $this->queryHintResolver->addHint(
            $query,
            Query::HINT_CUSTOM_TREE_WALKERS,
            'walker_class'
        );

        $this->assertTrue($added);
        $this->assertEquals(
            [
                Query::HINT_CUSTOM_TREE_WALKERS => ['walker_class']
            ],
            $query->getHints()
        );
    }

    public function testAddAlreadyExistingHintCustomTreeWalker()
    {
        $query = $this->getQuery();
        $query->setHint(Query::HINT_CUSTOM_TREE_WALKERS, ['walker_class']);

        $added = $this->queryHintResolver->addHint(
            $query,
            Query::HINT_CUSTOM_TREE_WALKERS,
            'walker_class'
        );

        $this->assertFalse($added);
        $this->assertEquals(
            [
                Query::HINT_CUSTOM_TREE_WALKERS => ['walker_class']
            ],
            $query->getHints()
        );
    }

    public function testAddHintCustomTreeWalkerWithExistingAnotherWalker()
    {
        $query = $this->getQuery();
        $query->setHint(Query::HINT_CUSTOM_TREE_WALKERS, ['another_walker_class']);

        $added = $this->queryHintResolver->addHint(
            $query,
            Query::HINT_CUSTOM_TREE_WALKERS,
            'walker_class'
        );

        $this->assertTrue($added);
        $this->assertEquals(
            [
                Query::HINT_CUSTOM_TREE_WALKERS => ['another_walker_class', 'walker_class']
            ],
            $query->getHints()
        );
    }

    public function testAddHints()
    {
        $query = $this->getQuery();
        $query->setHint(Query::HINT_CUSTOM_TREE_WALKERS, ['another_walker_class']);

        $this->queryHintResolver->addHints(
            $query,
            [
                'HINT_REFRESH',
                ['name' => Query::HINT_CUSTOM_TREE_WALKERS, 'value' => 'walker_class']
            ]
        );

        $this->assertEquals(
            [
                Query::HINT_CUSTOM_TREE_WALKERS => ['another_walker_class', 'walker_class'],
                Query::HINT_REFRESH             => true
            ],
            $query->getHints()
        );
    }

    public function testAddHintsWithParameters()
    {
        $query = $this->getQuery();
        $query->setParameter('parameter_name', 10);
        $query->setParameter('parameter_name2', 100);

        $this->queryHintResolver->addHints(
            $query,
            [
                ['name' => 'HINT_WITH_PARAMETER', 'value' => ':parameter_name'],
                ['name' => 'HINT_WITH_PARAMETER2', 'value' => ['id' => ':parameter_name2']]
            ]
        );

        $this->assertEquals(
            [
                'HINT_WITH_PARAMETER' => 10,
                'HINT_WITH_PARAMETER2' => ['id' => 100]
            ],
            $query->getHints()
        );
    }

    public function testResolveHints()
    {
        $query = $this->getQuery();
        $query->setHint(Query::HINT_CUSTOM_TREE_WALKERS, ['another_walker_class']);
        $query->setHint('test_output', true);

        $this->queryHintResolver->addOutputWalker('test_output', 'Test\OutputWalker', null, 'HINT_1');

        $walkerHintProvider = $this->createMock('Oro\Component\DoctrineUtils\ORM\QueryWalkerHintProviderInterface');
        $this->queryHintResolver->addTreeWalker('test_tree', 'Test\TreeWalker', $walkerHintProvider, 'HINT_2');
        $walkerHintProvider->expects($this->once())
            ->method('getHints')
            ->with('hint_2_param')
            ->willReturn(['test.tree_walker.hint' => 'val1']);

        $this->queryHintResolver->resolveHints(
            $query,
            [
                ['name' => 'HINT_2', 'value' => 'hint_2_param'],
                'HINT_REFRESH',
                ['name' => Query::HINT_CUSTOM_TREE_WALKERS, 'value' => 'walker_class']
            ]
        );

        $this->assertEquals(
            [
                'test_output'                    => true,
                'test_tree'                      => 'hint_2_param',
                Query::HINT_CUSTOM_TREE_WALKERS  => ['another_walker_class', 'walker_class', 'Test\TreeWalker'],
                Query::HINT_REFRESH              => true,
                Query::HINT_CUSTOM_OUTPUT_WALKER => 'Test\OutputWalker',
                'test.tree_walker.hint'          => 'val1'
            ],
            $query->getHints()
        );
    }

    public function testResolveCustomHintName()
    {
        $this->queryHintResolver->addTreeWalker('test', 'Test\Walker', null, 'HINT_TEST');

        $this->assertEquals(
            'test',
            $this->queryHintResolver->resolveHintName('HINT_TEST')
        );
        $this->assertEquals(
            'test',
            $this->queryHintResolver->resolveHintName('test')
        );
    }

    public function testResolveDoctrineHintName()
    {
        $this->assertEquals(
            Query::HINT_CUSTOM_OUTPUT_WALKER,
            $this->queryHintResolver->resolveHintName('HINT_CUSTOM_OUTPUT_WALKER')
        );
        $this->assertEquals(
            Query::HINT_CUSTOM_OUTPUT_WALKER,
            $this->queryHintResolver->resolveHintName(Query::HINT_CUSTOM_OUTPUT_WALKER)
        );
    }

    public function testResolveUndefinedHintName()
    {
        $this->assertEquals(
            'HINT_UNDEFINED',
            $this->queryHintResolver->resolveHintName('HINT_UNDEFINED')
        );
    }

    /**
     * @return Query
     */
    protected function getQuery()
    {
        $configuration = $this->getMockBuilder('Doctrine\ORM\Configuration')
            ->disableOriginalConstructor()
            ->getMock();
        $configuration->expects($this->any())
            ->method('getDefaultQueryHints')
            ->will($this->returnValue([]));
        $configuration->expects($this->any())
            ->method('isSecondLevelCacheEnabled')
            ->will($this->returnValue(false));

        $em = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $em->expects($this->any())
            ->method('getConfiguration')
            ->will($this->returnValue($configuration));

        return new Query($em);
    }
}
