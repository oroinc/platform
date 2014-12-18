<?php

namespace Oro\Bundle\ActivityListBundle\Placeholder;

use Doctrine\Common\Persistence\ManagerRegistry;

use Oro\Bundle\ActivityListBundle\Provider\ActivityListChainProvider;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;

class PlaceholderFilter
{
    /** @var ActivityListChainProvider */
    protected $activityListProvider;

    /** @var ManagerRegistry */
    protected $doctrine;

    /** @var DoctrineHelper */
    protected $doctrineHelper;

    /**
     * @param ActivityListChainProvider $activityListChainProvider
     * @param ManagerRegistry           $doctrine
     * @param DoctrineHelper            $doctrineHelper
     */
    public function __construct(
        ActivityListChainProvider $activityListChainProvider,
        ManagerRegistry $doctrine,
        DoctrineHelper $doctrineHelper
    ) {
        $this->activityListProvider = $activityListChainProvider;
        $this->doctrine             = $doctrine;
        $this->doctrineHelper       = $doctrineHelper;
    }

    /**
     * Checks if the entity can have activities
     *
     * @param object|null $entity
     * @return bool
     */
    public function isApplicable($entity = null)
    {
        if (null === $entity || !is_object($entity)) {
            return false;
        }

        $entityClass      = $this->doctrineHelper->getEntityClass($entity);
        $id               = $this->doctrineHelper->getSingleEntityIdentifier($entity);
        $activityListRepo = $this->doctrine->getRepository('OroActivityListBundle:ActivityList');

        return in_array($entityClass, $this->activityListProvider->getTargetEntityClasses())
            || (bool)$activityListRepo->getRecordsCountForTargetClassAndId($entityClass, $id);
    }
}
