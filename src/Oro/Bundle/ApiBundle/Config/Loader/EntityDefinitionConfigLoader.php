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
        ConfigUtil::DISABLE_META_PROPERTIES => ['disableMetaProperties', 'enableMetaProperties'],
        ConfigUtil::DISABLE_PARTIAL_LOAD    => ['disablePartialLoad', 'enablePartialLoad'],
        ConfigUtil::FORM_EVENT_SUBSCRIBER   => 'setFormEventSubscribers'
    ];

    private ConfigLoaderFactory $factory;

    /**
     * {@inheritdoc}
     */
    public function setConfigLoaderFactory(ConfigLoaderFactory $factory): void
    {
        $this->factory = $factory;
    }

    /**
     * {@inheritdoc}
     */
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
