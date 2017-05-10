<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Translation\KeyTemplate;

use Oro\Bundle\WorkflowBundle\Translation\KeyTemplate\TransitionButtonTitleTemplate;

class TransitionButtonTitleTemplateTest extends TemplateTestCase
{
    /**
     * {@inheritdoc}
     */
    public function getTemplateInstance()
    {
        return new TransitionButtonTitleTemplate();
    }

    public function testGetName()
    {
        $this->assertName(TransitionButtonTitleTemplate::NAME);
    }

    public function testGetTemplate()
    {
        $this->assertTemplate('oro.workflow.{{ workflow_name }}.transition.{{ transition_name }}.button_title');
    }

    public function testGetRequiredKeys()
    {
        $this->assertRequiredKeys(['workflow_name', 'transition_name']);
    }

    public function testGetKeyTemplates()
    {
        $this->assertKeyTemplates([
            'workflow_name' => '{{ workflow_name }}',
            'transition_name' => '{{ transition_name }}',
        ]);
    }
}
