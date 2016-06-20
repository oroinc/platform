<?php

namespace Oro\Bundle\ApiBundle\Processor\Shared;

use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Oro\Bundle\ApiBundle\Model\Error;
use Oro\Bundle\ApiBundle\Processor\Context;
use Oro\Bundle\ApiBundle\Request\Constraint;
use Oro\Bundle\ApiBundle\Request\Version;

/**
 * Makes sure that the request type exists in the Context.
 * Sets Data API version to "latest" if not specified otherwise.
 * Removes meaningless prefix, e.g. "v", from a version number.
 */
class ValidateRequestTypeAndVersion implements ProcessorInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context)
    {
        /** @var Context $context */

        if ($context->getRequestType()->isEmpty()) {
            $context->addError(
                Error::createValidationError(
                    Constraint::REQUEST_TYPE,
                    'The type of a request must be set in the context.'
                )
            );
        } else {
            $context->setVersion(Version::normalizeVersion($context->getVersion()));
        }
    }
}
