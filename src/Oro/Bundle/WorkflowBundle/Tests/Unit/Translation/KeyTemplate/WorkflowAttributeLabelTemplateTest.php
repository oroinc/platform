<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Translation\KeyTemplate;

use Oro\Bundle\WorkflowBundle\Translation\KeyTemplate\WorkflowAttributeLabelTemplate;

class WorkflowAttributeLabelTemplateTest extends TemplateTestCase
{
    /** {@inheritdoc} */
    public function getTemplateInstance()
    {
        return new WorkflowAttributeLabelTemplate();
    }

    public function testGetName()
    {
        $this->assertEquals(WorkflowAttributeLabelTemplate::NAME, $this->getTemplateInstance()->getName());
    }

    public function testGetTemplate()
    {
        $this->assertTemplate('oro.workflow.{{ workflow_name }}.attribute.{{ attribute_name }}.label');
    }

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
