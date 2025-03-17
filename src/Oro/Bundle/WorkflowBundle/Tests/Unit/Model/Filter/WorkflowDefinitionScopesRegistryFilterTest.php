<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Model\Filter;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\ScopeBundle\Manager\ScopeManager;
use Oro\Bundle\ScopeBundle\Model\ScopeCriteria;
use Oro\Bundle\WorkflowBundle\Entity\Repository\WorkflowDefinitionRepository;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;
use Oro\Bundle\WorkflowBundle\Model\Filter\WorkflowDefinitionScopesRegistryFilter;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class WorkflowDefinitionScopesRegistryFilterTest extends TestCase
{
    private ScopeManager&MockObject $scopeManager;
    private ManagerRegistry&MockObject $doctrine;
    private WorkflowDefinitionScopesRegistryFilter $filter;

    #[\Override]
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
    ): void {
        $scopeCriteria = $this->createMock(ScopeCriteria::class);

        $this->scopeManager->expects(self::once())
            ->method('getCriteria')
            ->with('workflow_definition')
            ->willReturn($scopeCriteria);

        $this->repositoryMocked()->expects(self::once())
            ->method('getScopedByNames')
            ->with($scopedWorkflowNames, $scopeCriteria)
            ->willReturn($matchingScopesDefinitionsResult);

        self::assertSame(
            $expectedResult->toArray(),
            $this->filter->filter($incomingDefinitions)->toArray(),
            'Entities and order should be kept.'
        );
    }

    private function repositoryMocked(): WorkflowDefinitionRepository&MockObject
    {
        $repository = $this->createMock(WorkflowDefinitionRepository::class);
        $this->doctrine->expects(self::once())
            ->method('getRepository')
            ->with(WorkflowDefinition::class)
            ->willReturn($repository);

        return $repository;
    }

    public function filterDataProvider(): array
    {
        $wd1 = (new WorkflowDefinition())->setName('wd1')->setConfiguration(['scopes' => ['a' => 1]]);
        $wd2 = (new WorkflowDefinition())->setName('wd2')->setConfiguration(['scopes' => ['a' => 1]]);
        $wd3 = (new WorkflowDefinition())->setName('wd3');

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
