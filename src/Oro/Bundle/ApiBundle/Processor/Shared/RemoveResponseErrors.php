<?php

namespace Oro\Bundle\ApiBundle\Processor\Shared;

use Symfony\Component\HttpFoundation\Response;

use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Oro\Bundle\ApiBundle\Processor\Context;

/**
 * Removes errors from the response for some predefined HTTP status codes.
 */
class RemoveResponseErrors implements ProcessorInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context)
    {
        /** @var Context $context */

        $responseStatusCode = $context->getResponseStatusCode();
        if (null === $responseStatusCode || !$context->hasErrors()) {
            // the status code is not set or the response does not contain any error
            return;
        }
        if (in_array($responseStatusCode, $this->getStatusCodesWithoutBody(), true)) {
            $context->resetErrors();
        }
    }

    /**
     * @return int[]
     */
    protected function getStatusCodesWithoutBody()
    {
        return [
            Response::HTTP_METHOD_NOT_ALLOWED
        ];
    }
}
