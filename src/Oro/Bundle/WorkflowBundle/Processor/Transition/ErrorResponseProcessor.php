<?php

namespace Oro\Bundle\WorkflowBundle\Processor\Transition;

use Oro\Bundle\WorkflowBundle\Processor\Context\TransitionContext;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Symfony\Component\HttpFoundation\Response;

/**
 * Converts workflow transition errors into HTTP responses.
 *
 * This processor checks if an error has occurred during transition processing and converts it
 * into an appropriate HTTP response. It sets the response status code and message based on the error
 * context, defaulting to HTTP 500 (Internal Server Error) if no specific code is provided.
 * The processor marks the context as processed to prevent further processing.
 */
class ErrorResponseProcessor implements ProcessorInterface
{
    /**
     * @param ContextInterface|TransitionContext $context
     */
    #[\Override]
    public function process(ContextInterface $context)
    {
        if (!$context->hasError()) {
            return;
        }

        $response = new Response();
        $response->setStatusCode(
            $context->get('responseCode') ?: Response::HTTP_INTERNAL_SERVER_ERROR,
            $context->get('responseMessage') ?: $context->getError()->getMessage()
        );

        $context->setResult($response);
        $context->setProcessed(true);
    }
}
