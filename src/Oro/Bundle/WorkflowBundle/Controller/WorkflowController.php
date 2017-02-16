<?php

namespace Oro\Bundle\WorkflowBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;
use Oro\Bundle\WorkflowBundle\Event\StartTransitionEvent;
use Oro\Bundle\WorkflowBundle\Event\StartTransitionEvents;
use Oro\Bundle\WorkflowBundle\Model\WorkflowManager;

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
     * @param string $workflowName
     * @param string $transitionName
     * @param Request $request
     *
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

        if (!$transition->isEmptyInitOptions()) {
            $context = $this->get('oro_action.helper.context')->getContext();
            $routeParams = array_merge($routeParams, $context);
        }

        // dispatch oro_workflow.start_transition.handle_before_render event
        $event = new StartTransitionEvent($workflow, $transition, $routeParams);
        $this->get('event_dispatcher')->dispatch(
            StartTransitionEvents::HANDLE_BEFORE_RENDER,
            $event
        );

        $routeParams = $event->getRouteParameters();
        $routeProvider = $this->container->get('oro_workflow.provider.start_transition_route');

        return $this->render(
            $transition->getPageTemplate() ?: self::DEFAULT_TRANSITION_TEMPLATE,
            [
                'transition' => $transition,
                'workflow' => $workflow,
                'transitionUrl' => $this->generateUrl(
                    $routeProvider->getExecutionRoute(),
                    $routeParams
                ),
                'transitionFormUrl' => $this->generateUrl(
                    $routeProvider->getFormDialogRoute(),
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
     *
     * @param string $transitionName
     * @param WorkflowItem $workflowItem
     *
     * @return Response
     *
     * @throws \LogicException
     * @throws \Oro\Bundle\WorkflowBundle\Exception\WorkflowException
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
        $routeProvider = $this->container->get('oro_workflow.provider.transition_route');

        return $this->render(
            $transition->getPageTemplate() ?: self::DEFAULT_TRANSITION_TEMPLATE,
            [
                'transition' => $transition,
                'workflow' => $workflow,
                'transitionUrl' => $this->generateUrl(
                    $routeProvider->getExecutionRoute(),
                    $routeParams
                ),
                'transitionFormUrl' => $this->generateUrl(
                    $routeProvider->getFormDialogRoute(),
                    $routeParams
                )
            ]
        );
    }
}
