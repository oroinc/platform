<?php

namespace Oro\Bundle\EntityExtendBundle\Provider;

use Doctrine\Inflector\Inflector;
use Oro\Bundle\EntityBundle\Model\EntityAlias;
use Oro\Bundle\EntityBundle\Provider\DuplicateEntityAliasResolver;
use Oro\Bundle\EntityBundle\Provider\EntityAliasConfigBag;
use Oro\Bundle\EntityBundle\Provider\EntityAliasProviderInterface;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;

/**
 * Provides aliases for custom entities and target classes for enum and multi-enum entities.
 */
class ExtendEntityAliasProvider implements EntityAliasProviderInterface
{
    private EntityAliasConfigBag $config;
    private ConfigManager $configManager;
    private DuplicateEntityAliasResolver $duplicateResolver;
    private Inflector $inflector;

    public function __construct(
        EntityAliasConfigBag $config,
        ConfigManager $configManager,
        DuplicateEntityAliasResolver $duplicateResolver,
        Inflector $inflector
    ) {
        $this->config = $config;
        $this->configManager = $configManager;
        $this->duplicateResolver = $duplicateResolver;
        $this->inflector = $inflector;
    }

    /**
     * {@inheritDoc}
     */
    public function getEntityAlias($entityClass)
    {
        // check for enums
        if (ExtendHelper::isOutdatedEnumOptionEntity($entityClass)) {
            return $this->getEntityAliasForEnum($entityClass, ExtendHelper::getEnumCode($entityClass));
        }

        if (!$this->configManager->hasConfig($entityClass)) {
            return null;
        }

        // check for dictionaries
        $groups = $this->configManager->getEntityConfig('grouping', $entityClass)->get('groups');
        if (!empty($groups) && \in_array('dictionary', $groups, true)) {
            // delegate aliases generation to default provider
            return null;
        }

        // check for custom entities
        if (ExtendHelper::isCustomEntity($entityClass)) {
            return $this->getEntityAliasForCustomEntity($entityClass);
        }

        return null;
    }

    /**
     * @param string $entityClass
     * @param string $enumCode
     *
     * @return EntityAlias|bool|null
     */
    private function getEntityAliasForEnum($entityClass, $enumCode)
    {
        $entityAlias = $this->getEntityAliasFromConfig($entityClass);
        if (null === $entityAlias) {
            $entityAlias = $this->duplicateResolver->getAlias($entityClass);
            if (null === $entityAlias) {
                $entityAlias = $this->createEntityAlias(str_replace('_', '', $enumCode));
                $this->duplicateResolver->saveAlias($entityClass, $entityAlias);
            }
        }

        return $entityAlias;
    }

    /**
     * @param string $entityClass
     *
     * @return EntityAlias|bool|null
     */
    private function getEntityAliasForCustomEntity($entityClass)
    {
        $entityAlias = $this->getEntityAliasFromConfig($entityClass);
        if (null === $entityAlias) {
            $entityAlias = $this->duplicateResolver->getAlias($entityClass);
            if (null === $entityAlias) {
                $entityAlias = $this->createEntityAlias('Extend' . ExtendHelper::getShortClassName($entityClass));
                $this->duplicateResolver->saveAlias($entityClass, $entityAlias);
            }
        }

        return $entityAlias;
    }

    /**
     * @param string $entityClass
     *
     * @return EntityAlias|bool|null
     */
    private function getEntityAliasFromConfig($entityClass)
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
    private function createEntityAlias($name)
    {
        $alias = strtolower($name);
        $pluralAlias = strtolower($this->inflector->pluralize($name));
        if ($this->duplicateResolver->hasAlias($alias, $pluralAlias)) {
            $alias = $this->duplicateResolver->getUniqueAlias($alias, $pluralAlias);
            $pluralAlias = $alias;
        }

        return new EntityAlias($alias, $pluralAlias);
    }
}
