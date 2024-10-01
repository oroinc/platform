<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Translation\KeyTemplate;

use Oro\Bundle\WorkflowBundle\Translation\KeyTemplate\WorkflowLabelTemplate;

class WorkflowLabelTemplateTest extends TemplateTestCase
{
    #[\Override]
    public function getTemplateInstance()
    {
        return new WorkflowLabelTemplate();
    }

    public function testGetName()
    {
        $this->assertName(WorkflowLabelTemplate::NAME);
    }

    #[\Override]
    public function testGetTemplate()
    {
        $this->assertTemplate('oro.workflow.{{ workflow_name }}.label');
    }

    #[\Override]
    public function testGetRequiredKeys()
    {
        $this->assertRequiredKeys(['workflow_name']);
    }

    public function testGetKeyTemplates()
    {
        $this->assertKeyTemplates(['workflow_name' => '{{ workflow_name }}']);
    }
}
