<?php

namespace Oro\Bundle\WorkflowBundle\Controller;

use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;
use Oro\Bundle\WorkflowBundle\Model\WorkflowManager;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

/**
 * @Route("/workflow")
 */
class WorkflowController extends Controller
{
    /**
     * @Route(
     *      "/start/{workflowName}/{transitionName}",
     *      name="oro_workflow_start_transition_form"
     * )
     * @AclAncestor("oro_workflow")
     * @param string $workflowName
     * @param string $transitionName
     * @return array
     */
    public function startTransitionAction($workflowName, $transitionName)
    {
        /** @var WorkflowManager $workflowManager */
        $workflowManager = $this->get('oro_workflow.manager');
        $workflow = $workflowManager->getWorkflow($workflowName);
        $transition = $workflow->getTransitionManager()->getTransition($transitionName);

        $routeParams = array(
            'workflowName' => $workflow->getName(),
            'transitionName' => $transition->getName(),
            'entityId' => $this->getRequest()->get('entityId', 0)
        );

        return $this->render(
            $transition->getPageTemplate() ?: 'OroWorkflowBundle:Workflow:transitionForm.html.twig',
            array(
                'transition' => $transition,
                'workflow' => $workflow,
                'transitionUrl' => $this->generateUrl(
                    'oro_workflow_api_rest_workflow_start',
                    $routeParams
                ),
                'transitionFormUrl' => $this->generateUrl(
                    'oro_workflow_widget_start_transition_form',
                    $routeParams
                )
            )
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
     * @return array
     */
    public function transitionAction($transitionName, WorkflowItem $workflowItem)
    {
        /** @var WorkflowManager $workflowManager */
        $workflowManager = $this->get('oro_workflow.manager');
        $workflow = $workflowManager->getWorkflow($workflowItem);
        $transition = $workflow->getTransitionManager()->getTransition($transitionName);

        $routeParams = array(
            'transitionName' => $transition->getName(),
            'workflowItemId' => $workflowItem->getId(),
        );

        return $this->render(
            $transition->getPageTemplate() ?: 'OroWorkflowBundle:Workflow:transitionForm.html.twig',
            array(
                'transition' => $transition,
                'workflow' => $workflow,
                'transitionUrl' => $this->generateUrl(
                    'oro_workflow_api_rest_workflow_transit',
                    $routeParams
                ),
                'transitionFormUrl' => $this->generateUrl(
                    'oro_workflow_widget_transition_form',
                    $routeParams
                )
            )
        );
    }
}
