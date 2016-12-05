<?php

namespace Oro\Bundle\WorkflowBundle\Extension;

use Doctrine\Common\Collections\Collection;

use Oro\Bundle\ActionBundle\Button\ButtonContext;
use Oro\Bundle\ActionBundle\Button\ButtonInterface;
use Oro\Bundle\ActionBundle\Button\ButtonSearchContext;
use Oro\Bundle\ActionBundle\Exception\UnsupportedButtonException;
use Oro\Bundle\ActionBundle\Extension\ButtonProviderExtensionInterface;
use Oro\Bundle\ActionBundle\Model\OperationRegistry;
use Oro\Bundle\ActionBundle\Provider\RouteProviderInterface;

use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;
use Oro\Bundle\WorkflowBundle\Model\Transition;
use Oro\Bundle\WorkflowBundle\Model\TransitionButton;
use Oro\Bundle\WorkflowBundle\Model\Workflow;
use Oro\Bundle\WorkflowBundle\Model\WorkflowData;
use Oro\Bundle\WorkflowBundle\Model\WorkflowRegistry;

class StartTransitionButtonProviderExtension implements ButtonProviderExtensionInterface
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

        foreach ($this->workflowRegistry->getActiveWorkflows() as $workflow) {
            $transitions = $this->getInitTransitions($workflow, $buttonSearchContext);

            foreach ($transitions as $transition) {
                $buttonContext = $this->generateButtonContext($transition, $buttonSearchContext);
                $buttons[] = new TransitionButton($transition, $workflow, $buttonContext);
            }
        }

        $this->baseButtonContext = null;

        return $buttons;
    }

    /**
     * {@inheritdoc}
     * @param TransitionButton $button
     */
    public function isAvailable(
        ButtonInterface $button,
        ButtonSearchContext $buttonSearchContext,
        Collection $errors = null
    ) {
        if (!$this->supports($button)) {
            throw new UnsupportedButtonException(
                sprintf(
                    'Button %s is not supported by %s. Can not determine availability.',
                    get_class($button),
                    get_class($this)
                )
            );
        }

        $workflowItem = $this->buildWorkflowItem(
            $button->getTransition(),
            $button->getWorkflow(),
            $buttonSearchContext
        );

        try {
            $isAvailable = $button->getTransition()->isAvailable($workflowItem, $errors);
        } catch (\Exception $e) {
            $isAvailable = false;
            if (null !== $errors) {
                $errors->add(['message' => $e->getMessage(), 'parameters' => []]);
            }
        }

        return $isAvailable;
    }

    /**
     * {@inheritdoc}
     */
    public function supports(ButtonInterface $button)
    {
        return $button instanceof TransitionButton && $button->getTransition()->isStart();
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
        $workflowItem = new WorkflowItem();

        return $workflowItem->setEntityClass($workflow->getDefinition()->getRelatedEntity())
            ->setDefinition($workflow->getDefinition())
            ->setWorkflowName($workflow->getName())
            ->setData(new WorkflowData([$transition->getInitContextAttribute() => $searchContext]));
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
            $this->baseButtonContext->setDatagridName($searchContext->getDatagrid())
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
        $transitionNames = array_merge(
            $this->getNodeInitTransitions($searchContext->getEntityClass(), $workflow->getInitEntities()),
            $this->getNodeInitTransitions($searchContext->getRouteName(), $workflow->getInitRoutes()),
            $this->getNodeInitTransitions($searchContext->getDatagrid(), $workflow->getInitDatagrids())
        );

        return array_filter(
            $workflow->getTransitionManager()->getStartTransitions()->toArray(),
            function (Transition $transition) use ($transitionNames) {
                return in_array($transition->getName(), $transitionNames, true);
            }
        );
    }

    /**
     * @param $value
     * @param array|null $data
     * @return array
     */
    private function getNodeInitTransitions($value, array $data = null)
    {
        return ($data && array_key_exists($value, $data)) ? $data[$value] : [];
    }
}
