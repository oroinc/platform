<?php

namespace Oro\Bundle\WorkflowBundle\Controller;

use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;
use Oro\Bundle\WorkflowBundle\Model\Transition;
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
     * @param string $workflowName
     * @param string $transitionName
     * @param Request $request
     *
     * @return Response
     */
    public function startTransitionAction($workflowName, $transitionName, Request $request)
    {
        $presenter = $this->get('oro_workflow.provider.page_data.start_transition');

        return $this->buildResponse(
            $presenter->getData($workflowName, $transitionName, $request->get('entityId', 0))
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
        $presenter = $this->get('oro_workflow.provider.page_data.transition');

        return $this->buildResponse(
            $presenter->getData($transitionName, $workflowItem)
        );
    }

    /**
     * @param array $data
     * @return Response
     */
    private function buildResponse(array $data)
    {
        /** @var Transition $transition */
        $transition = $data['transition'];

        return $this->render(
            $transition->getPageTemplate() ?: self::DEFAULT_TRANSITION_TEMPLATE,
            $data
        );
    }
}
