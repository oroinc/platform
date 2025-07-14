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

    public function testRootConst(): void
    {
        $this->assertEquals('oro.workflow', WorkflowTemplate::KEY_PREFIX);
    }

    public function testGetName(): void
    {
        $this->assertName(WorkflowTemplate::NAME);
    }

    #[\Override]
    public function testGetTemplate(): void
    {
        $this->assertTemplate('oro.workflow.{{ workflow_name }}');
    }

    #[\Override]
    public function testGetRequiredKeys(): void
    {
        $this->assertRequiredKeys(['workflow_name']);
    }

    public function testGetKeyTemplate(): void
    {
        $this->assertEquals('{{ attr1 }}', $this->getTemplateInstance()->getKeyTemplate('attr1'));
    }

    public function testGetKeyTemplates(): void
    {
        $this->assertKeyTemplates(['workflow_name' => '{{ workflow_name }}']);
    }
}
