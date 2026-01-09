<?php

namespace Oro\Bundle\WorkflowBundle\EventListener;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\WorkflowBundle\Entity\Repository\WorkflowDefinitionRepository;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;

/**
 * Listens to events that require invalidation of the workflow definition repository cache.
 *
 * This listener ensures that cached workflow definitions are cleared when changes occur,
 * forcing fresh data to be loaded from the database on the next query.
 */
class WorkflowDefinitionRepositoryCacheInvalidationListener
{
    /** @var DoctrineHelper */
    protected $doctrineHelper;

    public function __construct(DoctrineHelper $doctrineHelper)
    {
        $this->doctrineHelper = $doctrineHelper;
    }

    public function invalidateCache()
    {
        /** @var WorkflowDefinitionRepository $repo */
        $repo = $this->doctrineHelper->getEntityRepositoryForClass(WorkflowDefinition::class);
        $repo->invalidateCache();
    }
}
