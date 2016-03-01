<?php

namespace Oro\Bundle\ActivityBundle\Provider;

use Doctrine\ORM\Mapping\ClassMetadata;

use Oro\Bundle\ActivityBundle\EntityConfig\ActivityScope;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityBundle\Provider\ExclusionProviderInterface;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;

/**
 * The implementation of ExclusionProviderInterface that can be used to ignore
 * relations which are an activity associations.
 */
class ActivityExclusionProvider implements ExclusionProviderInterface
{
    /** @var ConfigManager */
    protected $configManager;

    /**
     * @param ConfigManager $configManager
     */
    public function __construct(ConfigManager $configManager)
    {
        $this->configManager = $configManager;
    }

    /**
     * {@inheritdoc}
     */
    public function isIgnoredEntity($className)
    {
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function isIgnoredField(ClassMetadata $metadata, $fieldName)
    {
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function isIgnoredRelation(ClassMetadata $metadata, $associationName)
    {
        if (!$this->configManager->hasConfig($metadata->name, $associationName)) {
            return false;
        }

        $groups = $this->configManager->getEntityConfig('grouping', $metadata->name)->get('groups');
        if (empty($groups) || !in_array(ActivityScope::GROUP_ACTIVITY, $groups, true)) {
            return false;
        }

        $mapping = $metadata->getAssociationMapping($associationName);
        if (!$mapping['isOwningSide'] || !($mapping['type'] & ClassMetadata::MANY_TO_MANY)) {
            return false;
        }

        $activityAssociationName = ExtendHelper::buildAssociationName(
            $mapping['targetEntity'],
            ActivityScope::ASSOCIATION_KIND
        );

        return $associationName === $activityAssociationName;
    }
}
