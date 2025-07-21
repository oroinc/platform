<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Translation\KeyTemplate;

use Oro\Bundle\WorkflowBundle\Translation\KeyTemplate\TransitionAttributeTemplate;

class TransitionAttributeTemplateTest extends TemplateTestCase
{
    #[\Override]
    public function getTemplateInstance()
    {
        return new TransitionAttributeTemplate();
    }

    public function testGetName(): void
    {
        $this->assertName(TransitionAttributeTemplate::NAME);
    }

    #[\Override]
    public function testGetTemplate(): void
    {
        $this->assertTemplate(
            'oro.workflow.{{ workflow_name }}.transition.{{ transition_name }}.attribute.{{ attribute_name }}'
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
