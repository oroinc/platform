<?php

namespace Oro\Bundle\WorkflowBundle\Translation\KeyTemplate;

class AttributeLabelTemplate extends AttributeTemplate
{
    const NAME = 'attribute_label';

    /**
     * @return string
     */
    public function getTemplate()
    {
        return parent::getTemplate() . '.label';
    }
}
