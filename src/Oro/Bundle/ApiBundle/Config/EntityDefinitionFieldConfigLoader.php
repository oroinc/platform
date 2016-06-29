<?php

namespace Oro\Bundle\ApiBundle\Config;

use Oro\Bundle\ApiBundle\Util\ConfigUtil;

class EntityDefinitionFieldConfigLoader extends AbstractConfigLoader implements ConfigLoaderFactoryAwareInterface
{
    /** @var array */
    protected $methodMap = [
        EntityDefinitionFieldConfig::EXCLUDE          => 'setExcluded',
        EntityDefinitionFieldConfig::COLLAPSE         => 'setCollapsed',
        EntityDefinitionFieldConfig::DATA_TRANSFORMER => 'setDataTransformers',
    ];

    /** @var array */
    protected $targetEntityMethodMap = [
        EntityDefinitionConfig::EXCLUSION_POLICY       => 'setExclusionPolicy',
        EntityDefinitionConfig::DISABLE_PARTIAL_LOAD   => ['disablePartialLoad', 'enablePartialLoad'],
        EntityDefinitionConfig::ORDER_BY               => 'setOrderBy',
        EntityDefinitionConfig::MAX_RESULTS            => 'setMaxResults',
        EntityDefinitionConfig::HINTS                  => 'setHints',
        EntityDefinitionConfig::POST_SERIALIZE         => 'setPostSerializeHandler',
        EntityDefinitionConfig::IDENTIFIER_FIELD_NAMES => 'setIdentifierFieldNames',
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
            } elseif (ConfigUtil::FIELDS === $key) {
                $this->loadTargetFields($field, $value);
            } elseif ($this->factory->hasLoader($key)) {
                $this->loadTargetSection($field, $this->factory->getLoader($key), $key, $value);
            } else {
                $this->loadConfigValue($field, $key, $value, $this->methodMap);
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
     * @param ConfigLoaderInterface       $loader
     * @param string                      $sectionName
     * @param array|null                  $config
     */
    protected function loadTargetSection(
        EntityDefinitionFieldConfig $field,
        ConfigLoaderInterface $loader,
        $sectionName,
        array $config = null
    ) {
        if (!empty($config)) {
            $section = $loader->load($config);
            $isEmpty = false;
            if (is_object($section)) {
                if (method_exists($section, 'isEmpty') && $section->isEmpty()) {
                    $isEmpty = true;
                }
            } elseif (empty($section)) {
                $isEmpty = true;
            }
            if (!$isEmpty) {
                $this->setValue($field->getOrCreateTargetEntity(), $sectionName, $section);
            }
        }
    }
}
