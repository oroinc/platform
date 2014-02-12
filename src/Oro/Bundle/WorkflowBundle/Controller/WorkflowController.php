<?php

namespace Oro\Bundle\WorkflowBundle\Controller;

use Oro\Bundle\WorkflowBundle\Model\WorkflowManager;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;

class WorkflowController extends Controller
{
    /**
     * @Route(
     *      "/create/{transitionName}/{workflowName}",
     *      name="oro_workflow_start_transition_form"
     * )
     * @Template("OroWorkflowBundle:Workflow:transitionForm.html.twig")
     * @AclAncestor("oro_workflow")
     * @param string $transitionName
     * @param string $workflowName
     * @return array
     * @throws BadRequestHttpException
     */
    public function startTransitionAction($transitionName, $workflowName)
    {
        /** @var WorkflowManager $workflowManager */
        $workflowManager = $this->get('oro_workflow.manager');
        $workflow = $workflowManager->getWorkflow($workflowName);
        $transition = $workflow->getTransitionManager()->getTransition($transitionName);

        return array(
            'transition' => $transition,
            'workflow' => $workflow,
            'transitionUrl' => $this->generateUrl(
                'oro_workflow_api_rest_workflow_start',
                array(
                    'workflowName' => $workflow->getName(),
                    'transitionName' => $transition->getName(),
                    'entityId' => 0
                )
            ),
            'entity' => null
        );
    }
}
