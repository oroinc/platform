<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Translation\KeySource;

use Oro\Bundle\WorkflowBundle\Model\Workflow;
use Oro\Bundle\WorkflowBundle\Translation\KeySource\TransitionNameSource;

class TransitionNameSourceTest extends TranslationKeySourceTestCase
{
    public function testGetTemplate()
    {
        $keySource = new TransitionNameSource($this->getWorkflow('workflow'), ['transition_name' => 'test_transition']);

        $this->assertEquals(
            'oro.workflow.{{ workflow_name }}.transition.{{ transition_name }}.name',
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

        new TransitionNameSource($workflow, $data);
    }

    /**
     * @return array
     */
    public function requiredKeysDataProvider()
    {
        return array_merge(
            parent::requiredKeysDataProvider(),
            [
                'empty transition name' => [
                    'workflow' => $this->getWorkflow('test'),
                    'data' => [],
                    'expectedMessage' => 'Expected not empty value for key "transition_name" in data, null given'
                ]
            ]
        );
    }
}
