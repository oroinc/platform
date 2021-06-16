<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\EventListener;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query\Expr;
use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\DataGridBundle\Event\OrmResultBeforeQuery;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureChecker;
use Oro\Bundle\WorkflowBundle\EventListener\GridsSubscriber;

class GridSubscriberTest extends \PHPUnit\Framework\TestCase
{
    /** @var GridsSubscriber|\PHPUnit\Framework\MockObject\MockObject */
    private $gridSubscriber;

    /** @var FeatureChecker|\PHPUnit\Framework\MockObject\MockObject */
    private $featurechecker;

    /** @var QueryBuilder */
    private $queryBuilder;

    /** @var OrmResultBeforeQuery|\PHPUnit\Framework\MockObject\MockObject */
    private $event;

    protected function setUp(): void
    {
        $this->featurechecker = $this->createMock(FeatureChecker::class);
        $this->gridSubscriber = new GridsSubscriber($this->featurechecker);

        $this->queryBuilder = $this->getQueryBuilder();

        $this->event = $this->createMock(OrmResultBeforeQuery::class);

        $this->event->expects($this->any())
            ->method('getQueryBuilder')
            ->willReturn($this->queryBuilder);
    }

    public function testOnWorkflowsResultBeforeQueryWithDisabledEntities()
    {
        $disabledEntities = [
            'Acme\Bundle\TestBundle\AcmeEntity',
            'Acme\Bundle\TestBundle\TestEntity'
        ];

        $this->featurechecker->expects($this->once())
            ->method('getDisabledResourcesByType')
            ->with('entities')
            ->willReturn($disabledEntities);

        $this->gridSubscriber->onWorkflowsResultBeforeQuery($this->event);

        $this->assertEquals(
            'w.relatedEntity NOT IN(:relatedEntities)',
            (string) $this->queryBuilder->getDQLPart('where')
        );
        $this->assertEquals($disabledEntities, $this->queryBuilder->getParameter('relatedEntities')->getValue());
    }

    public function testOnWorkflowsResultBeforeQueryWithoutDisabledEntities()
    {
        $this->featurechecker->expects($this->once())
            ->method('getDisabledResourcesByType')
            ->with('entities')
            ->willReturn([]);

        $this->gridSubscriber->onWorkflowsResultBeforeQuery($this->event);

        $this->assertEquals('', (string) $this->queryBuilder->getDQLPart('where'));
    }

    public function testOnProcessesResultBeforeQueryWithDisabledProcesses()
    {
        $disabledProcesses = ['activate_update_territory_assignment'];
        $this->featurechecker->expects($this->once())
            ->method('getDisabledResourcesByType')
            ->with('processes')
            ->willReturn($disabledProcesses);

        $this->gridSubscriber->onProcessesResultBeforeQuery($this->event);

        $this->assertEquals('process.name NOT IN(:processes)', (string) $this->queryBuilder->getDQLPart('where'));
        $this->assertEquals($disabledProcesses, $this->queryBuilder->getParameter('processes')->getValue());
    }

    public function testOnProcessesResultBeforeQueryWithoutDisabledProcesses()
    {
        $this->featurechecker->expects($this->once())
            ->method('getDisabledResourcesByType')
            ->with('processes')
            ->willReturn([]);

        $this->gridSubscriber->onProcessesResultBeforeQuery($this->event);

        $this->assertEquals('', (string) $this->queryBuilder->getDQLPart('where'));
    }

    private function getQueryBuilder(): QueryBuilder
    {
        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->any())
            ->method('getExpressionBuilder')
            ->willReturn(new Expr());

        return new QueryBuilder($em);
    }
}
