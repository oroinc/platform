<?php

namespace Oro\Bundle\ApiBundle\Config;

use Oro\Bundle\ApiBundle\Util\ConfigUtil;

class EntityDefinitionConfigLoader extends AbstractConfigLoader implements
    ConfigLoaderInterface,
    ConfigLoaderFactoryAwareInterface
{
    /** @var array */
    protected $methodMap = [
        ConfigUtil::EXCLUSION_POLICY     => 'setExclusionPolicy',
        ConfigUtil::DISABLE_PARTIAL_LOAD => ['disablePartialLoad', 'enablePartialLoad'],
        ConfigUtil::ORDER_BY             => 'setOrderBy',
        ConfigUtil::MAX_RESULTS          => 'setMaxResults',
        ConfigUtil::HINTS                => 'setHints',
        ConfigUtil::POST_SERIALIZE       => 'setPostSerializeHandler',
        ConfigUtil::LABEL                => 'setLabel',
        ConfigUtil::PLURAL_LABEL         => 'setPluralLabel',
        ConfigUtil::DESCRIPTION          => 'setDescription',
    ];

    /** @var ConfigLoaderFactory */
    protected $factory;

    /**
     * {@inheritdoc}
     */
    public function setConfigLoaderFactory(ConfigLoaderFactory $factory)
    {
        $this->factory = $factory;
    }

    /**
     * {@inheritdoc}
     */
    public function load(array $config)
    {
        $definition = new EntityDefinitionConfig();
        $this->loadDefinition($definition, $config);

        return $definition;
    }

    /**
     * @param EntityDefinitionConfig $definition
     * @param array|null             $config
     */
    protected function loadDefinition(EntityDefinitionConfig $definition, $config)
    {
        if (empty($config)) {
            return;
        }

        foreach ($config as $key => $value) {
            if (isset($this->methodMap[$key])) {
                $this->callSetter($definition, $this->methodMap[$key], $value);
            } elseif (ConfigUtil::FIELDS === $key) {
                $this->loadFields($definition, $value);
            } elseif (ConfigUtil::DEFINITION === $key) {
                $this->loadDefinition($definition, $value);
            } elseif (ConfigUtil::FILTERS === $key) {
                $this->loadFilters($definition, $value);
            } elseif (ConfigUtil::SORTERS === $key) {
                $this->loadSorters($definition, $value);
            } else {
                $this->setValue($definition, $key, $value);
            }
        }
    }

    /**
     * @param EntityDefinitionConfig $definition
     * @param array|null             $fields
     */
    protected function loadFields(EntityDefinitionConfig $definition, $fields)
    {
        if (!empty($fields)) {
            foreach ($fields as $name => $config) {
                $definition->addField(
                    $name,
                    $this->factory->getLoader(ConfigUtil::FIELDS)->load(null !== $config ? $config : [])
                );
            }
        }
    }

    /**
     * @param EntityDefinitionConfig $definition
     * @param array|null             $config
     */
    protected function loadFilters(EntityDefinitionConfig $definition, $config)
    {
        if (!empty($config)) {
            /** @var FiltersConfig $filters */
            $filters = $this->factory->getLoader(ConfigUtil::FILTERS)->load($config);
            if (!$filters->isEmpty()) {
                $this->setValue($definition, ConfigUtil::FILTERS, $filters);
            }
        }
    }

    /**
     * @param EntityDefinitionConfig $definition
     * @param array|null             $config
     */
    protected function loadSorters(EntityDefinitionConfig $definition, $config)
    {
        if (!empty($config)) {
            /** @var SortersConfig $sorters */
            $sorters = $this->factory->getLoader(ConfigUtil::SORTERS)->load($config);
            if (!$sorters->isEmpty()) {
                $this->setValue($definition, ConfigUtil::SORTERS, $sorters);
            }
        }
    }
}
