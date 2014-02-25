<?php

namespace Oro\Bundle\WorkflowBundle\Controller;

use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;
use Oro\Bundle\WorkflowBundle\Model\WorkflowManager;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

use Oro\Bundle\SecurityBundle\Annotation\Acl;
use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;

class WorkflowController extends Controller
{
    /**
     * @Route(
     *      "/{workflowName}/start/{transitionName}",
     *      name="oro_workflow_start_transition_form"
     * )
     * @Template("OroWorkflowBundle:Workflow:transitionForm.html.twig")
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
        return array(
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
        );
    }

    /**
     * @Route(
     *      "/transit/{transitionName}/{workflowItemId}",
     *      name="oro_workflow_transition_form"
     * )
     * @ParamConverter("workflowItem", options={"id"="workflowItemId"})
     * @Template("OroWorkflowBundle:Workflow:transitionForm.html.twig")
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
        return array(
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
        );
    }

    /**
     * @Route(
     *      "/{_format}",
     *      name="oro_workflow_definition_index",
     *      requirements={"_format"="html|json"},
     *      defaults={"_format" = "html"}
     * )
     * @Template
     * * @Acl(
     *      id="oro_workflow_definition_view",
     *      type="entity",
     *      class="OroWorkflowBundle:WorkflowDefinition",
     *      permission="VIEW"
     * )
     * @return array
     */
    public function indexAction()
    {
        return array();
    }

    /**
     * @Route(
     *      "/create",
     *      name="oro_workflow_definition_create"
     * )
     * @Template("OroWorkflowBundle:Workflow:update.html.twig")
     * @Acl(
     *      id="oro_workflow_definition_create",
     *      type="entity",
     *      class="OroWorkflowBundle:WorkflowDefinition",
     *      permission="CREATE"
     * )
     * @return array
     */
    public function createAction()
    {
        return $this->updateAction(new WorkflowDefinition());
    }

    /**
     * @Route(
     *      "/update/{name}",
     *      name="oro_workflow_definition_update"
     * )
     * @Template("OroWorkflowBundle:Workflow:update.html.twig")
     * @Acl(
     *      id="oro_workflow_definition_update",
     *      type="entity",
     *      class="OroWorkflowBundle:WorkflowDefinition",
     *      permission="EDIT"
     * )
     * @param WorkflowDefinition $workflowDefinition
     * @return array
     */
    public function updateAction(WorkflowDefinition $workflowDefinition)
    {
        $form = $this->get('oro_workflow.form.workflow_definition');
        $form->setData($workflowDefinition);
        return array(
            'form' => $form->createView(),
            'entity' => $workflowDefinition
        );
    }
}
