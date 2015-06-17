<?php

namespace Oro\Bundle\WorkflowBundle\Handler;

use Exception;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;
use Oro\Bundle\WorkflowBundle\Exception\ForbiddenTransitionException;
use Oro\Bundle\WorkflowBundle\Exception\InvalidTransitionException;
use Oro\Bundle\WorkflowBundle\Exception\WorkflowNotFoundException;
use Oro\Bundle\WorkflowBundle\Handler\Helper\TransitionHelper;
use Oro\Bundle\WorkflowBundle\Model\Transition;
use Oro\Bundle\WorkflowBundle\Model\WorkflowManager;
use Symfony\Component\HttpFoundation\Response;

class TransitionHandler
{
    /** @var WorkflowManager */
    protected $workflowManager;

    /** @var TransitionHelper */
    protected $transitionHelper;

    /**
     * @param WorkflowManager $workflowManager
     * @param TransitionHelper $transitionHelper
     */
    public function __construct(WorkflowManager $workflowManager, TransitionHelper $transitionHelper)
    {
        $this->workflowManager = $workflowManager;
        $this->transitionHelper = $transitionHelper;
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
            return;
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
        } catch (Exception $e) {
            $responseCode = 500;
        }

        return $this->transitionHelper->createCompleteResponse($workflowItem, $responseCode);
    }
}
