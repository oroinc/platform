<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Translation\KeySource;

use Oro\Bundle\WorkflowBundle\Model\Workflow;
use Oro\Bundle\WorkflowBundle\Translation\KeySource\StepNameSource;

class StepNameSourceTest extends TranslationKeySourceTestCase
{
    public function testGetTemplate()
    {
        $keySource = new StepNameSource($this->getWorkflow('workflow'), ['step_name' => 'test_step']);

        $this->assertEquals(
            'oro.workflow.{{ workflow_name }}.step.{{ step_name }}.name',
            $keySource->getTemplate()
        );
    }

    /**
     * @dataProvider requiredKeysDataProvider
     *
     * @param Workflow $workflow
     * @param array $data
     * @param string $expectedMessage
     */
    public function testRequiredKeys(Workflow $workflow, array $data, $expectedMessage)
    {
        $this->setExpectedException('InvalidArgumentException', $expectedMessage);

        new StepNameSource($workflow, $data);
    }

    /**
     * @return array
     */
    public function requiredKeysDataProvider()
    {
        return array_merge(
            parent::requiredKeysDataProvider(),
            [
                'empty step name' => [
                    'workflow' => $this->getWorkflow('test'),
                    'data' => [],
                    'expectedMessage' => 'Expected not empty value for key "step_name" in data, null given'
                ]
            ]
        );
    }
}
