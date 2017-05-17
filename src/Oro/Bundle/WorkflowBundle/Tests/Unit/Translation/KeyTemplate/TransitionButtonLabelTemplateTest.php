<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Translation\KeyTemplate;

use Oro\Bundle\WorkflowBundle\Translation\KeyTemplate\TransitionButtonLabelTemplate;

class TransitionButtonLabelTemplateTest extends TemplateTestCase
{
    /**
     * {@inheritdoc}
     */
    public function getTemplateInstance()
    {
        return new TransitionButtonLabelTemplate();
    }

    public function testGetName()
    {
        $this->assertName(TransitionButtonLabelTemplate::NAME);
    }

    public function testGetTemplate()
    {
        $this->assertTemplate('oro.workflow.{{ workflow_name }}.transition.{{ transition_name }}.button_label');
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
