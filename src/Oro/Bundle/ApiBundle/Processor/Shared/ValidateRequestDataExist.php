<?php

namespace Oro\Bundle\ApiBundle\Processor\Shared;

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
            $context->addError(
                Error::createValidationError('request data constraint', 'The request data should not be empty.')
            );
        }
    }
}
