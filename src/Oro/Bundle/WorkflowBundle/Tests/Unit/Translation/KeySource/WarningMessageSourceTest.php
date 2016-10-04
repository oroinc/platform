<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Translation\KeySource;

use Oro\Bundle\WorkflowBundle\Model\Workflow;
use Oro\Bundle\WorkflowBundle\Translation\KeySource\WarningMessageSource;

class WarningMessageSourceTest extends TranslationKeySourceTestCase
{
    public function testGetTemplate()
    {
        $keySource = new WarningMessageSource($this->getWorkflow('workflow'), ['transition_name' => 'test_transition']);

        $this->assertEquals(
            'oro.workflow.{{ workflow_name }}.transition.{{ transition_name }}.warning_message',
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

        new WarningMessageSource($workflow, $data);
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
