<?php

namespace Oro\Bundle\ActivityListBundle\Tests\Unit\Provider\Fixture;

use Oro\Bundle\ActivityListBundle\Entity\ActivityList;
use Oro\Bundle\ActivityListBundle\Model\ActivityListProviderInterface;
use Oro\Bundle\ActivityListBundle\Model\ActivityListUpdatedByProviderInterface;
use Oro\Bundle\CommentBundle\Model\CommentProviderInterface;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Config\Id\ConfigIdInterface;

class TestActivityProvider implements
    ActivityListProviderInterface,
    CommentProviderInterface,
    ActivityListUpdatedByProviderInterface
{
    const ACTIVITY_CLASS_NAME = 'Test\Entity';
    const ACL_CLASS = 'Test\Entity';

    const SUPPORTED_TARGET_CLASS_NAME = 'Acme\DemoBundle\Entity\CorrectEntity';

    protected $targets;

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
    public function getRoutes()
    {
        return ['delete' => 'test_delete_route'];
    }

    /**
     * {@inheritdoc}
     */
    public function getActivityClass()
    {
        return self::ACTIVITY_CLASS_NAME;
    }

    /**
     * {@inheritdoc}
     */
    public function getAclClass()
    {
        return self::ACL_CLASS;
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

    public function setTargets($targets)
    {
        $this->targets = $targets;
    }

    /**
     * {@inheritdoc}
     */
    public function getData(ActivityList $activityListEntity)
    {
        return ['test_data'];
    }

    /**
     * {@inheritdoc}
     */
    public function getOrganization($activityEntity)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function isCommentsEnabled($entityClass)
    {
        return true;
    }

    public function getActivityOwners($entity, ActivityList $activity)
    {
        return [];
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
