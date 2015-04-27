<?php

namespace Oro\Bundle\ActivityListBundle\Tests\Unit\Provider\Fixture;

use Oro\Bundle\ActivityListBundle\Entity\ActivityList;
use Oro\Bundle\ActivityListBundle\Model\ActivityListProviderInterface;
use Oro\Bundle\CommentBundle\Model\CommentProviderInterface;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Config\Id\ConfigIdInterface;

class TestActivityProvider implements ActivityListProviderInterface, CommentProviderInterface
{
    const ACTIVITY_CLASS_NAME = 'Test\Entity';

    protected $targets;

    /**
     * {@inheritdoc}
     */
    public function isApplicableTarget(ConfigIdInterface $configId, ConfigManager $configManager)
    {
        if ($configId->getClassName() === 'Acme\\DemoBundle\\Entity\\CorrectEntity') {
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
    public function hasComments(ConfigManager $configManager, $entity)
    {
        return true;
    }

    public function getCommentCountProvider()
    {
        return false;
    }
}
