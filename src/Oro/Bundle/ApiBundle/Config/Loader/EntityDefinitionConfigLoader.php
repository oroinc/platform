<?php

namespace Oro\Bundle\ApiBundle\Config\Loader;

use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Util\ConfigUtil;

/**
 * The loader for "entities" configuration section.
 */
class EntityDefinitionConfigLoader extends AbstractConfigLoader implements ConfigLoaderFactoryAwareInterface
{
    private const METHOD_MAP = [
        ConfigUtil::EXCLUSION_POLICY        => 'setExclusionPolicy',
        ConfigUtil::IDENTIFIER_FIELD_NAMES  => 'setIdentifierFieldNames',
        ConfigUtil::IDENTIFIER_DESCRIPTION  => 'setIdentifierDescription',
        ConfigUtil::MAX_RESULTS             => 'setMaxResults',
        ConfigUtil::ORDER_BY                => 'setOrderBy',
        ConfigUtil::HINTS                   => 'setHints',
        ConfigUtil::INNER_JOIN_ASSOCIATIONS => 'setInnerJoinAssociations',
        ConfigUtil::FORM_TYPE               => 'setFormType',
        ConfigUtil::FORM_OPTIONS            => 'setFormOptions',
        ConfigUtil::DOCUMENTATION_RESOURCE  => 'setDocumentationResources',
        ConfigUtil::COLLAPSE                => 'setCollapsed',
        ConfigUtil::DISABLE_SORTING         => ['disableSorting', 'enableSorting'],
        ConfigUtil::DISABLE_INCLUSION       => ['disableInclusion', 'enableInclusion'],
        ConfigUtil::DISABLE_FIELDSET        => ['disableFieldset', 'enableFieldset'],
        ConfigUtil::DISABLE_PARTIAL_LOAD    => ['disablePartialLoad', 'enablePartialLoad'],
        ConfigUtil::FORM_EVENT_SUBSCRIBER   => 'setFormEventSubscribers',
        ConfigUtil::ENABLE_VALIDATION       => ['enableValidation', 'disableValidation'],
    ];

    private ConfigLoaderFactory $factory;

    #[\Override]
    public function setConfigLoaderFactory(ConfigLoaderFactory $factory): void
    {
        $this->factory = $factory;
    }

    #[\Override]
    public function load(array $config): mixed
    {
        $definition = new EntityDefinitionConfig();
        $this->loadDefinition($definition, $config);

        return $definition;
    }

    private function loadDefinition(EntityDefinitionConfig $definition, ?array $config): void
    {
        if (empty($config)) {
            return;
        }

        foreach ($config as $key => $value) {
            if (ConfigUtil::FIELDS === $key) {
                $this->loadFields($definition, $value);
            } elseif (ConfigUtil::DISABLE_META_PROPERTIES === $key) {
                $this->loadDisabledMetaProperties($definition, $value);
            } elseif (ConfigUtil::UPSERT === $key) {
                $this->loadUpsertConfig($definition, $value);
            } elseif ($this->factory->hasLoader($key)) {
                $this->loadSection($definition, $this->factory->getLoader($key), $key, $value);
            } else {
                $this->loadConfigValue($definition, $key, $value, self::METHOD_MAP);
            }
        }
    }

    private function loadFields(EntityDefinitionConfig $definition, ?array $fields): void
    {
        if (!empty($fields)) {
            foreach ($fields as $name => $config) {
                $definition->addField(
                    $name,
                    $this->factory->getLoader(ConfigUtil::FIELDS)->load($config ?? [])
                );
            }
        }
    }

    private function loadDisabledMetaProperties(EntityDefinitionConfig $definition, mixed $value): void
    {
        foreach ((array)$value as $val) {
            if (true === $val) {
                $definition->disableMetaProperties();
            } elseif (false === $val) {
                $definition->enableMetaProperties();
            } else {
                $definition->disableMetaProperty($val);
            }
        }
    }

    private function loadUpsertConfig(EntityDefinitionConfig $definition, array $value): void
    {
        if (isset($value[ConfigUtil::UPSERT_DISABLE])) {
            $definition->getUpsertConfig()->setEnabled(!$value[ConfigUtil::UPSERT_DISABLE]);
        }
        if (isset($value[ConfigUtil::UPSERT_REPLACE])) {
            $definition->getUpsertConfig()->replaceFields($value[ConfigUtil::UPSERT_REPLACE]);
        }
        if (isset($value[ConfigUtil::UPSERT_ADD])) {
            foreach ($value[ConfigUtil::UPSERT_ADD] as $fieldNames) {
                $definition->getUpsertConfig()->addFields($fieldNames);
            }
        }
        if (isset($value[ConfigUtil::UPSERT_REMOVE])) {
            foreach ($value[ConfigUtil::UPSERT_REMOVE] as $fieldNames) {
                $definition->getUpsertConfig()->removeFields($fieldNames);
            }
        }
    }

    private function loadSection(
        EntityDefinitionConfig $definition,
        ConfigLoaderInterface $loader,
        string $sectionName,
        ?array $config
    ): void {
        if (!empty($config)) {
            $section = $loader->load($config);
            $isEmpty = false;
            if (\is_object($section)) {
                if (method_exists($section, 'isEmpty') && $section->isEmpty()) {
                    $isEmpty = true;
                }
            } elseif (empty($section)) {
                $isEmpty = true;
            }
            if (!$isEmpty) {
                $this->setValue($definition, $sectionName, $section);
            }
        }
    }
}
