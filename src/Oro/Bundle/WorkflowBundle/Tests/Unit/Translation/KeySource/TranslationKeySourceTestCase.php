<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Translation\KeySource;

use Oro\Bundle\WorkflowBundle\Model\Workflow;

abstract class TranslationKeySourceTestCase extends \PHPUnit_Framework_TestCase
{
    /**
     * @return array
     */
    public function requiredKeysDataProvider()
    {
        return [
            'empty workflow name' => [
                'workflow' => $this->getWorkflow(),
                'data' => [],
                'expectedMessage' => 'Expected not empty value for key "workflow_name" in data, null given'
            ]
        ];
    }

    /**
     * @param string $name
     * @return Workflow|\PHPUnit_Framework_MockObject_MockObject
     */
    protected function getWorkflow($name = null)
    {
        $workflow = $this->getMockBuilder(Workflow::class)->disableOriginalConstructor()->getMock();
        $workflow->expects($this->any())->method('getName')->willReturn($name);

        return $workflow;
    }
}
