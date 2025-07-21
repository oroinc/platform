<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Translation\KeyTemplate;

use Oro\Bundle\WorkflowBundle\Translation\KeyTemplate\TransitionAttributeLabelTemplate;

class TransitionAttributeLabelTemplateTest extends TemplateTestCase
{
    #[\Override]
    public function getTemplateInstance()
    {
        return new TransitionAttributeLabelTemplate();
    }

    public function testGetName(): void
    {
        $this->assertEquals(TransitionAttributeLabelTemplate::NAME, $this->getTemplateInstance()->getName());
    }

    #[\Override]
    public function testGetTemplate(): void
    {
        $this->assertTemplate(
            'oro.workflow.{{ workflow_name }}.transition.{{ transition_name }}.attribute.{{ attribute_name }}.label'
        );
    }

    #[\Override]
    public function testGetRequiredKeys(): void
    {
        $this->assertRequiredKeys(['workflow_name', 'transition_name', 'attribute_name']);
    }

    public function testGetKeyTemplates(): void
    {
        $this->assertKeyTemplates([
            'workflow_name' => '{{ workflow_name }}',
            'transition_name' => '{{ transition_name }}',
            'attribute_name' => '{{ attribute_name }}',
        ]);
    }
}
