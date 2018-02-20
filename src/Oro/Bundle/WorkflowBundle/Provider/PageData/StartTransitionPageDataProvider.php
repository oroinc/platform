<?php

namespace Oro\Bundle\WorkflowBundle\Provider\PageData;

use Oro\Bundle\ActionBundle\Helper\ContextHelper;
use Oro\Bundle\ActionBundle\Provider\RouteProviderInterface;
use Oro\Bundle\WorkflowBundle\Event\StartTransitionEvent;
use Oro\Bundle\WorkflowBundle\Event\StartTransitionEvents;
use Oro\Bundle\WorkflowBundle\Exception\InvalidTransitionException;
use Oro\Bundle\WorkflowBundle\Model\Transition;
use Oro\Bundle\WorkflowBundle\Model\Workflow;
use Oro\Bundle\WorkflowBundle\Model\WorkflowManager;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Routing\RouterInterface;

class StartTransitionPageDataProvider
{
    /** @var WorkflowManager */
    private $workflowManager;

    /** @var ContextHelper */
    private $contextHelper;

    /** @var EventDispatcherInterface */
    private $eventDispatcher;

    /** @var RouteProviderInterface */
    private $routeProvider;

    /** @var RouterInterface */
    private $router;

    /**
     * @param WorkflowManager $workflowManager
     * @param ContextHelper $contextHelper
     * @param EventDispatcherInterface $eventDispatcher
     * @param RouteProviderInterface $routeProvider
     * @param RouterInterface $router
     */
    public function __construct(
        WorkflowManager $workflowManager,
        ContextHelper $contextHelper,
        EventDispatcherInterface $eventDispatcher,
        RouteProviderInterface $routeProvider,
        RouterInterface $router
    ) {
        $this->workflowManager = $workflowManager;
        $this->contextHelper = $contextHelper;
        $this->eventDispatcher = $eventDispatcher;
        $this->routeProvider = $routeProvider;
        $this->router = $router;
    }

    /**
     * @param string $workflowName
     * @param string $transitionName
     * @param mixed $entityId
     *
     * @return array
     * @throws InvalidTransitionException
     */
    public function getData(string $workflowName, string $transitionName, $entityId): array
    {
        $workflow = $this->workflowManager->getWorkflow($workflowName);
        $transition = $workflow->getTransitionManager()->getTransition($transitionName);

        if (!$transition instanceof Transition) {
            throw InvalidTransitionException::unknownTransition($transition);
        }

        $event = $this->dispatch($workflow, $transition, $entityId);

        return array_merge(
            [
                'transition' => $transition,
                'workflow' => $workflow,
            ],
            $this->getUrls($event->getRouteParameters())
        );
    }

    /**
     * @param Workflow $workflow
     * @param Transition $transition
     * @param $entityId
     *
     * @return StartTransitionEvent
     */
    private function dispatch(Workflow $workflow, Transition $transition, $entityId): StartTransitionEvent
    {
        $event = $this->createEvent($workflow, $transition, $entityId);

        $this->eventDispatcher->dispatch(StartTransitionEvents::HANDLE_BEFORE_RENDER, $event);

        return $event;
    }

    /**
     * @param Workflow $workflow
     * @param Transition $transition
     * @param mixed $entityId
     * @return StartTransitionEvent
     */
    private function createEvent(Workflow $workflow, Transition $transition, $entityId): StartTransitionEvent
    {
        $routeParams = [
            'workflowName' => $workflow->getName(),
            'transitionName' => $transition->getName(),
            'entityId' => $entityId
        ];

        if (!$transition->isEmptyInitOptions()) {
            $context = $this->contextHelper->getContext();
            $routeParams = array_merge($routeParams, $context);
        }

        return new StartTransitionEvent($workflow, $transition, $routeParams);
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
