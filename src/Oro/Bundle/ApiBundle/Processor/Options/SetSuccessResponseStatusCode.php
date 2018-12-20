<?php

namespace Oro\Bundle\ApiBundle\Processor\Options;

use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Symfony\Component\HttpFoundation\Response;

/**
 * Sets 200 OK response status code for success OPTIONS request.
 * This processor is required because by default 204 No Content is used for empty responses.
 */
class SetSuccessResponseStatusCode implements ProcessorInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context)
    {
        /** @var OptionsContext $context */

        if (!$context->hasResult() && !$context->hasErrors() && null === $context->getResponseStatusCode()) {
            $context->setResponseStatusCode(Response::HTTP_OK);
        }
    }
}
