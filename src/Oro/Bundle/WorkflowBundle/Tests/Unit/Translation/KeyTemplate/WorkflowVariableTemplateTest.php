<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Translation\KeyTemplate;

use Oro\Bundle\WorkflowBundle\Translation\KeyTemplate\WorkflowVariableTemplate;

class WorkflowVariableTemplateTest extends TemplateTestCase
{
    #[\Override]
    public function getTemplateInstance()
    {
        return new WorkflowVariableTemplate();
    }

    public function testGetName(): void
    {
        $this->assertName(WorkflowVariableTemplate::NAME);
    }

    #[\Override]
    public function testGetTemplate(): void
    {
        $this->assertTemplate('oro.workflow.{{ workflow_name }}.variable.{{ variable_name }}');
    }

    #[\Override]
    public function testGetRequiredKeys(): void
    {
        $this->assertRequiredKeys(['workflow_name', 'variable_name']);
    }

    public function testGetKeyTemplates(): void
    {
        $this->assertKeyTemplates([
            'workflow_name' => '{{ workflow_name }}',
            'variable_name' => '{{ variable_name }}'
        ]);
    }
}
