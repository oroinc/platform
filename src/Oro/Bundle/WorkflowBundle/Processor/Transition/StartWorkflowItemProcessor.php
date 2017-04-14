<?php

namespace Oro\Bundle\WorkflowBundle\Processor\Transition;

use Oro\Bundle\EntityBundle\Exception\NotManageableEntityException;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\WorkflowBundle\Processor\Context\TransitionContext;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class StartWorkflowItemProcessor implements ProcessorInterface
{
    /** @var DoctrineHelper */
    private $doctrineHelper;

    /**
     * @param DoctrineHelper $doctrineHelper
     */
    public function __construct(DoctrineHelper $doctrineHelper)
    {
        $this->doctrineHelper = $doctrineHelper;
    }

    /**
     * @param ContextInterface|TransitionContext $context
     */
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
