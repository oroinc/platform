<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Provider;

use Oro\Bundle\WorkflowBundle\Entity\WorkflowTransitionRecord;
use Oro\Bundle\WorkflowBundle\Provider\EntityVariablesProvider;

class EntityVariablesProviderTest extends \PHPUnit\Framework\TestCase
{
    /** @var EntityVariablesProvider */
    protected $provider;

    protected function setUp()
    {
        $this->provider = new EntityVariablesProvider();
    }

    /**
     * @dataProvider entityProvider
     *
     * @param string|null $entity
     */
    public function testGetVariableDefinitions($entity)
    {
        $this->assertEquals([], $this->provider->getVariableDefinitions($entity));
    }

    /**
     * @dataProvider entityProvider
     *
     * @param string|null $entity
     */
    public function testGetVariableGetters($entity)
    {
        $this->assertEquals(
            [
                WorkflowTransitionRecord::class => [
                    'id' => 'getId',
                    'workflowItem' => 'getWorkflowItem',
                    'transitionName' => 'getTransitionName',
                    'stepFrom' => 'getStepFrom',
                    'stepTo' => 'getStepTo',
                    'transitionDate' => 'getTransitionDate'
                ]
            ],
            $this->provider->getVariableGetters($entity)
        );
    }

    /**
     * @return array
     */
    public function entityProvider()
    {
        return [
            [null],
            [new \stdClass()]
        ];
    }
}
