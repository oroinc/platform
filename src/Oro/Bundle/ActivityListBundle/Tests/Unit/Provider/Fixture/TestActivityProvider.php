<?php

namespace Oro\Bundle\ActivityListBundle\Tests\Unit\Provider\Fixture;

use Oro\Bundle\ActivityListBundle\Model\ActivityListProviderInterface;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Config\Id\ConfigIdInterface;

class TestActivityProvider implements ActivityListProviderInterface
{
    const ACTIVITY_CLASS_NAME = 'Test\Entity';

    /**
     * {@inheritdoc}
     */
    public function isApplicableTarget(ConfigIdInterface $configId, ConfigManager $configManager)
    {
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
    public function getData($entity)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function getTemplate()
    {
    }

    /**
     * {@inheritdoc}
     */
    public function getRoutes()
    {
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
    }
}
