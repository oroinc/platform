<?php

namespace Oro\Bundle\ActivityListBundle\Tests\Unit\Stub;

use Oro\Bundle\ActivityListBundle\Entity\ActivityList;
use Oro\Bundle\ActivityListBundle\Model\ActivityListProviderInterface;
use Oro\Bundle\ActivityListBundle\Model\ActivityListUpdatedByProviderInterface;
use Oro\Bundle\CommentBundle\Model\CommentProviderInterface;

class TestActivityProvider implements
    ActivityListProviderInterface,
    CommentProviderInterface,
    ActivityListUpdatedByProviderInterface
{
    public const SUPPORTED_TARGET_CLASS_NAME = 'Acme\DemoBundle\Entity\CorrectEntity';

    /** @var object[] */
    private $targets;

    #[\Override]
    public function isApplicableTarget($entityClass, $accessible = true)
    {
        if ($entityClass === self::SUPPORTED_TARGET_CLASS_NAME) {
            return true;
        }

        return false;
    }

    #[\Override]
    public function getSubject($entity)
    {
        return $entity->subject;
    }

    #[\Override]
    public function getDescription($entity)
    {
        return $entity->description;
    }

    #[\Override]
    public function getTemplate()
    {
        return 'test_template.js.twig';
    }

    #[\Override]
    public function getRoutes($entity)
    {
        return ['delete' => 'test_delete_route'];
    }

    #[\Override]
    public function getActivityId($entity)
    {
    }

    #[\Override]
    public function isApplicable($entity)
    {
        return $entity instanceof \stdClass;
    }

    #[\Override]
    public function getTargetEntities($entity)
    {
        return $this->targets;
    }

    /**
     * @param object[] $targets
     */
    public function setTargets($targets)
    {
        $this->targets = $targets;
    }

    #[\Override]
    public function getData(ActivityList $activityList)
    {
        return ['test_data'];
    }

    #[\Override]
    public function getOrganization($entity)
    {
    }

    #[\Override]
    public function isCommentsEnabled($entityClass)
    {
        return true;
    }

    #[\Override]
    public function getActivityOwners($entity, ActivityList $activityList)
    {
        return [];
    }

    #[\Override]
    public function isActivityListApplicable(ActivityList $activityList): bool
    {
        return true;
    }

    public function getCreatedAt($entity)
    {
        return $entity->createdAt;
    }

    public function getUpdatedAt($entity)
    {
        return $entity->updatedAt;
    }

    #[\Override]
    public function getUpdatedBy($entity)
    {
        return $entity->updatedBy;
    }

    #[\Override]
    public function getOwner($entity)
    {
        return $entity->owner;
    }
}
