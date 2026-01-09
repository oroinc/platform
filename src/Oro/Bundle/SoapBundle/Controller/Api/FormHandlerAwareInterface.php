<?php

namespace Oro\Bundle\SoapBundle\Controller\Api;

use Oro\Bundle\SoapBundle\Form\Handler\ApiFormHandler;

/**
 * Defines the contract for objects that provide access to an API form handler.
 *
 * Implementing classes expose a form handler that manages form submission, validation,
 * and entity persistence for API requests.
 */
interface FormHandlerAwareInterface
{
    /**
     * @return ApiFormHandler
     */
    public function getFormHandler();
}
