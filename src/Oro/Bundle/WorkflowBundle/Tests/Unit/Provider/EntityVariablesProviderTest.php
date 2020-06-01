<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Provider;

use Oro\Bundle\WorkflowBundle\Entity\WorkflowTransitionRecord;
use Oro\Bundle\WorkflowBundle\Provider\EntityVariablesProvider;

class EntityVariablesProviderTest extends \PHPUnit\Framework\TestCase
{
    /** @var EntityVariablesProvider */
    private $provider;

    protected function setUp(): void
    {
        $this->provider = new EntityVariablesProvider();
    }

    public function testGetVariableDefinitions()
    {
        $this->assertSame([], $this->provider->getVariableDefinitions());
    }

    public function testGetVariableGetters()
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

    public function testGetVariableProcessors()
    {
        self::assertSame([], $this->provider->getVariableProcessors(WorkflowTransitionRecord::class));
    }
}
