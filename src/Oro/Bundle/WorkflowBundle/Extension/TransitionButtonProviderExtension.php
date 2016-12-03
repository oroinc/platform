<?php

namespace Oro\Bundle\WorkflowBundle\Extension;

use Doctrine\Common\Collections\Collection;

use Oro\Bundle\ActionBundle\Model\ButtonContext;
use Oro\Bundle\ActionBundle\Model\ButtonInterface;
use Oro\Bundle\ActionBundle\Model\ButtonSearchContext;
use Oro\Bundle\ActionBundle\Provider\RouteProviderInterface;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\WorkflowBundle\Entity\Repository\WorkflowItemRepository;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;
use Oro\Bundle\WorkflowBundle\Model\Transition;
use Oro\Bundle\WorkflowBundle\Model\TransitionButton;
use Oro\Bundle\WorkflowBundle\Model\Workflow;
use Oro\Bundle\WorkflowBundle\Model\WorkflowRegistry;

class TransitionButtonProviderExtension extends AbstractButtonProviderExtension
{
    /** @var DoctrineHelper */
    protected $doctrineHelper;

    /**
     * @param WorkflowRegistry $workflowRegistry
     * @param RouteProviderInterface $routeProvider
     * @param DoctrineHelper $doctrineHelper
     */
    public function __construct(
        WorkflowRegistry $workflowRegistry,
        RouteProviderInterface $routeProvider,
        DoctrineHelper $doctrineHelper
    ) {
        $this->doctrineHelper = $doctrineHelper;
        parent::__construct($workflowRegistry, $routeProvider);
    }

    /**
     * {@inheritdoc}
     */
    public function supports(ButtonInterface $button)
    {
        return $button instanceof TransitionButton;
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
            throw $this->createUnsupportedButtonException($button);
        }

        $workflowItem = $this->getWorkflowItem($button->getWorkflow(), $buttonSearchContext);

        if ($workflowItem === null) {
            return false;
        }

        $transition = $button->getTransition();
        $workflow = $button->getWorkflow();
        try {
            $isAvailable = $workflow->isTransitionAllowed($workflowItem, $transition, $errors);
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
    protected function getTransitions(Workflow $workflow, ButtonSearchContext $searchContext)
    {
        if ($searchContext->getDatagrid() &&
            in_array($searchContext->getDatagrid(), $workflow->getDefinition()->getDatagrids()) &&
            $workflow->getDefinition()->getRelatedEntity() === $searchContext->getEntityClass()
        ) {
            $transitions = $workflow->getTransitionManager()->getTransitions()->toArray();
            return array_filter($transitions, function (Transition $transition) {
                return !$transition->isStart();
            });
        }

        return [];
    }

    /**
     * {@inheritdoc}
     */
    protected function createTransitionButton(
        Transition $transition,
        Workflow $workflow,
        ButtonContext $buttonContext
    ) {
        return new TransitionButton($transition, $workflow, $buttonContext);
    }

    /**
     * @param Workflow $workflow
     * @param ButtonSearchContext $searchContext
     * @return null|WorkflowItem
     */
    protected function getWorkflowItem(Workflow $workflow, ButtonSearchContext $searchContext)
    {
        return $this->getWorkflowItemRepository()->findOneByEntityMetadata(
            $searchContext->getEntityClass(),
            $searchContext->getEntityId(),
            $workflow->getName()
        );
    }

    /**
     * @return WorkflowItemRepository
     */
    protected function getWorkflowItemRepository()
    {
        return $this->doctrineHelper->getEntityRepositoryForClass(WorkflowItem::class);
    }
}
