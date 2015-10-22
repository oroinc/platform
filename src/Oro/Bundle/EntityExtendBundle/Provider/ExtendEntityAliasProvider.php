<?php

namespace Oro\Bundle\EntityExtendBundle\Provider;

use Doctrine\Common\Inflector\Inflector;

use Oro\Bundle\EntityBundle\EntityConfig\GroupingScope;
use Oro\Bundle\EntityBundle\Model\EntityAlias;
use Oro\Bundle\EntityBundle\Provider\EntityAliasConfigBag;
use Oro\Bundle\EntityBundle\Provider\EntityAliasProviderInterface;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;

class ExtendEntityAliasProvider implements EntityAliasProviderInterface
{
    /** @var EntityAliasConfigBag */
    protected $config;

    /** @var ConfigManager */
    protected $configManager;

    /**
     * @param EntityAliasConfigBag $config
     * @param ConfigManager        $configManager
     */
    public function __construct(EntityAliasConfigBag $config, ConfigManager $configManager)
    {
        $this->config        = $config;
        $this->configManager = $configManager;
    }

    /**
     * {@inheritdoc}
     */
    public function getEntityAlias($entityClass)
    {
        if ($this->configManager->hasConfig($entityClass)) {
            // check for enums
            $enumCode = $this->configManager->getProvider('enum')->getConfig($entityClass)->get('code');
            if ($enumCode) {
                $entityAlias = $this->getEntityAliasFromConfig($entityClass);
                if (null !== $entityAlias) {
                    return $entityAlias;
                }

                return $this->createEntityAlias(str_replace('_', '', $enumCode));
            }

            // check for dictionaries
            $groups = $this->configManager->getProvider('grouping')->getConfig($entityClass)->get('groups');
            if (!empty($groups) && in_array(GroupingScope::GROUP_DICTIONARY, $groups, true)) {
                // delegate aliases generation to default provider
                return null;
            }

            // exclude hidden entities
            if ($this->configManager->isHiddenModel($entityClass)) {
                return false;
            }

            // check for custom entities
            if (ExtendHelper::isCustomEntity($entityClass)) {
                $entityAlias = $this->getEntityAliasFromConfig($entityClass);
                if (null !== $entityAlias) {
                    return $entityAlias;
                }

                return $this->createEntityAlias('Extend' . ExtendHelper::getShortClassName($entityClass));
            }
        }

        return null;
    }

    /**
     * @param string $entityClass
     *
     * @return EntityAlias|bool|null
     */
    protected function getEntityAliasFromConfig($entityClass)
    {
        // check for the exclusion list
        if ($this->config->isEntityAliasExclusionExist($entityClass)) {
            return false;
        }

        // check for explicitly configured aliases
        if ($this->config->hasEntityAlias($entityClass)) {
            return $this->config->getEntityAlias($entityClass);
        }

        return null;
    }

    /**
     * @param string $name
     *
     * @return EntityAlias
     */
    protected function createEntityAlias($name)
    {
        return new EntityAlias(
            strtolower($name),
            strtolower(Inflector::pluralize($name))
        );
    }
}
