<?php

namespace Oro\Bundle\FlexibleEntityBundle\AttributeType;

use Oro\Bundle\FlexibleEntityBundle\Model\AbstractAttribute;
use Oro\Bundle\FlexibleEntityBundle\Model\FlexibleValueInterface;
use Symfony\Component\Form\FormFactoryInterface;

/**
 * The attribute type interface
 */
interface AttributeTypeInterface
{
    /**
     * Get name
     *
     * @return string
     */
    public function getName();

    /**
     * Build form type for flexible entity value
     *
     * @param FormFactoryInterface   $factory the form factory
     * @param FlexibleValueInterface $value   the flexible value
     *
     * @return FormInterface the form
     */
    public function buildValueFormType(FormFactoryInterface $factory, FlexibleValueInterface $value);

    /**
     * Build form types for custom properties of an attribute
     *
     * @param FormFactoryInterface $factory   the form factory
     * @param AbstractAttribute    $attribute the attribute
     *
     * @return FormInterface the form
     */
    public function buildAttributeFormTypes(FormFactoryInterface $factory, AbstractAttribute $attribute);
}
