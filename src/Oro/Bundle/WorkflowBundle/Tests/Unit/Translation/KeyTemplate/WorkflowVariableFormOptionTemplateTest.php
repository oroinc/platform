<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Translation\KeyTemplate;

use Oro\Bundle\WorkflowBundle\Translation\KeyTemplate\WorkflowVariableFormOptionTemplate;

class WorkflowVariableFormOptionTemplateTest extends TemplateTestCase
{
    /** {@inheritdoc} */
    public function getTemplateInstance()
    {
        return new WorkflowVariableFormOptionTemplate();
    }

    public function testGetName()
    {
        $this->assertEquals(WorkflowVariableFormOptionTemplate::NAME, $this->getTemplateInstance()->getName());
    }

    public function testGetTemplate()
    {
        $this->assertTemplate('oro.workflow.{{ workflow_name }}.variable.{{ variable_name }}.{{ option_name }}');
    }

    public function testGetRequiredKeys()
    {
        $this->assertRequiredKeys(['workflow_name', 'variable_name', 'option_name']);
    }

    public function testGetKeyTemplates()
    {
        $this->assertKeyTemplates([
            'workflow_name' => '{{ workflow_name }}',
            'variable_name' => '{{ variable_name }}',
            'option_name' => '{{ option_name }}',
        ]);
    }
}
