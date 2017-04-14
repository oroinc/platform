<?php

namespace Oro\Bundle\WorkflowBundle\Processor\Transition;

use Oro\Bundle\WorkflowBundle\Exception\ForbiddenTransitionException;
use Oro\Bundle\WorkflowBundle\Exception\InvalidTransitionException;
use Oro\Bundle\WorkflowBundle\Exception\UnknownAttributeException;
use Oro\Bundle\WorkflowBundle\Exception\WorkflowNotFoundException;
use Oro\Bundle\WorkflowBundle\Processor\Context\TransitionContext;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * Processing error normalization for desired errors
 */
class ErrorNormalizeProcessor implements ProcessorInterface
{
    /** @var LoggerInterface */
    private $logger;

    /**
     * @param LoggerInterface $logger
     */
    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * @param ContextInterface|TransitionContext $context
     */
    public function process(ContextInterface $context)
    {
        //skip if custom failure handling were performed already or no failures met
        if (!$this->isApplicable($context)) {
            return;
        }

        try {
            throw $context->getError();
        } catch (HttpException $e) {
            $responseCode = $e->getStatusCode();
        } catch (WorkflowNotFoundException $e) {
            $responseCode = Response::HTTP_NOT_FOUND;
        } catch (UnknownAttributeException $e) {
            $responseCode = Response::HTTP_BAD_REQUEST;
        } catch (InvalidTransitionException $e) {
            $responseCode = Response::HTTP_BAD_REQUEST;
        } catch (ForbiddenTransitionException $e) {
            $responseCode = Response::HTTP_FORBIDDEN;
        } catch (\Throwable $e) {
            $responseCode = Response::HTTP_INTERNAL_SERVER_ERROR;
        } finally {
            $responseMessage = $e->getMessage();
        }

        $this->logger->error(
            '[TransitionHandler] Could not perform transition.',
            [
                'exception' => $context->getError(),
                'transition' => $context->getTransition(),
                'workflowItem' => $context->getWorkflowItem()
            ]
        );

        $context->set('responseCode', $responseCode);
        $context->set('responseMessage', $responseMessage);
    }

    /**
     * @param TransitionContext $context
     * @return bool
     */
    protected function isApplicable(TransitionContext $context): bool
    {
        if (!$context->hasError()) {
            return false;
        }

        if ($context->has('responseCode') && $context->has('responseMessage')) {
            return false;
        }

        return true;
    }
}
