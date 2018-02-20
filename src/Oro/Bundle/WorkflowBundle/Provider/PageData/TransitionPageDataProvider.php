<?php

namespace Oro\Bundle\WorkflowBundle\Provider\PageData;

use Oro\Bundle\ActionBundle\Provider\RouteProviderInterface;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;
use Oro\Bundle\WorkflowBundle\Exception\InvalidTransitionException;
use Oro\Bundle\WorkflowBundle\Model\Transition;
use Oro\Bundle\WorkflowBundle\Model\WorkflowManager;
use Symfony\Component\Routing\RouterInterface;

class TransitionPageDataProvider
{
    /** @var WorkflowManager */
    private $workflowManager;

    /** @var RouteProviderInterface */
    private $routeProvider;

    /** @var RouterInterface */
    private $router;

    /**
     * @param WorkflowManager $workflowManager
     * @param RouteProviderInterface $routeProvider
     * @param RouterInterface $router
     */
    public function __construct(
        WorkflowManager $workflowManager,
        RouteProviderInterface $routeProvider,
        RouterInterface $router
    ) {
        $this->workflowManager = $workflowManager;
        $this->routeProvider = $routeProvider;
        $this->router = $router;
    }

    /**
     * @param string $transition
     * @param WorkflowItem $workflowItem
     *
     * @return array
     * @throws InvalidTransitionException
     */
    public function getData(string $transition, WorkflowItem $workflowItem): array
    {
        $workflow = $this->workflowManager->getWorkflow($workflowItem);
        $transition = $workflow->getTransitionManager()->getTransition($transition);

        if (!$transition instanceof Transition) {
            throw InvalidTransitionException::unknownTransition($transition);
        }

        $routeParams = [
            'transitionName' => $transition->getName(),
            'workflowItemId' => $workflowItem->getId(),
        ];

        return array_merge(
            [
                'transition' => $transition,
                'workflow' => $workflow
            ],
            $this->getUrls($routeParams)
        );
    }

    /**
     * @param array $parameters
     * @return array
     */
    protected function getUrls(array $parameters): array
    {
        return [
            'transitionUrl' => $this->router->generate($this->routeProvider->getExecutionRoute(), $parameters),
            'transitionFormUrl' => $this->router->generate($this->routeProvider->getFormDialogRoute(), $parameters)
        ];
    }
}
