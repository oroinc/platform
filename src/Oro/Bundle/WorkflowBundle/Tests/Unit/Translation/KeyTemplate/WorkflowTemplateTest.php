<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Translation\KeyTemplate;

use Oro\Bundle\WorkflowBundle\Translation\KeyTemplate\WorkflowTemplate;

class WorkflowTemplateTest extends TemplateTestCase
{
    #[\Override]
    public function getTemplateInstance()
    {
        return new WorkflowTemplate();
    }

    public function testRootConst()
    {
        $this->assertEquals('oro.workflow', WorkflowTemplate::KEY_PREFIX);
    }

    public function testGetName()
    {
        $this->assertName(WorkflowTemplate::NAME);
    }

    #[\Override]
    public function testGetTemplate()
    {
        $this->assertTemplate('oro.workflow.{{ workflow_name }}');
    }

    #[\Override]
    public function testGetRequiredKeys()
    {
        $this->assertRequiredKeys(['workflow_name']);
    }

    public function testGetKeyTemplate()
    {
        $this->assertEquals('{{ attr1 }}', $this->getTemplateInstance()->getKeyTemplate('attr1'));
    }

    public function testGetKeyTemplates()
    {
        $this->assertKeyTemplates(['workflow_name' => '{{ workflow_name }}']);
    }
}
