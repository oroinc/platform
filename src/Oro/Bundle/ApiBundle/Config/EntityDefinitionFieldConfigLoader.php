<?php

namespace Oro\Bundle\ApiBundle\Config;

use Oro\Bundle\ApiBundle\Util\ConfigUtil;

class EntityDefinitionFieldConfigLoader extends AbstractConfigLoader implements
    ConfigLoaderInterface,
    ConfigLoaderFactoryAwareInterface
{
    /** @var array */
    protected $methodMap = [
        EntityDefinitionFieldConfig::EXCLUDE          => 'setExcluded',
        EntityDefinitionFieldConfig::COLLAPSE         => 'setCollapsed',
        EntityDefinitionFieldConfig::PROPERTY_PATH    => 'setPropertyPath',
        EntityDefinitionFieldConfig::DATA_TRANSFORMER => 'setDataTransformers',
        EntityDefinitionFieldConfig::LABEL            => 'setLabel',
        EntityDefinitionFieldConfig::DESCRIPTION      => 'setDescription',
    ];

    /** @var array */
    protected $targetEntityMethodMap = [
        EntityDefinitionConfig::EXCLUSION_POLICY     => 'setExclusionPolicy',
        EntityDefinitionConfig::DISABLE_PARTIAL_LOAD => ['disablePartialLoad', 'enablePartialLoad'],
        EntityDefinitionConfig::ORDER_BY             => 'setOrderBy',
        EntityDefinitionConfig::MAX_RESULTS          => 'setMaxResults',
        EntityDefinitionConfig::HINTS                => 'setHints',
        EntityDefinitionConfig::POST_SERIALIZE       => 'setPostSerializeHandler',
        EntityDefinitionConfig::LABEL                => 'setLabel',
        EntityDefinitionConfig::PLURAL_LABEL         => 'setPluralLabel',
        EntityDefinitionConfig::DESCRIPTION          => 'setDescription',
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
        $field = new EntityDefinitionFieldConfig();
        $this->loadField($field, $config);

        return $field;
    }

    /**
     * @param EntityDefinitionFieldConfig $field
     * @param array|null                  $config
     */
    protected function loadField(EntityDefinitionFieldConfig $field, array $config = null)
    {
        if (empty($config)) {
            return;
        }

        foreach ($config as $key => $value) {
            if (isset($this->targetEntityMethodMap[$key])) {
                $this->callSetter($field->getOrCreateTargetEntity(), $this->targetEntityMethodMap[$key], $value);
            } elseif (isset($this->methodMap[$key])) {
                $this->callSetter($field, $this->methodMap[$key], $value);
            } elseif (ConfigUtil::FIELDS === $key) {
                $this->loadTargetFields($field, $value);
            } elseif (ConfigUtil::FILTERS === $key) {
                $this->loadTargetFilters($field, $value);
            } elseif (ConfigUtil::SORTERS === $key) {
                $this->loadTargetSorters($field, $value);
            } else {
                $this->setValue($field, $key, $value);
            }
        }
    }

    /**
     * @param EntityDefinitionFieldConfig $field
     * @param array|string|null           $fields
     */
    protected function loadTargetFields(EntityDefinitionFieldConfig $field, $fields)
    {
        if (!empty($fields)) {
            $targetEntity = $field->getOrCreateTargetEntity();
            if (is_string($fields)) {
                $field->setCollapsed();
                $targetEntity->addField($fields);
            } else {
                foreach ($fields as $name => $config) {
                    $targetEntity->addField(
                        $name,
                        $this->factory->getLoader(ConfigUtil::FIELDS)->load(null !== $config ? $config : [])
                    );
                }
            }
        }
    }

    /**
     * @param EntityDefinitionFieldConfig $field
     * @param array|null                  $config
     */
    protected function loadTargetFilters(EntityDefinitionFieldConfig $field, array $config = null)
    {
        if (!empty($config)) {
            /** @var FiltersConfig $filters */
            $filters = $this->factory->getLoader(ConfigUtil::FILTERS)->load($config);
            if (!$filters->isEmpty()) {
                $this->setValue($field->getOrCreateTargetEntity(), ConfigUtil::FILTERS, $filters);
            }
        }
    }

    /**
     * @param EntityDefinitionFieldConfig $field
     * @param array|null                  $config
     */
    protected function loadTargetSorters(EntityDefinitionFieldConfig $field, array $config = null)
    {
        if (!empty($config)) {
            /** @var SortersConfig $sorters */
            $sorters = $this->factory->getLoader(ConfigUtil::SORTERS)->load($config);
            if (!$sorters->isEmpty()) {
                $this->setValue($field->getOrCreateTargetEntity(), ConfigUtil::SORTERS, $sorters);
            }
        }
    }
}
