<?php

namespace Oro\Bundle\WorkflowBundle\Translation\KeyTemplate;

class StepNameTemplate extends StepTemplate
{
    /**
     * @return string
     */
    public function getRequiredKeys()
    {
        return parent::getRequiredKeys() . '.name';
    }
}
