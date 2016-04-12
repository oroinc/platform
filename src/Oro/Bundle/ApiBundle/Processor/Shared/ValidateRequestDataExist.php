<?php

namespace Oro\Bundle\ApiBundle\Processor\Shared;

use Symfony\Component\HttpFoundation\Response;

use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Oro\Bundle\ApiBundle\Model\Error;
use Oro\Bundle\ApiBundle\Processor\FormContext;

/**
 * Validates that the request data exist and not empty.
 */
class ValidateRequestDataExist implements ProcessorInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context)
    {
        /** @var FormContext $context */

        $requestData = $context->getRequestData();
        if (empty($requestData)) {
            $error = new Error();
            $error->setStatusCode(Response::HTTP_BAD_REQUEST);
            $error->setTitle('request data constraint');
            $error->setDetail('The request data should not be empty.');
            $context->addError($error);
        }
    }
}
