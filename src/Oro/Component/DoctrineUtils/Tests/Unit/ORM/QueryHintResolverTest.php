<?php

namespace Oro\Component\DoctrineUtils\Tests\Unit\ORM;

use Doctrine\ORM\Configuration;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query;
use Oro\Component\DoctrineUtils\ORM\QueryHintResolver;
use Oro\Component\DoctrineUtils\ORM\QueryWalkerHintProviderInterface;
use Oro\Component\Testing\Unit\TestContainerBuilder;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class QueryHintResolverTest extends \PHPUnit\Framework\TestCase
{
    private function getQueryHintResolver(
        array $walkers = [],
        array $providers = [],
        array $aliases = []
    ): QueryHintResolver {
        $containerBuilder = TestContainerBuilder::create();
        foreach ($providers as $id => $provider) {
            $containerBuilder->add($id, $provider);
        }

        return new QueryHintResolver(
            $walkers,
            $containerBuilder->getContainer($this),
            $aliases
        );
    }

    private function getQuery(): Query
    {
        $configuration = $this->createMock(Configuration::class);
        $configuration->expects($this->any())
            ->method('getDefaultQueryHints')
            ->willReturn([]);
        $configuration->expects($this->any())
            ->method('isSecondLevelCacheEnabled')
            ->willReturn(false);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->any())
            ->method('getConfiguration')
            ->willReturn($configuration);

        return new Query($em);
    }

    public function testAddHint()
    {
        $query = $this->getQuery();

        $queryHintResolver = $this->getQueryHintResolver();
        $added = $queryHintResolver->addHint(
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

        $queryHintResolver = $this->getQueryHintResolver();
        $added = $queryHintResolver->addHint(
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

        $queryHintResolver = $this->getQueryHintResolver();
        $added = $queryHintResolver->addHint(
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

        $queryHintResolver = $this->getQueryHintResolver();
        $added = $queryHintResolver->addHint(
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

        $queryHintResolver = $this->getQueryHintResolver();
        $added = $queryHintResolver->addHint(
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

        $queryHintResolver = $this->getQueryHintResolver();
        $added = $queryHintResolver->addHint(
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

        $queryHintResolver = $this->getQueryHintResolver();
        $added = $queryHintResolver->addHint(
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

        $queryHintResolver = $this->getQueryHintResolver();
        $queryHintResolver->addHints(
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

        $queryHintResolver = $this->getQueryHintResolver();
        $queryHintResolver->addHints(
            $query,
            [
                ['name' => 'HINT_WITH_PARAMETER', 'value' => ':parameter_name'],
                ['name' => 'HINT_WITH_PARAMETER2', 'value' => ['id' => ':parameter_name2']]
            ]
        );

        $this->assertEquals(
            [
                'HINT_WITH_PARAMETER'  => 10,
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

        $walkerHintProvider = $this->createMock(QueryWalkerHintProviderInterface::class);
        $walkerHintProvider->expects($this->once())
            ->method('getHints')
            ->with('hint_2_param')
            ->willReturn(['test.tree_walker.hint' => 'val1']);

        $queryHintResolver = $this->getQueryHintResolver(
            [
                'test_output' => [
                    'class'         => 'Test\OutputWalker',
                    'output'        => true,
                    'hint_provider' => null
                ],
                'test_tree'   => [
                    'class'         => 'Test\TreeWalker',
                    'output'        => false,
                    'hint_provider' => 'test_tree_provider'
                ]
            ],
            ['test_tree_provider' => $walkerHintProvider],
            ['HINT_1' => 'test_output', 'HINT_2' => 'test_tree']
        );

        $queryHintResolver->resolveHints(
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
        $queryHintResolver = $this->getQueryHintResolver(
            [
                'test_tree' => [
                    'class'         => 'Test\Walker',
                    'output'        => false,
                    'hint_provider' => null
                ]
            ],
            [],
            ['HINT_TEST' => 'test']
        );

        $this->assertEquals(
            'test',
            $queryHintResolver->resolveHintName('HINT_TEST')
        );
        $this->assertEquals(
            'test',
            $queryHintResolver->resolveHintName('test')
        );
    }

    public function testResolveDoctrineHintName()
    {
        $queryHintResolver = $this->getQueryHintResolver();
        $this->assertEquals(
            Query::HINT_CUSTOM_OUTPUT_WALKER,
            $queryHintResolver->resolveHintName('HINT_CUSTOM_OUTPUT_WALKER')
        );
        $this->assertEquals(
            Query::HINT_CUSTOM_OUTPUT_WALKER,
            $queryHintResolver->resolveHintName(Query::HINT_CUSTOM_OUTPUT_WALKER)
        );
    }

    public function testResolveUndefinedHintName()
    {
        $queryHintResolver = $this->getQueryHintResolver();
        $this->assertEquals(
            'HINT_UNDEFINED',
            $queryHintResolver->resolveHintName('HINT_UNDEFINED')
        );
    }
}
