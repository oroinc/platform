<?php

namespace Oro\Bundle\WorkflowBundle\Extension;

use Doctrine\Common\Collections\ArrayCollection;

use Oro\Bundle\ActionBundle\Extension\ButtonProviderExtensionInterface;
use Oro\Bundle\ActionBundle\Model\ButtonContext;
use Oro\Bundle\ActionBundle\Model\ButtonSearchContext;
use Oro\Bundle\ActionBundle\Model\OperationRegistry;
use Oro\Bundle\ActionBundle\Provider\RouteProviderInterface;

use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;
use Oro\Bundle\WorkflowBundle\Model\Transition;
use Oro\Bundle\WorkflowBundle\Model\TransitionButton;
use Oro\Bundle\WorkflowBundle\Model\Workflow;
use Oro\Bundle\WorkflowBundle\Model\WorkflowData;
use Oro\Bundle\WorkflowBundle\Model\WorkflowRegistry;

class TransitionButtonProviderExtension implements ButtonProviderExtensionInterface
{
    /** @var WorkflowRegistry */
    protected $workflowRegistry;

    /** @var RouteProviderInterface */
    protected $routeProvider;

    /** @var ButtonContext */
    private $baseButtonContext;

    /**
     * @param WorkflowRegistry $workflowRegistry
     * @param RouteProviderInterface $routeProvider
     */
    public function __construct(WorkflowRegistry $workflowRegistry, RouteProviderInterface $routeProvider)
    {
        $this->workflowRegistry = $workflowRegistry;
        $this->routeProvider = $routeProvider;
    }

    /**
     * {@inheritdoc}
     */
    public function find(ButtonSearchContext $buttonSearchContext)
    {
        $buttons = [];

        $group = $buttonSearchContext->getGroup();

        // Skip if custom buttons group defined
        if ($group && ($group !== OperationRegistry::DEFAULT_GROUP)) {
            return $buttons;
        }

        // Skip if DataGrid defined
        if ($buttonSearchContext->getGridName()) {
            return $buttons;
        }

        foreach ($this->workflowRegistry->getActiveWorkflows() as $workflow) {
            $transitions = $this->getInitTransitions($workflow, $buttonSearchContext);

            foreach ($transitions as $transition) {
                $workflowItem = $this->buildWorkflowItem($transition, $workflow, $buttonSearchContext);
                $errors = new ArrayCollection();
                try {
                    $isAvailable = $transition->isAvailable(clone $workflowItem, $errors);
                } catch (\Exception $e) {
                    $isAvailable = false;
                    $errors->add(['message' => $e->getMessage(), 'parameters' => []]);
                }
                if ($isAvailable || !$transition->isUnavailableHidden()) {
                    $buttonContext = $this->generateButtonContext($transition, $buttonSearchContext);
                    $buttonContext->setEnabled($isAvailable);
                    $buttonContext->setErrors($errors->toArray());
                    $buttons[] = new TransitionButton($transition, $workflow, $buttonContext);
                }
            }
        }

        $this->baseButtonContext = null;

        return $buttons;
    }

    /**
     * @param Transition $transition
     * @param Workflow $workflow
     * @param ButtonSearchContext $searchContext
     *
     * @return WorkflowItem
     */
    protected function buildWorkflowItem(Transition $transition, Workflow $workflow, ButtonSearchContext $searchContext)
    {
        $workflowData = new WorkflowData([$transition->getInitContextAttribute() => $searchContext]);
        $workflowItem = new WorkflowItem();

        return $workflowItem->setEntityClass($workflow->getDefinition()->getRelatedEntity())
            ->setDefinition($workflow->getDefinition())
            ->setWorkflowName($workflow->getName())
            ->setData($workflowData);
    }

    /**
     * @param Transition $transition
     * @param ButtonSearchContext $searchContext
     *
     * @return ButtonContext
     */
    protected function generateButtonContext(Transition $transition, ButtonSearchContext $searchContext)
    {
        if (!$this->baseButtonContext) {
            $this->baseButtonContext = new ButtonContext();
            $this->baseButtonContext->setDatagridName($searchContext->getGridName())
                ->setEntity($searchContext->getEntityClass(), $searchContext->getEntityId())
                ->setRouteName($searchContext->getRouteName())
                ->setGroup($searchContext->getGroup())
                ->setExecutionRoute($this->routeProvider->getExecutionRoute());
        }

        $context = clone $this->baseButtonContext;
        $context->setUnavailableHidden($transition->isUnavailableHidden());

        if ($transition->hasForm()) {
            $context->setFormDialogRoute($this->routeProvider->getFormDialogRoute());
            $context->setFormPageRoute($this->routeProvider->getFormPageRoute());
        }

        return $context;
    }

    /**
     * @param Workflow $workflow
     * @param ButtonSearchContext $searchContext
     *
     * @return Transition[]
     */
    protected function getInitTransitions(Workflow $workflow, ButtonSearchContext $searchContext)
    {
        $transitionNames = [];
        if ($searchContext->getEntityClass()) {
            $entities = $workflow->getInitEntities();
            if (array_key_exists($searchContext->getEntityClass(), $entities)) {
                $transitionNames = $entities[$searchContext->getEntityClass()];
            }
        }
        if ($searchContext->getRouteName()) {
            $routes = $workflow->getInitRoutes();
            if (array_key_exists($searchContext->getRouteName(), $routes)) {
                $transitionNames = array_merge($transitionNames, $routes[$searchContext->getRouteName()]);
            }
        }

        return array_filter(
            $workflow->getTransitionManager()->getStartTransitions()->toArray(),
            function (Transition $transition) use ($transitionNames) {
                return in_array($transition->getName(), $transitionNames, true);
            }
        );
    }
}
