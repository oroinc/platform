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

    public function testGetName(): void
    {
        $this->assertEquals(WorkflowVariableLabelTemplate::NAME, $this->getTemplateInstance()->getName());
    }

    #[\Override]
    public function testGetTemplate(): void
    {
        $this->assertTemplate('oro.workflow.{{ workflow_name }}.variable.{{ variable_name }}.label');
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
            'variable_name' => '{{ variable_name }}',
        ]);
    }
}
