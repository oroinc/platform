<?php

namespace Oro\Bundle\WorkflowBundle\Translation\KeyTemplate;

class AttributeLabelTemplate extends AttributeTemplate
{
    /**
     * @return string
     */
    public function getTemplate()
    {
        return parent::getTemplate() . '.label';
    }
}
