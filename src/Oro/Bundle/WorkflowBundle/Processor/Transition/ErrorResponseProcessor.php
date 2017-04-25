<?php

namespace Oro\Bundle\WorkflowBundle\Processor\Transition;

use Oro\Bundle\WorkflowBundle\Processor\Context\TransitionContext;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Symfony\Component\HttpFoundation\Response;

class ErrorResponseProcessor implements ProcessorInterface
{
    /**
     * @param ContextInterface|TransitionContext $context
     */
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
