<?php

namespace Oro\Bundle\WorkflowBundle\Translation\KeyTemplate;

/**
 * Step label key template.
 */
class StepLabelTemplate extends StepTemplate
{
    const NAME = 'step_label';

    public function getTemplate(): string
    {
        return parent::getTemplate() . '.label';
    }
}
