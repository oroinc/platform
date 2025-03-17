<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Handler;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;
use Oro\Bundle\WorkflowBundle\Handler\WorkflowVariablesHandler;
use Oro\Bundle\WorkflowBundle\Model\WorkflowData;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class WorkflowVariablesHandlerTest extends TestCase
{
    private EntityManagerInterface&MockObject $entityManager;
    private WorkflowVariablesHandler $handler;

    #[\Override]
    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);

        $doctrine = $this->createMock(ManagerRegistry::class);
        $doctrine->expects(self::any())
            ->method('getManagerForClass')
            ->willReturn($this->entityManager);

        $this->handler = new WorkflowVariablesHandler($doctrine);
    }

    /**
     * @dataProvider updateWorkflowVariablesDataProvider
     */
    public function testUpdateWorkflowVariables(
        WorkflowDefinition $definition,
        WorkflowData $data,
        WorkflowDefinition $expectedDefinition
    ): void {
        $this->entityManager->expects(self::once())
            ->method('persist');
        $this->entityManager->expects(self::once())
            ->method('flush');

        self::assertEquals($this->handler->updateWorkflowVariables($definition, $data), $expectedDefinition);
    }

    public function updateWorkflowVariablesDataProvider(): array
    {
        $definition = new WorkflowDefinition();
        $definition->setConfiguration([
            'variable_definitions' => [
                'variables' => [
                    'variable1' => [
                        'type' => 'array',
                        'value' => [1, 2],
                    ],
                    'variable2' => [
                        'type' => 'string',
                        'value' => 'variable2Value',
                    ],
                ],
            ],
            'other_fields' => [],
        ]);

        $data = new WorkflowData([
            'variable1' => [3, 4],
            'variable2' => 'variable2NewValue',
            'not_existing_variable' => 9001,
        ]);

        $expected = new WorkflowDefinition();
        $expected->setConfiguration([
            'variable_definitions' => [
                'variables' => [
                    'variable1' => [
                        'type' => 'array',
                        'value' => [3, 4],
                    ],
                    'variable2' => [
                        'type' => 'string',
                        'value' => 'variable2NewValue',
                    ],
                ],
            ],
            'other_fields' => [],
        ]);

        return [
            [$definition, $data, $expected],
        ];
    }
}
