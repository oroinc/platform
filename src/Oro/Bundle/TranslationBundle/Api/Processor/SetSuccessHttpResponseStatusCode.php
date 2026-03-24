<?php

namespace Oro\Bundle\TranslationBundle\Api\Processor;

use Oro\Bundle\ApiBundle\Processor\Create\CreateContext;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Symfony\Component\HttpFoundation\Response;

/**
 * Sets HTTP status code for success response of "create" action for TranslationKey entity.
 */
class SetSuccessHttpResponseStatusCode implements ProcessorInterface
{
    #[\Override]
    public function process(ContextInterface $context): void
    {
        /** @var CreateContext $context */

        if (null !== $context->getResponseStatusCode()) {
            // the status code is already set
            return;
        }

        if ($context->hasErrors() || !$context->hasResult()) {
            // this processor sets HTTP status code only for success response
            return;
        }

        $context->setResponseStatusCode($this->getResponseStatusCode($context));
    }

    private function getResponseStatusCode(CreateContext $context): int
    {
        if (!$context->isExisting() && $context->getId()) {
            return Response::HTTP_CREATED;
        }

        return Response::HTTP_OK;
    }
}
