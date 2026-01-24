<?php

namespace Oro\Bundle\FormBundle\Event\FormHandler;

use Symfony\Component\Form\FormInterface;

/**
 * Defines the contract for objects that are aware of and provide access to a form.
 *
 * Implementations of this interface represent events or objects that carry a reference
 * to a Symfony Form instance, allowing consumers to access and interact with the form.
 */
interface FormAwareInterface
{
    /**
     * @return FormInterface
     */
    public function getForm();
}
