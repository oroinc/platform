<?php

namespace Oro\Bundle\ApiBundle\Config\Loader;

use Oro\Bundle\ApiBundle\Config\EntityDefinitionFieldConfig;
use Oro\Bundle\ApiBundle\Util\ConfigUtil;

/**
 * The loader for fields of "entities" configuration section.
 */
class EntityDefinitionFieldConfigLoader extends AbstractConfigLoader implements ConfigLoaderFactoryAwareInterface
{
    private const METHOD_MAP = [
        ConfigUtil::DATA_TYPE              => 'setDataType',
        ConfigUtil::PROPERTY_PATH          => 'setPropertyPath',
        ConfigUtil::FORM_TYPE              => 'setFormType',
        ConfigUtil::FORM_OPTIONS           => 'setFormOptions',
        ConfigUtil::TARGET_CLASS           => 'setTargetClass',
        ConfigUtil::TARGET_TYPE            => 'setTargetType',
        ConfigUtil::DEPENDS_ON             => 'setDependsOn',
        ConfigUtil::DESCRIPTION            => 'setDescription',
        ConfigUtil::EXCLUDE                => 'setExcluded',
        ConfigUtil::COLLAPSE               => 'setCollapsed',
        ConfigUtil::DATA_TRANSFORMER       => 'setDataTransformers',
        ConfigUtil::POST_PROCESSOR         => 'setPostProcessor',
        ConfigUtil::POST_PROCESSOR_OPTIONS => 'setPostProcessorOptions'
    ];

    private const TARGET_ENTITY_METHOD_MAP = [
        ConfigUtil::EXCLUSION_POLICY       => 'setExclusionPolicy',
        ConfigUtil::IDENTIFIER_FIELD_NAMES => 'setIdentifierFieldNames',
        ConfigUtil::ORDER_BY               => 'setOrderBy',
        ConfigUtil::MAX_RESULTS            => 'setMaxResults',
        ConfigUtil::HINTS                  => 'setHints',
        ConfigUtil::FORM_EVENT_SUBSCRIBER  => 'setFormEventSubscribers'
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

    protected function loadField(EntityDefinitionFieldConfig $field, array $config = null)
    {
        if (empty($config)) {
            return;
        }

        foreach ($config as $key => $value) {
            if (isset(self::TARGET_ENTITY_METHOD_MAP[$key])) {
                $this->callSetter($field->getOrCreateTargetEntity(), self::TARGET_ENTITY_METHOD_MAP[$key], $value);
            } elseif (ConfigUtil::FIELDS === $key) {
                $this->loadTargetFields($field, $value);
            } elseif ($this->factory->hasLoader($key)) {
                $this->loadTargetSection($field, $this->factory->getLoader($key), $key, $value);
            } else {
                $this->loadConfigValue($field, $key, $value, self::METHOD_MAP);
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
            if (\is_string($fields)) {
                $field->setCollapsed();
                $targetEntity->addField($fields);
            } else {
                foreach ($fields as $name => $config) {
                    $targetEntity->addField(
                        $name,
                        $this->factory->getLoader(ConfigUtil::FIELDS)->load($config ?? [])
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
            if (\is_object($section)) {
                if (\method_exists($section, 'isEmpty') && $section->isEmpty()) {
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
