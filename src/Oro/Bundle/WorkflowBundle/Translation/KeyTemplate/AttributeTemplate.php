<?php

namespace Oro\Bundle\WorkflowBundle\Translation\KeyTemplate;

class AttributeTemplate extends WorkflowTemplate
{
    /**
     * @return string
     */
    public function getTemplate()
    {
        return parent::getTemplate() . '.attribute.{{ attribute_name}}';
    }

    /**
     * @return array
     */
    public function getRequiredKeys()
    {
        return array_merge(parent::getRequiredKeys(), ['attribute_name']);
    }
}
