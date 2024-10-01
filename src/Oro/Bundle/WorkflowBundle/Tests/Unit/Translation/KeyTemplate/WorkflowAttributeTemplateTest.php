<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Translation\KeyTemplate;

use Oro\Bundle\WorkflowBundle\Translation\KeyTemplate\WorkflowAttributeTemplate;

class WorkflowAttributeTemplateTest extends TemplateTestCase
{
    #[\Override]
    public function getTemplateInstance()
    {
        return new WorkflowAttributeTemplate();
    }

    public function testGetName()
    {
        $this->assertName(WorkflowAttributeTemplate::NAME);
    }

    #[\Override]
    public function testGetTemplate()
    {
        $this->assertTemplate('oro.workflow.{{ workflow_name }}.attribute.{{ attribute_name }}');
    }

    #[\Override]
    public function testGetRequiredKeys()
    {
        $this->assertRequiredKeys(['workflow_name', 'attribute_name']);
    }

    public function testGetKeyTemplates()
    {
        $this->assertKeyTemplates([
            'workflow_name' => '{{ workflow_name }}',
            'attribute_name' => '{{ attribute_name }}',
        ]);
    }
}
