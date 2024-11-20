<?php

namespace Oro\Bundle\ApiBundle\Processor\Subresource\ChangeSubresource;

use Oro\Bundle\ApiBundle\Model\Error;
use Oro\Bundle\ApiBundle\Processor\Subresource\ChangeSubresourceContext;
use Oro\Bundle\ApiBundle\Request\Constraint;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Validates that the request data exist and not empty.
 */
class ValidateRequestDataExist implements ProcessorInterface
{
    #[\Override]
    public function process(ContextInterface $context): void
    {
        /** @var ChangeSubresourceContext $context */

        $requestData = $context->getRequestData();
        if (empty($requestData) && $context->getRequestMetadata()?->hasIdentifierFields()) {
            $context->addError(
                Error::createValidationError(
                    Constraint::REQUEST_DATA,
                    'The request data should not be empty.'
                )
            );
        }
    }
}
