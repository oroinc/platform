<?php

namespace Oro\Bundle\FormBundle\Form\Handler;

use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Defines the contract for form processing handlers.
 *
 * Implementations of this interface are responsible for processing form submissions,
 * including validation, data transformation, and persistence. Handlers receive the
 * form data, form instance, and HTTP request, and return a boolean indicating
 * whether processing was successful.
 */
interface FormHandlerInterface
{
    /**
     * @param mixed $data
     * @param FormInterface $form
     * @param Request $request
     *
     * @return bool
     */
    public function process($data, FormInterface $form, Request $request);
}
