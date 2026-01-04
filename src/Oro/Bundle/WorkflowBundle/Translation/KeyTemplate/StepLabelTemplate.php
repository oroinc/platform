<?php

namespace Oro\Bundle\WorkflowBundle\Translation\KeyTemplate;

/**
 * Step label key template.
 */
class StepLabelTemplate extends StepTemplate
{
    public const NAME = 'step_label';

    #[\Override]
    public function getTemplate(): string
    {
        return parent::getTemplate() . '.label';
    }
}
