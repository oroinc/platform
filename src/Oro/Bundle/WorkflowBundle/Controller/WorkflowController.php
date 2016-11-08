<?php

namespace Oro\Bundle\WorkflowBundle\Controller;

use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;
use Oro\Bundle\WorkflowBundle\Event\StartTransitionEvent;
use Oro\Bundle\WorkflowBundle\Event\StartTransitionEvents;
use Oro\Bundle\WorkflowBundle\Model\WorkflowManager;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @Route("/workflow")
 */
class WorkflowController extends Controller
{
    const DEFAULT_TRANSITION_TEMPLATE = 'OroWorkflowBundle:Workflow:transitionForm.html.twig';

    /**
     * @Route(
     *      "/start/{workflowName}/{transitionName}",
     *      name="oro_workflow_start_transition_form"
     * )
     * @AclAncestor("oro_workflow")
     * @param string $workflowName
     * @param string $transitionName
     * @param Request $request
     * @return Response
     */
    public function startTransitionAction($workflowName, $transitionName, Request $request)
    {
        /** @var WorkflowManager $workflowManager */
        $workflowManager = $this->get('oro_workflow.manager');
        $workflow = $workflowManager->getWorkflow($workflowName);
        $transition = $workflow->getTransitionManager()->getTransition($transitionName);

        $routeParams = [
            'workflowName' => $workflow->getName(),
            'transitionName' => $transition->getName(),
            'entityId' => $request->get('entityId', 0)
        ];

        // dispatch oro_workflow.start_transition.handle_before_render event
        $event = new StartTransitionEvent($workflow, $transition, $routeParams);
        $this->get('event_dispatcher')->dispatch(
            StartTransitionEvents::HANDLE_BEFORE_RENDER,
            $event
        );
        $routeParams = $event->getRouteParameters();

        return $this->render(
            $transition->getPageTemplate() ?: self::DEFAULT_TRANSITION_TEMPLATE,
            [
                'transition' => $transition,
                'workflow' => $workflow,
                'transitionUrl' => $this->generateUrl(
                    'oro_api_workflow_start',
                    $routeParams
                ),
                'transitionFormUrl' => $this->generateUrl(
                    'oro_workflow_widget_start_transition_form',
                    $routeParams
                )
            ]
        );
    }

    /**
     * @Route(
     *      "/transit/{workflowItemId}/{transitionName}",
     *      name="oro_workflow_transition_form"
     * )
     * @ParamConverter("workflowItem", options={"id"="workflowItemId"})
     * @AclAncestor("oro_workflow")
     * @param string $transitionName
     * @param WorkflowItem $workflowItem
     * @return Response
     */
    public function transitionAction($transitionName, WorkflowItem $workflowItem)
    {
        /** @var WorkflowManager $workflowManager */
        $workflowManager = $this->get('oro_workflow.manager');
        $workflow = $workflowManager->getWorkflow($workflowItem);
        $transition = $workflow->getTransitionManager()->getTransition($transitionName);

        $routeParams = [
            'transitionName' => $transition->getName(),
            'workflowItemId' => $workflowItem->getId(),
        ];

        return $this->render(
            $transition->getPageTemplate() ?: self::DEFAULT_TRANSITION_TEMPLATE,
            [
                'transition' => $transition,
                'workflow' => $workflow,
                'transitionUrl' => $this->generateUrl(
                    'oro_api_workflow_transit',
                    $routeParams
                ),
                'transitionFormUrl' => $this->generateUrl(
                    'oro_workflow_widget_transition_form',
                    $routeParams
                )
            ]
        );
    }
}
