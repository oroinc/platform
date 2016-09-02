<?php

namespace Oro\Bundle\WorkflowBundle\Handler;

use Exception;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;

use Oro\Bundle\WorkflowBundle\Exception\ForbiddenTransitionException;
use Oro\Bundle\WorkflowBundle\Exception\InvalidTransitionException;
use Oro\Bundle\WorkflowBundle\Exception\UnknownAttributeException;
use Oro\Bundle\WorkflowBundle\Exception\WorkflowNotFoundException;
use Oro\Bundle\WorkflowBundle\Handler\Helper\TransitionHelper;
use Oro\Bundle\WorkflowBundle\Model\Transition;
use Oro\Bundle\WorkflowBundle\Model\WorkflowData;
use Oro\Bundle\WorkflowBundle\Model\WorkflowManager;
use Oro\Bundle\WorkflowBundle\Serializer\WorkflowAwareSerializer;
use Oro\Bundle\WorkflowBundle\Model\Workflow;

class StartTransitionHandler
{
    /** @var WorkflowManager */
    protected $workflowManager;

    /** @var WorkflowAwareSerializer */
    protected $serializer;

    /** @var TransitionHelper */
    protected $transitionHelper;

    /**
     * @param WorkflowManager $workflowManager
     * @param WorkflowAwareSerializer $serializer
     * @param TransitionHelper $transitionHelper
     */
    public function __construct(
        WorkflowManager $workflowManager,
        WorkflowAwareSerializer $serializer,
        TransitionHelper $transitionHelper
    ) {
        $this->workflowManager = $workflowManager;
        $this->serializer = $serializer;
        $this->transitionHelper = $transitionHelper;
    }

    /**
     * @param Workflow $workflow
     * @param Transition $transition
     * @param string $data
     * @param object $entity
     *
     * @return Response|null
     */
    public function handle(Workflow $workflow, Transition $transition, $data, $entity)
    {
        if ($transition->getPageTemplate() || $transition->getDialogTemplate()) {
            return;
        }

        $responseCode = null;
        $workflowItem = null;
        try {
            $dataArray = [];
            $workflowName = $workflow->getName();
            if ($data) {
                $this->serializer->setWorkflowName($workflowName);
                /* @var $data WorkflowData */
                $data = $this->serializer->deserialize(
                    $data,
                    'Oro\Bundle\WorkflowBundle\Model\WorkflowData',
                    'json'
                );
                $dataArray = $data->getValues();
            }

            $workflowItem = $this->workflowManager->startWorkflow($workflowName, $entity, $transition, $dataArray);
        } catch (HttpException $e) {
            $responseCode = $e->getStatusCode();
        } catch (WorkflowNotFoundException $e) {
            $responseCode = 404;
        } catch (UnknownAttributeException $e) {
            $responseCode = 400;
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
