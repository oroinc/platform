<?php

namespace Oro\Bundle\EntityBundle\Provider;

use Oro\Bundle\EntityBundle\Model\EntityAlias;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;

/**
 * The utility class to help resolving duplicated entity aliases for configurable entities.
 */
class DuplicateEntityAliasResolver
{
    /** @var ConfigManager */
    private $configManager;

    /** @var array [class name => EntityAlias|null, ...] */
    private $classes;

    /** @var array [alias => TRUE, ...] */
    private $aliases;

    /**
     * @param ConfigManager $configManager
     */
    public function __construct(ConfigManager $configManager)
    {
        $this->configManager = $configManager;
    }

    /**
     * @param string $entityClass
     *
     * @return EntityAlias|null
     */
    public function getAlias(string $entityClass): ?EntityAlias
    {
        $this->ensureAliasesInitialized();

        if (!array_key_exists($entityClass, $this->classes)) {
            return null;
        }

        return $this->classes[$entityClass];
    }

    /**
     * @param string $alias
     * @param string $pluralAlias
     *
     * @return bool
     */
    public function hasAlias(string $alias, string $pluralAlias): bool
    {
        $this->ensureAliasesInitialized();

        return isset($this->aliases[$alias]) || isset($this->aliases[$pluralAlias]);
    }

    /**
     * @param string $alias
     * @param string $pluralAlias
     *
     * @return string
     */
    public function getUniqueAlias(string $alias, string $pluralAlias): string
    {
        $this->ensureAliasesInitialized();

        if (!isset($this->aliases[$alias]) && !isset($this->aliases[$pluralAlias])) {
            return $alias;
        }

        $i = 1;
        $generatedAlias = $alias . $i;
        while (isset($this->aliases[$generatedAlias])) {
            $i++;
            $generatedAlias = $alias . $i;
        }

        return $generatedAlias;
    }

    /**
     * @param string      $entityClass
     * @param EntityAlias $entityAlias
     */
    public function saveAlias(string $entityClass, EntityAlias $entityAlias): void
    {
        $this->ensureAliasesInitialized();

        if (!array_key_exists($entityClass, $this->classes)) {
            throw new \InvalidArgumentException(sprintf('The entity "%s" must be configurable.', $entityClass));
        }

        $alias = $entityAlias->getAlias();
        $pluralAlias = $entityAlias->getPluralAlias();
        $config = $this->configManager->getEntityConfig('entity', $entityClass);
        $config->set('entity_alias', $alias);
        $config->set('entity_plural_alias', $pluralAlias);
        $this->configManager->persist($config);
        $this->configManager->flush();

        $this->aliases[$alias] = true;
        if ($pluralAlias !== $alias) {
            $this->aliases[$pluralAlias] = true;
        }
        $this->classes[$entityClass] = $entityAlias;
    }

    private function ensureAliasesInitialized(): void
    {
        if (null !== $this->classes) {
            return;
        }

        $this->classes = [];
        $this->aliases = [];
        $configs = $this->configManager->getConfigs('entity', null, true);
        foreach ($configs as $config) {
            $entityClass = $config->getId()->getClassName();
            $entityAlias = null;
            $alias = $config->get('entity_alias');
            $pluralAlias = null;
            if ($alias) {
                $this->aliases[$alias] = true;
                $pluralAlias = $config->get('entity_plural_alias');
                if ($pluralAlias !== $alias) {
                    $this->aliases[$pluralAlias] = true;
                }
                $entityAlias = new EntityAlias($alias, $pluralAlias);
            }
            $this->classes[$entityClass] = $entityAlias;
        }
    }
}
