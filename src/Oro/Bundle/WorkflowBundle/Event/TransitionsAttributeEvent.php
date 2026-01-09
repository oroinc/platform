<?php

namespace Oro\Bundle\WorkflowBundle\Event;

use Oro\Bundle\ActionBundle\Model\Attribute;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * Event dispatched before adding an attribute to workflow transitions.
 *
 * This event allows listeners to modify attribute options and transition options before
 * an attribute is added to the workflow transition configuration.
 */
class TransitionsAttributeEvent extends Event
{
    public const BEFORE_ADD = 'oro_workflow.transitions.attribute.before_add';

    /** @var Attribute */
    protected $attribute;

    /** @var array */
    protected $attributeOptions;

    /** @var array */
    protected $options;

    /**
     * @param Attribute $attribute
     * @param array $attributeOptions
     * @param array $options
     */
    public function __construct(Attribute $attribute, $attributeOptions, $options)
    {
        $this->attribute        = $attribute;
        $this->attributeOptions = $attributeOptions;
        $this->options          = $options;
    }

    /**
     * @return Attribute
     */
    public function getAttribute()
    {
        return $this->attribute;
    }

    /**
     * @param array $attributeOptions
     */
    public function setAttributeOptions($attributeOptions)
    {
        $this->attributeOptions = $attributeOptions;
    }

    /**
     * @return array
     */
    public function getAttributeOptions()
    {
        return $this->attributeOptions;
    }

    /**
     * @return array
     */
    public function getOptions()
    {
        return $this->options;
    }
}
