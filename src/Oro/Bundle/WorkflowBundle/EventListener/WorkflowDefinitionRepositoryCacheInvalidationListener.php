<?php

namespace Oro\Bundle\WorkflowBundle\EventListener;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\WorkflowBundle\Entity\Repository\WorkflowDefinitionRepository;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;

class WorkflowDefinitionRepositoryCacheInvalidationListener
{
    /** @var DoctrineHelper */
    protected $doctrineHelper;

    /**
     * @param DoctrineHelper $doctrineHelper
     */
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
