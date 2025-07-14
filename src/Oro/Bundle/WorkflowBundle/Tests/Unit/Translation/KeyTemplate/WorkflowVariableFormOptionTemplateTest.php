<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Translation\KeyTemplate;

use Oro\Bundle\WorkflowBundle\Translation\KeyTemplate\WorkflowVariableFormOptionTemplate;

class WorkflowVariableFormOptionTemplateTest extends TemplateTestCase
{
    #[\Override]
    public function getTemplateInstance()
    {
        return new WorkflowVariableFormOptionTemplate();
    }

    public function testGetName(): void
    {
        $this->assertEquals(WorkflowVariableFormOptionTemplate::NAME, $this->getTemplateInstance()->getName());
    }

    #[\Override]
    public function testGetTemplate(): void
    {
        $this->assertTemplate('oro.workflow.{{ workflow_name }}.variable.{{ variable_name }}.{{ option_name }}');
    }

    #[\Override]
    public function testGetRequiredKeys(): void
    {
        $this->assertRequiredKeys(['workflow_name', 'variable_name', 'option_name']);
    }

    public function testGetKeyTemplates(): void
    {
        $this->assertKeyTemplates([
            'workflow_name' => '{{ workflow_name }}',
            'variable_name' => '{{ variable_name }}',
            'option_name' => '{{ option_name }}',
        ]);
    }
}
