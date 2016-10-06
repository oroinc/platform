<?php

namespace Oro\Bundle\WorkflowBundle\Translation\KeyTemplate;

class AttributeNameTemplate extends AttributeTemplate
{
    /**
     * @return string
     */
    public function getTemplate()
    {
        return parent::getTemplate() . '.name';
    }
}
