<?php

namespace Oro\Bundle\ApiBundle\Processor\Shared;

use Oro\Bundle\ApiBundle\Processor\FormContext;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Symfony\Component\HttpFoundation\Response;

/**
 * Sets the status code for the success HTTP response for API actions
 * that can create a new entity or update existing entity depends on request data.
 */
class SetHttpResponseStatusCodeForFormAction implements ProcessorInterface
{
    #[\Override]
    public function process(ContextInterface $context): void
    {
        /** @var FormContext $context */

        if (null !== $context->getResponseStatusCode()) {
            // the status code is already set
            return;
        }

        if ($context->hasErrors() || !$context->hasResult()) {
            return;
        }

        $context->setResponseStatusCode($context->isExisting() ? Response::HTTP_OK : Response::HTTP_CREATED);
    }
}
