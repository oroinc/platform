<?php

namespace Oro\Bundle\EntityBundle\Provider;

use Oro\Bundle\EntityBundle\Model\EntityAlias;
use Oro\Bundle\EntityConfigBundle\Config\ConfigInterface;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;

/**
 * The utility class to help resolving duplicated entity aliases for configurable entities.
 */
class DuplicateEntityAliasResolver
{
    private ConfigManager $configManager;
    /** @var array|null [class name => EntityAlias|null, ...] */
    private ?array $classes = null;
    /** @var array|null [enum option class name => [[entity class, field name], ...], ...] */
    private ?array $enumFields = null;
    /** @var array|null [alias => TRUE, ...] */
    private ?array $aliases = null;

    public function __construct(ConfigManager $configManager)
    {
        $this->configManager = $configManager;
    }

    public function getAlias(string $entityClass): ?EntityAlias
    {
        $this->ensureAliasesInitialized();

        return $this->classes[$entityClass] ?? null;
    }

    public function hasAlias(string $alias, string $pluralAlias): bool
    {
        $this->ensureAliasesInitialized();

        return isset($this->aliases[$alias]) || isset($this->aliases[$pluralAlias]);
    }

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

    public function saveAlias(string $entityClass, EntityAlias $entityAlias): void
    {
        $this->ensureAliasesInitialized();

        if (!\array_key_exists($entityClass, $this->classes)) {
            throw new \InvalidArgumentException(sprintf(
                'The entity "%s" must be configurable or must represent an enum option.',
                $entityClass
            ));
        }

        $alias = $entityAlias->getAlias();
        $pluralAlias = $entityAlias->getPluralAlias();
        $this->saveAliasInEntityConfig($entityClass, $alias, $pluralAlias);

        $this->aliases[$alias] = true;
        if ($pluralAlias !== $alias) {
            $this->aliases[$pluralAlias] = true;
        }
        $this->classes[$entityClass] = $entityAlias;
    }

    private function saveAliasInEntityConfig(string $entityClass, string $alias, string $pluralAlias): void
    {
        $configs = [];
        if (ExtendHelper::isOutdatedEnumOptionEntity($entityClass)) {
            foreach ($this->enumFields[$entityClass] as [$enumFieldClass, $enumFieldName]) {
                $configs[] = $this->configManager->getFieldConfig('enum', $enumFieldClass, $enumFieldName);
            }
        } else {
            $configs[] = $this->configManager->getEntityConfig('entity', $entityClass);
        }

        $hasChanges = false;
        foreach ($configs as $config) {
            $hasChangesInConfig = false;
            if ($config->get('entity_alias') !== $alias) {
                $config->set('entity_alias', $alias);
                $hasChangesInConfig = true;
            }
            if ($config->get('entity_plural_alias') !== $pluralAlias) {
                $config->set('entity_plural_alias', $pluralAlias);
                $hasChangesInConfig = true;
            }
            if ($hasChangesInConfig) {
                $this->configManager->persist($config);
                $hasChanges = true;
            }
        }
        if ($hasChanges) {
            $this->configManager->flush();
        }
    }

    private function ensureAliasesInitialized(): void
    {
        if (null !== $this->classes) {
            return;
        }

        $this->classes = [];
        $this->enumFields = [];
        $this->aliases = [];
        $configs = $this->configManager->getConfigs('entity', null, true);
        foreach ($configs as $config) {
            $entityClass = $config->getId()->getClassName();
            // skip outdated enum option entity aliases
            if (ExtendHelper::isOutdatedEnumOptionEntity($entityClass)) {
                continue;
            }
            $entityAlias = null;
            $alias = $config->get('entity_alias');
            if ($alias) {
                $pluralAlias = $config->get('entity_plural_alias');
                $this->initializeAlias($alias, $pluralAlias);
                $entityAlias = new EntityAlias($alias, $pluralAlias);
            }
            $this->classes[$entityClass] = $entityAlias;
            $fieldConfigs = $this->getEnumFields($entityClass);
            foreach ($fieldConfigs as $fieldConfig) {
                $enumOptionClass = ExtendHelper::getOutdatedEnumOptionClassName($fieldConfig->get('enum_code'));
                $this->enumFields[$enumOptionClass][] = [$entityClass, $fieldConfig->getId()->getFieldName()];
                $entityAlias = null;
                $alias = $fieldConfig->get('entity_alias');
                if ($alias) {
                    $pluralAlias = $fieldConfig->get('entity_plural_alias');
                    $this->initializeAlias($alias, $pluralAlias);
                    $entityAlias = new EntityAlias($alias, $pluralAlias);
                }
                $this->classes[$enumOptionClass] = $entityAlias;
            }
        }
    }

    private function initializeAlias(string $alias, string $pluralAlias): void
    {
        $this->aliases[$alias] = true;
        if ($pluralAlias !== $alias) {
            $this->aliases[$pluralAlias] = true;
        }
    }

    /**
     * @param string $entityClass
     *
     * @return ConfigInterface[]
     */
    private function getEnumFields(string $entityClass): array
    {
        $fieldConfigs = [];
        /** @var FieldConfigId[] $fieldConfigIds */
        $fieldConfigIds = $this->configManager->getIds('enum', $entityClass, true);
        foreach ($fieldConfigIds as $fieldConfigId) {
            if (!ExtendHelper::isEnumerableType($fieldConfigId->getFieldType())) {
                continue;
            }
            $fieldName = $fieldConfigId->getFieldName();
            $fieldConfig = $this->configManager->getFieldConfig('enum', $entityClass, $fieldName);
            $enumCode = $fieldConfig->get('enum_code');
            if (!$enumCode) {
                continue;
            }
            $fieldConfigs[] = $fieldConfig;
        }

        return $fieldConfigs;
    }
}
