<?php

namespace Oro\Bundle\WorkflowBundle\Controller;

use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;
use Oro\Bundle\WorkflowBundle\Model\Transition;
use Oro\Bundle\WorkflowBundle\Provider\PageData\StartTransitionPageDataProvider;
use Oro\Bundle\WorkflowBundle\Provider\PageData\TransitionPageDataProvider;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Workflow controller
 * @Route("/workflow")
 */
class WorkflowController extends AbstractController
{
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
        return $this->buildResponse(
            $this->get(StartTransitionPageDataProvider::class)
                ->getData($workflowName, $transitionName, $request->get('entityId', 0))
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
        return $this->buildResponse(
            $this->get(TransitionPageDataProvider::class)->getData($transitionName, $workflowItem)
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
            $transition->getPageTemplate() ?: '@OroWorkflow/Workflow/transitionForm.html.twig',
            $data
        );
    }

    /**
     * {@inheritDoc}
     */
    public static function getSubscribedServices(): array
    {
        return array_merge(parent::getSubscribedServices(), [
            StartTransitionPageDataProvider::class,
            TransitionPageDataProvider::class,
        ]);
    }
}
