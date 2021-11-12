<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Handler;

use Doctrine\ORM\EntityManager;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;
use Oro\Bundle\WorkflowBundle\Handler\WorkflowVariablesHandler;
use Oro\Bundle\WorkflowBundle\Model\WorkflowData;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class WorkflowVariablesHandlerTest extends \PHPUnit\Framework\TestCase
{
    /** @var EntityManager|\PHPUnit\Framework\MockObject\MockObject */
    private $entityManager;

    /** @var EventDispatcherInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $eventDispatcher;

    /** @var ManagerRegistry|\PHPUnit\Framework\MockObject\MockObject */
    private $managerRegistry;

    /** @var WorkflowVariablesHandler */
    private $handler;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManager::class);
        $this->managerRegistry = $this->createMock(ManagerRegistry::class);
        $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);

        $this->managerRegistry->expects($this->any())
            ->method('getManagerForClass')
            ->willReturn($this->entityManager);

        $this->handler = new WorkflowVariablesHandler($this->eventDispatcher, $this->managerRegistry);
    }

    /**
     * @dataProvider updateWorkflowVariablesDataProvider
     */
    public function testUpdateWorkflowVariables(
        WorkflowDefinition $definition,
        WorkflowData $data,
        WorkflowDefinition $expectedDefinition
    ) {
        $this->entityManager->expects($this->once())
            ->method('persist');
        $this->entityManager->expects($this->once())
            ->method('flush');

        $this->assertEquals($this->handler->updateWorkflowVariables($definition, $data), $expectedDefinition);
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
