<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Translation\KeySource;

use Oro\Bundle\WorkflowBundle\Model\Workflow;
use Oro\Bundle\WorkflowBundle\Translation\KeySource\WorkflowNameSource;

class WorkflowNameSourceTest extends TranslationKeySourceTestCase
{
    public function testGetTemplate()
    {
        $keySource = new WorkflowNameSource($this->getWorkflow('workflow'));

        $this->assertEquals('oro.workflow.{{ workflow_name }}.name', $keySource->getTemplate());
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

        new WorkflowNameSource($workflow, $data);
    }
}
