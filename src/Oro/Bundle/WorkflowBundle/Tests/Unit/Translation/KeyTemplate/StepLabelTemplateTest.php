<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Translation\KeyTemplate;

use Oro\Bundle\WorkflowBundle\Translation\KeyTemplate\StepLabelTemplate;

class StepLabelTemplateTest extends TemplateTestCase
{
    /**
     * {@inheritdoc}
     */
    public function getTemplateInstance()
    {
        return new StepLabelTemplate();
    }

    public function testGetName()
    {
        $this->assertName(StepLabelTemplate::NAME);
    }

    /**
     * {@inheritdoc}
     */
    public function testGetTemplate()
    {
        $this->assertTemplate('oro.workflow.{{ workflow_name }}.step.{{ step_name }}.label');
    }

    /**
     * {@inheritdoc}
     */
    public function testGetRequiredKeys()
    {
        $this->assertRequiredKeys(['workflow_name', 'step_name']);
    }

    public function testGetKeyTemplates()
    {
        $this->assertKeyTemplates([
            'workflow_name' => '{{ workflow_name }}',
            'step_name' => '{{ step_name }}',
        ]);
    }
}
