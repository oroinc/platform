<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Translation\KeyTemplate;

use Oro\Bundle\WorkflowBundle\Translation\KeyTemplate\WorkflowVariableLabelTemplate;

class WorkflowVariableLabelTemplateTest extends TemplateTestCase
{
    #[\Override]
    public function getTemplateInstance()
    {
        return new WorkflowVariableLabelTemplate();
    }

    public function testGetName()
    {
        $this->assertEquals(WorkflowVariableLabelTemplate::NAME, $this->getTemplateInstance()->getName());
    }

    #[\Override]
    public function testGetTemplate()
    {
        $this->assertTemplate('oro.workflow.{{ workflow_name }}.variable.{{ variable_name }}.label');
    }

    #[\Override]
    public function testGetRequiredKeys()
    {
        $this->assertRequiredKeys(['workflow_name', 'variable_name']);
    }

    public function testGetKeyTemplates()
    {
        $this->assertKeyTemplates([
            'workflow_name' => '{{ workflow_name }}',
            'variable_name' => '{{ variable_name }}',
        ]);
    }
}
