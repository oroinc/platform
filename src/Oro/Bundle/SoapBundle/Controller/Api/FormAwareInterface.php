<?php

namespace Oro\Bundle\SoapBundle\Controller\Api;

use Symfony\Component\Form\FormInterface;

/**
 * Defines the contract for objects that provide access to a Symfony form instance.
 *
 * Implementing classes expose a form that can be used for data validation and transformation
 * in API request handling.
 */
interface FormAwareInterface
{
    /**
     * @return FormInterface
     */
    public function getForm();
}
