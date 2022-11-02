<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Configuration\Handler;

use Oro\Bundle\WorkflowBundle\Configuration\Handler\ConfigurationHandlerInterface;
use Oro\Bundle\WorkflowBundle\Configuration\Handler\WorkflowHandler;
use Oro\Bundle\WorkflowBundle\Configuration\WorkflowConfiguration;

class WorkflowHandlerTest extends \PHPUnit\Framework\TestCase
{
    /** @var WorkflowHandler */
    private $handler;

    protected function setUp(): void
    {
        $this->handler = new WorkflowHandler();
    }

    /**
     * @dataProvider handleDataProvider
     */
    public function testHandle(array $expected, array $input)
    {
        $otherHandler = $this->createMock(ConfigurationHandlerInterface::class);
        $otherHandler->expects($this->once())
            ->method('handle')
            ->with($expected)
            ->willReturn($expected);

        $this->handler->addHandler($otherHandler);

        $this->assertEquals($expected, $this->handler->handle($input));
    }

    public function handleDataProvider(): array
    {
        return [
            'simple configuration' => [
                'expected' => [
                    'name' => 'test_workflow',
                    'label' => 'Test Workflow',
                    'entity' => \DateTime::class,
                ],
                'input' => [
                    'name' => 'test_workflow',
                    'label' => 'Test Workflow',
                    'entity' => \DateTime::class,
                ],
            ],
            'filtered configuration' => [
                'expected' => [
                    'name' => 'test_workflow',
                    'entity' => \DateTime::class,
                    'label' => 'Test Workflow',
                    'is_system' => false,
                    'start_step' => null,
                    'entity_attribute' => 'entity',
                    'steps_display_ordered' => true,
                    WorkflowConfiguration::NODE_STEPS => [],
                    WorkflowConfiguration::NODE_ATTRIBUTES => [],
                    WorkflowConfiguration::NODE_TRANSITIONS => [],
                    WorkflowConfiguration::NODE_TRANSITION_DEFINITIONS => [],
                ],
                'input' => [
                    'name' => 'test_workflow',
                    'label' => 'Test Workflow',
                    'entity' => \DateTime::class,
                    'is_system' => false,
                    'start_step' => null,
                    'entity_attribute' => 'entity',
                    'steps_display_ordered' => true,
                    WorkflowConfiguration::NODE_STEPS => [],
                    WorkflowConfiguration::NODE_ATTRIBUTES => [],
                    WorkflowConfiguration::NODE_TRANSITIONS => [],
                    WorkflowConfiguration::NODE_TRANSITION_DEFINITIONS => [],
                ],
            ]
        ];
    }

    public function testHandleEmptyConfiguration()
    {
        $configuration = [
            'entity' => 'NotExistingEntity',
        ];

        $result = $this->handler->handle($configuration);

        $this->assertArrayHasKey('name', $result);
        $this->assertArrayNotHasKey('entity', $result);

        $this->assertStringStartsWith('workflow_', $result['name']);
    }
}
