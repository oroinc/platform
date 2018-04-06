<?php

namespace Oro\Bundle\ApiBundle\Processor\Shared;

use Oro\Bundle\ApiBundle\Processor\Context;
use Oro\Bundle\ApiBundle\Request\ErrorStatusCodesWithoutContentTrait;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Removes errors from the response for some predefined HTTP status codes,
 * e.g. for 405 (Method Not Allowed).
 */
class RemoveResponseErrors implements ProcessorInterface
{
    use ErrorStatusCodesWithoutContentTrait;

    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context)
    {
        /** @var Context $context */

        $responseStatusCode = $context->getResponseStatusCode();
        if (null !== $responseStatusCode
            && $context->hasErrors()
            && $this->isErrorResponseWithoutContent($responseStatusCode)
        ) {
            $context->resetErrors();
        }
    }
}
