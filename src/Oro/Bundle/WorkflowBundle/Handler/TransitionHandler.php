<?php

namespace Oro\Bundle\WorkflowBundle\Handler;

use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;
use Oro\Bundle\WorkflowBundle\Exception\ForbiddenTransitionException;
use Oro\Bundle\WorkflowBundle\Exception\InvalidTransitionException;
use Oro\Bundle\WorkflowBundle\Exception\WorkflowNotFoundException;
use Oro\Bundle\WorkflowBundle\Handler\Helper\TransitionHelper;
use Oro\Bundle\WorkflowBundle\Model\Transition;
use Oro\Bundle\WorkflowBundle\Model\WorkflowManager;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Response;

class TransitionHandler
{
    /** @var WorkflowManager */
    protected $workflowManager;

    /** @var TransitionHelper */
    protected $transitionHelper;

    /** @var LoggerInterface */
    protected $logger;

    /**
     * @param WorkflowManager $workflowManager
     * @param TransitionHelper $transitionHelper
     * @param LoggerInterface $logger
     */
    public function __construct(
        WorkflowManager $workflowManager,
        TransitionHelper $transitionHelper,
        LoggerInterface $logger
    ) {
        $this->workflowManager = $workflowManager;
        $this->transitionHelper = $transitionHelper;
        $this->logger = $logger;
    }

    /**
     * @param Transition $transition
     * @param WorkflowItem $workflowItem
     *
     * @return Response|null
     */
    public function handle(Transition $transition, WorkflowItem $workflowItem)
    {
        if ($transition->getPageTemplate() || $transition->getDialogTemplate()) {
            return null;
        }

        $responseCode = null;

        try {
            $this->workflowManager->transit($workflowItem, $transition);
        } catch (WorkflowNotFoundException $e) {
            $responseCode = 404;
        } catch (InvalidTransitionException $e) {
            $responseCode = 400;
        } catch (ForbiddenTransitionException $e) {
            $responseCode = 403;
        } catch (\Exception $e) {
            $responseCode = 500;
        }

        if (isset($e)) {
            $this->logger->error(
                '[TransitionHandler] Could not perform transition.',
                [
                    'exception' => $e,
                    'transition' => $transition,
                    'workflowItem' => $workflowItem
                ]
            );
        }

        return $this->transitionHelper->createCompleteResponse($workflowItem, $responseCode);
    }
}
