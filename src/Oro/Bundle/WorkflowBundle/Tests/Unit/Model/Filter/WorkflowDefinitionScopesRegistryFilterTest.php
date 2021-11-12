<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Model\Filter;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\ScopeBundle\Manager\ScopeManager;
use Oro\Bundle\ScopeBundle\Model\ScopeCriteria;
use Oro\Bundle\WorkflowBundle\Entity\Repository\WorkflowDefinitionRepository;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;
use Oro\Bundle\WorkflowBundle\Model\Filter\WorkflowDefinitionScopesRegistryFilter;

class WorkflowDefinitionScopesRegistryFilterTest extends \PHPUnit\Framework\TestCase
{
    /** @var ScopeManager|\PHPUnit\Framework\MockObject\MockObject */
    private $scopeManager;

    /** @var ManagerRegistry|\PHPUnit\Framework\MockObject\MockObject */
    private $doctrine;

    /** @var WorkflowDefinitionScopesRegistryFilter */
    private $filter;

    protected function setUp(): void
    {
        $this->scopeManager = $this->createMock(ScopeManager::class);
        $this->doctrine = $this->createMock(ManagerRegistry::class);

        $this->filter = new WorkflowDefinitionScopesRegistryFilter($this->scopeManager, $this->doctrine);
    }

    /**
     * @dataProvider filterDataProvider
     */
    public function testFilter(
        ArrayCollection $incomingDefinitions,
        array $scopedWorkflowNames,
        array $matchingScopesDefinitionsResult,
        ArrayCollection $expectedResult
    ) {
        $scopeCriteria = $this->createMock(ScopeCriteria::class);

        $this->scopeManager->expects($this->once())
            ->method('getCriteria')
            ->with('workflow_definition')
            ->willReturn($scopeCriteria);

        $this->repositoryMocked()->expects($this->once())
            ->method('getScopedByNames')
            ->with($scopedWorkflowNames, $scopeCriteria)
            ->willReturn($matchingScopesDefinitionsResult);

        $this->assertSame(
            $expectedResult->toArray(),
            $this->filter->filter($incomingDefinitions)->toArray(),
            'Entities and order should be kept.'
        );
    }

    /**
     * @return WorkflowDefinitionRepository|\PHPUnit\Framework\MockObject\MockObject
     */
    private function repositoryMocked()
    {
        $repository = $this->createMock(WorkflowDefinitionRepository::class);

        $manager = $this->createMock(ObjectManager::class);

        $this->doctrine->expects($this->once())
            ->method('getManagerForClass')
            ->with(WorkflowDefinition::class)
            ->willReturn($manager);

        $manager->expects($this->once())
            ->method('getRepository')
            ->with(WorkflowDefinition::class)
            ->willReturn($repository);

        return $repository;
    }

    public function filterDataProvider(): array
    {
        $wd1 = (new WorkflowDefinition)->setName('wd1')->setConfiguration(['scopes' => ['a' => 1]]);
        $wd2 = (new WorkflowDefinition)->setName('wd2')->setConfiguration(['scopes' => ['a' => 1]]);
        $wd3 = (new WorkflowDefinition)->setName('wd3');

        return [
            'full case' => [
                'definitions to filter' => new ArrayCollection(
                    [
                        'wd1' => $wd1,
                        'wd2' => $wd2,
                        'wd3' => $wd3
                    ]
                ),
                'has scope configs' => [
                    'wd1',
                    'wd2'
                ],
                'query result matched by scopes' => [
                    $wd1
                ],
                'expected' => new ArrayCollection(
                    [
                        'wd1' => $wd1,
                        'wd3' => $wd3
                    ]
                )
            ]
        ];
    }
}
