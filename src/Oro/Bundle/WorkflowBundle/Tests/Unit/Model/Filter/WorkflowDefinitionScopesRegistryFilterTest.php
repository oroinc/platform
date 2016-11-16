<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Model\Filter;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\ScopeBundle\Manager\ScopeManager;
use Oro\Bundle\ScopeBundle\Model\ScopeCriteria;
use Oro\Bundle\WorkflowBundle\Entity\Repository\WorkflowDefinitionRepository;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;
use Oro\Bundle\WorkflowBundle\Model\Filter\WorkflowDefinitionScopesRegistryFilter;

class WorkflowDefinitionScopesRegistryFilterTest extends \PHPUnit_Framework_TestCase
{
    /** @var ScopeManager|\PHPUnit_Framework_MockObject_MockObject */
    private $scopeManager;

    /** @var WorkflowDefinitionRepository|\PHPUnit_Framework_MockObject_MockObject */
    private $definitionRepository;

    /** @var WorkflowDefinitionScopesRegistryFilter */
    private $filter;

    protected function setUp()
    {
        $this->scopeManager = $this->getMockBuilder(ScopeManager::class)
            ->disableOriginalConstructor()->getMock();
        $this->definitionRepository = $this->getMockBuilder(WorkflowDefinitionRepository::class)
            ->disableOriginalConstructor()->getMock();
        $this->filter = new WorkflowDefinitionScopesRegistryFilter($this->scopeManager, $this->definitionRepository);
    }

    /**
     * @dataProvider filterDataProvider
     * @param ArrayCollection $incomingDefinitions
     * @param array $scopedWorkflowNames
     * @param array $matchingScopesDefinitionsResult
     * @param ArrayCollection $expectedResult
     */
    public function testFilter(
        ArrayCollection $incomingDefinitions,
        array $scopedWorkflowNames,
        array $matchingScopesDefinitionsResult,
        ArrayCollection $expectedResult
    ) {
        $this->getScopeMatchedMocking($scopedWorkflowNames, $matchingScopesDefinitionsResult);
        $this->assertEquals($expectedResult, $this->filter->filter($incomingDefinitions));
    }

    /**
     * @param array $scopedWorkflowNames
     * @param array $result
     */
    private function getScopeMatchedMocking(array $scopedWorkflowNames, array $result)
    {
        $queryBuilder = $this->getMockBuilder(QueryBuilder::class)->disableOriginalConstructor()->getMock();
        $query = $this->getMockBuilder(AbstractQuery::class)
            ->disableOriginalConstructor()->setMethods(['getResult'])->getMockForAbstractClass();
        $scopeCriteria = $this->getMockBuilder(ScopeCriteria::class)->disableOriginalConstructor()->getMock();

        $this->definitionRepository->expects($this->once())
            ->method('getByNamesQueryBuilder')->with($scopedWorkflowNames)
            ->willReturn($queryBuilder);

        $queryBuilder->expects($this->once())
            ->method('join')->with('wd.scopes', 'scopes', Join::WITH)
            ->willReturnSelf();

        $this->scopeManager->expects($this->once())
            ->method('getCriteria')->with('workflow_definition')->willReturn($scopeCriteria);

        $scopeCriteria->expects($this->once())
            ->method('applyToJoinWithPriority')->with($queryBuilder, 'scopes');

        $queryBuilder->expects($this->once())
            ->method('getQuery')->willReturn($query);

        $query->expects($this->once())->method('getResult')->willReturn($result);
    }

    /**
     * @return array
     */
    public function filterDataProvider()
    {
        return [
            'full case' => [
                'definitions to filter' => new ArrayCollection(
                    [
                        'wd1' => (new WorkflowDefinition)->setName('wd1')->setScopesConfig([['a' => 1]]),
                        'wd2' => (new WorkflowDefinition)->setName('wd2')->setScopesConfig([['a' => 1]]),
                        'wd3' => (new WorkflowDefinition)->setName('wd3')
                    ]
                ),
                'has scope configs' => [
                    'wd1',
                    'wd2'
                ],
                'query result matched by scopes' => [
                    (new WorkflowDefinition)->setName('wd1')->setScopesConfig([['a' => 1]])
                ],
                'expected' => new ArrayCollection(
                    [
                        'wd1' => (new WorkflowDefinition)->setName('wd1')->setScopesConfig([['a' => 1]]),
                        'wd3' => (new WorkflowDefinition)->setName('wd3')
                    ]
                )
            ]
        ];
    }
}
