<?php

namespace Oro\Bundle\EntityExtendBundle\Form\Util;

use Oro\Bundle\EntityBundle\EntityConfig\GroupingScope;
use Oro\Bundle\EntityBundle\ORM\EntityClassResolver;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Form\Util\ConfigTypeHelper;

class AssociationTypeHelper extends ConfigTypeHelper
{
    /** @var EntityClassResolver */
    protected $entityClassResolver;

    /** @var array */
    protected $owningSideEntities;

    /**
     * @param ConfigManager       $configManager
     * @param EntityClassResolver $entityClassResolver
     */
    public function __construct(ConfigManager $configManager, EntityClassResolver $entityClassResolver)
    {
        parent::__construct($configManager);

        $this->entityClassResolver = $entityClassResolver;
    }
    /**
     * Checks if the given entity is included in 'dictionary' group
     *
     * @param string $className
     *
     * @return bool
     */
    public function isDictionary($className)
    {
        $groupingConfigProvider = $this->configManager->getProvider('grouping');
        if ($groupingConfigProvider->hasConfig($className)) {
            $groups = $groupingConfigProvider->getConfig($className)->get('groups');
            if (!empty($groups) && in_array(GroupingScope::GROUP_DICTIONARY, $groups)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Checks if the given entity is an owning side of association
     *
     * @param string $className
     * @param string $associationClass Represents the owning side entity, can be:
     *                                 - full class name or entity name for single association
     *                                 - a group name for multiple association
     *                                 it is supposed that the group name should not contain \ and : characters
     *
     * @return bool
     */
    public function isAssociationOwningSideEntity($className, $associationClass)
    {
        if (strpos($associationClass, ':') !== false || strpos($associationClass, '\\') !== false) {
            // the association class is full class name or entity name
            if ($className === $this->entityClassResolver->getEntityClass($associationClass)) {
                return true;
            }
        } else {
            // the association class is a group name
            if (!empty($className) && in_array($className, $this->getOwningSideEntities($associationClass))) {
                return true;
            };
        }

        return false;
    }

    /**
     * @param string $groupName
     *
     * @return string[]
     */
    public function getOwningSideEntities($groupName)
    {
        if (null === $this->owningSideEntities) {
            $this->owningSideEntities = $this->loadOwningSideEntities($groupName);
        }

        return $this->owningSideEntities;
    }

    /**
     * Makes sure that the owning side entities are loaded
     *
     * @param string $groupName
     */
    protected function ensureOwningSideEntitiesLoaded($groupName)
    {
        if (null === $this->owningSideEntities) {
            $this->owningSideEntities = $this->loadOwningSideEntities($groupName);
        }
    }

    /**
     * Loads the list of owning side entities
     *
     * @param $groupName
     * @return string[]
     */
    protected function loadOwningSideEntities($groupName)
    {
        $result  = [];
        $configs = $this->configManager->getProvider('grouping')->getConfigs();
        foreach ($configs as $config) {
            $groups = $config->get('groups');
            if (!empty($groups) && in_array($groupName, $groups)) {
                $result[] = $config->getId()->getClassName();
            }
        }

        return $result;
    }
}
