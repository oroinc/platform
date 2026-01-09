<?php

namespace Oro\Bundle\WorkflowBundle\Processor\Transition;

use Oro\Bundle\EntityBundle\Exception\NotManageableEntityException;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\WorkflowBundle\Processor\Context\TransitionContext;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

/**
 * Creates workflow items for workflow start transitions.
 *
 * This processor creates a new workflow item for start transitions. It retrieves or creates the entity
 * that the workflow will operate on based on the entity ID from the context. If an entity ID is provided,
 * it loads the existing entity; otherwise, it creates a new instance. The processor then creates a workflow
 * item from the entity and initialization data, making it available for subsequent processing.
 * Errors during entity retrieval or creation are converted to HTTP exceptions.
 */
class StartWorkflowItemProcessor implements ProcessorInterface
{
    /** @var DoctrineHelper */
    private $doctrineHelper;

    public function __construct(DoctrineHelper $doctrineHelper)
    {
        $this->doctrineHelper = $doctrineHelper;
    }

    /**
     * @param ContextInterface|TransitionContext $context
     */
    #[\Override]
    public function process(ContextInterface $context)
    {
        if ($context->hasError() || $context->hasWorkflowItem()) {
            return;
        }

        $workflow = $context->getWorkflow();

        $entityClass = $workflow->getDefinition()->getRelatedEntity();
        $entityId = $context->get(TransitionContext::ENTITY_ID);

        try {
            if ($entityId) {
                $entity = $this->doctrineHelper->getEntityReference($entityClass, $entityId);
            } else {
                $entity = $this->doctrineHelper->createEntityInstance($entityClass);
            }
        } catch (NotManageableEntityException $e) {
            $context->setError(new BadRequestHttpException($e->getMessage(), $e));
            $context->setFirstGroup('normalize');

            return;
        }

        $workflowItem = $workflow->createWorkflowItem($entity, $context->get(TransitionContext::INIT_DATA));
        $context->setWorkflowItem($workflowItem);
    }
}
