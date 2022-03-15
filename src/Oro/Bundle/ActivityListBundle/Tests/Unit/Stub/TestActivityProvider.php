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

    /**
     * {@inheritdoc}
     */
    public function isApplicableTarget($entityClass, $accessible = true)
    {
        if ($entityClass === self::SUPPORTED_TARGET_CLASS_NAME) {
            return true;
        }

        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function getSubject($entity)
    {
        return $entity->subject;
    }

    /**
     * {@inheritdoc}
     */
    public function getDescription($entity)
    {
        return $entity->description;
    }

    /**
     * {@inheritdoc}
     */
    public function getTemplate()
    {
        return 'test_template.js.twig';
    }

    /**
     * {@inheritdoc}
     */
    public function getRoutes($entity)
    {
        return ['delete' => 'test_delete_route'];
    }

    /**
     * {@inheritdoc}
     */
    public function getActivityId($entity)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function isApplicable($entity)
    {
        return $entity instanceof \stdClass;
    }

    /**
     * {@inheritdoc}
     */
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

    /**
     * {@inheritdoc}
     */
    public function getData(ActivityList $activityList)
    {
        return ['test_data'];
    }

    /**
     * {@inheritdoc}
     */
    public function getOrganization($entity)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function isCommentsEnabled($entityClass)
    {
        return true;
    }

    public function getActivityOwners($entity, ActivityList $activityList)
    {
        return [];
    }

    /**
     * {@inheritDoc}
     */
    public function isActivityListApplicable(ActivityList $activityList): bool
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function getCreatedAt($entity)
    {
        return $entity->createdAt;
    }

    /**
     * {@inheritdoc}
     */
    public function getUpdatedAt($entity)
    {
        return $entity->updatedAt;
    }

    /**
     * {@inheritdoc}
     */
    public function getUpdatedBy($entity)
    {
        return $entity->updatedBy;
    }

    /**
     * {@inheritdoc}
     */
    public function getOwner($entity)
    {
        return $entity->owner;
    }
}
