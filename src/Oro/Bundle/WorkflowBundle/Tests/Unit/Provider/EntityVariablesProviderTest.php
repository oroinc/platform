<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Provider;

use Oro\Bundle\WorkflowBundle\Entity\WorkflowTransitionRecord;
use Oro\Bundle\WorkflowBundle\Provider\EntityVariablesProvider;
use PHPUnit\Framework\TestCase;

class EntityVariablesProviderTest extends TestCase
{
    private EntityVariablesProvider $provider;

    #[\Override]
    protected function setUp(): void
    {
        $this->provider = new EntityVariablesProvider();
    }

    public function testGetVariableDefinitions(): void
    {
        $this->assertSame([], $this->provider->getVariableDefinitions());
    }

    public function testGetVariableGetters(): void
    {
        $this->assertEquals(
            [
                WorkflowTransitionRecord::class => [
                    'id'             => 'getId',
                    'workflowItem'   => 'getWorkflowItem',
                    'transitionName' => 'getTransitionName',
                    'stepFrom'       => 'getStepFrom',
                    'stepTo'         => 'getStepTo',
                    'transitionDate' => 'getTransitionDate'
                ]
            ],
            $this->provider->getVariableGetters()
        );
    }

    public function testGetVariableProcessors(): void
    {
        self::assertSame([], $this->provider->getVariableProcessors(WorkflowTransitionRecord::class));
    }
}
