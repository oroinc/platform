<?php

namespace Oro\Bundle\WorkflowBundle\Extension;

use Doctrine\Common\Collections\ArrayCollection;

use Oro\Bundle\ActionBundle\Exception\UnsupportedButtonException;
use Oro\Bundle\ActionBundle\Extension\ButtonProviderExtensionInterface;
use Oro\Bundle\ActionBundle\Button\ButtonContext;
use Oro\Bundle\ActionBundle\Button\ButtonInterface;
use Oro\Bundle\ActionBundle\Button\ButtonSearchContext;
use Oro\Bundle\ActionBundle\Provider\RouteProviderInterface;

use Oro\Bundle\WorkflowBundle\Model\Transition;
use Oro\Bundle\WorkflowBundle\Model\Workflow;
use Oro\Bundle\WorkflowBundle\Model\WorkflowRegistry;

abstract class AbstractButtonProviderExtension implements ButtonProviderExtensionInterface
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
        $groups = (array) $buttonSearchContext->getGroup();

        // Skip if custom buttons group defined
        if ($groups &&
            !in_array(ButtonInterface::DEFAULT_GROUP, $groups, true) &&
            !in_array('datagridRowAction', $groups, true)
        ) {
            return $buttons;
        }

        foreach ($this->getActiveWorkflows() as $workflow) {
            $transitions = $this->getTransitions($workflow, $buttonSearchContext);

            foreach ($transitions as $transition) {
                $buttonContext = $this->generateButtonContext($transition, $buttonSearchContext);
                $buttons[] = $this->createTransitionButton($transition, $workflow, $buttonContext);
            }
        }

        $this->baseButtonContext = null;

        return $buttons;
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
     * @param ButtonInterface $button
     *
     * @return UnsupportedButtonException
     */
    protected function createUnsupportedButtonException(ButtonInterface $button)
    {
        return new UnsupportedButtonException(
            sprintf(
                'Button %s is not supported by %s. Can not determine availability.',
                get_class($button),
                get_class($this)
            )
        );
    }

    /**
     * @return Workflow[]|ArrayCollection
     */
    protected function getActiveWorkflows()
    {
        return $this->workflowRegistry->getActiveWorkflows();
    }

    /**
     * @param Workflow $workflow
     * @param ButtonSearchContext $searchContext
     *
     * @return Transition[]
     */
    abstract protected function getTransitions(Workflow $workflow, ButtonSearchContext $searchContext);

    /**
     * @param Transition $transition
     * @param Workflow $workflow
     * @param ButtonContext $buttonContext
     *
     * @return ButtonInterface
     */
    abstract protected function createTransitionButton(
        Transition $transition,
        Workflow $workflow,
        ButtonContext $buttonContext
    );
}
