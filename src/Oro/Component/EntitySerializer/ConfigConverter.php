<?php

namespace Oro\Component\EntitySerializer;

/**
 * Provides a method to convert normalized configuration of the EntityConfig object.
 *
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
class ConfigConverter
{
    /**
     * Converts normalized configuration of the EntityConfig object.
     *
     * @param array $config
     *
     * @return EntityConfig
     */
    public function convertConfig(array $config)
    {
        $result = new InternalEntityConfig();
        $this->buildEntityConfig($result, $config);

        return $result;
    }

    /**
     * @param EntityConfig $result
     * @param array        $config
     */
    protected function buildEntityConfig(EntityConfig $result, array $config)
    {
        $this->setExclusionPolicy($result, $config);
        $this->setPartialLoad($result, $config);
        $this->setHints($result, $config);
        $this->setOrderBy($result, $config);
        $this->setMaxResults($result, $config);
        $this->setHasMore($result, $config);
        $this->setPostSerializeHandler($result, $config);
        $this->setPostSerializeCollectionHandler($result, $config);
        $this->setExcludedFields($result, $config);
        $this->setRenamedFields($result, $config);

        if (!empty($config[ConfigUtil::FIELDS])) {
            foreach ($config[ConfigUtil::FIELDS] as $fieldName => $fieldConfig) {
                $field = $result->addField($fieldName);
                if (null !== $fieldConfig) {
                    $this->buildFieldConfig($field, $fieldConfig);
                }
            }
        }
    }

    /**
     * @param FieldConfig $result
     * @param array       $config
     */
    protected function buildFieldConfig(FieldConfig $result, array $config)
    {
        $this->setPropertyPath($result, $config);
        $this->setExcluded($result, $config);
        $this->setCollapsed($result, $config);
        $this->setDataTransformers($result, $config);

        $targetEntity = new InternalEntityConfig();
        $this->buildEntityConfig($targetEntity, $config);
        if (!$targetEntity->isEmpty()) {
            $result->setTargetEntity($targetEntity);
        }

        $this->setCollapseField($result, $config);
    }

    /**
     * @param EntityConfig $result
     * @param array        $config
     */
    protected function setExclusionPolicy(EntityConfig $result, array $config)
    {
        if (!empty($config[ConfigUtil::EXCLUSION_POLICY])
            && ConfigUtil::EXCLUSION_POLICY_NONE !== $config[ConfigUtil::EXCLUSION_POLICY]
        ) {
            $result->setExclusionPolicy($config[ConfigUtil::EXCLUSION_POLICY]);
        }
    }

    /**
     * @param EntityConfig $result
     * @param array        $config
     */
    protected function setPartialLoad(EntityConfig $result, array $config)
    {
        if (\array_key_exists(ConfigUtil::DISABLE_PARTIAL_LOAD, $config)
            && $config[ConfigUtil::DISABLE_PARTIAL_LOAD]
        ) {
            $result->disablePartialLoad();
        }
    }

    /**
     * @param EntityConfig $result
     * @param array        $config
     */
    protected function setHints(EntityConfig $result, array $config)
    {
        if (!empty($config[ConfigUtil::HINTS])) {
            foreach ($config[ConfigUtil::HINTS] as $hint) {
                if (\is_array($hint)) {
                    $result->addHint($hint['name'], $hint['value'] ?? null);
                } else {
                    $result->addHint($hint);
                }
            }
        }
    }

    /**
     * @param EntityConfig $result
     * @param array        $config
     */
    protected function setOrderBy(EntityConfig $result, array $config)
    {
        if (!empty($config[ConfigUtil::ORDER_BY])) {
            $result->setOrderBy($config[ConfigUtil::ORDER_BY]);
        }
    }

    /**
     * @param EntityConfig $result
     * @param array        $config
     */
    protected function setMaxResults(EntityConfig $result, array $config)
    {
        if (\array_key_exists(ConfigUtil::MAX_RESULTS, $config)
            && null !== $config[ConfigUtil::MAX_RESULTS]
        ) {
            $result->setMaxResults($config[ConfigUtil::MAX_RESULTS]);
        }
    }

    /**
     * @param EntityConfig $result
     * @param array        $config
     */
    protected function setHasMore(EntityConfig $result, array $config)
    {
        if (\array_key_exists(ConfigUtil::HAS_MORE, $config)
            && $config[ConfigUtil::HAS_MORE]
        ) {
            $result->setHasMore(true);
        }
    }

    /**
     * @param EntityConfig $result
     * @param array        $config
     */
    protected function setPostSerializeHandler(EntityConfig $result, array $config)
    {
        if (\array_key_exists(ConfigUtil::POST_SERIALIZE, $config)
            && null !== $config[ConfigUtil::POST_SERIALIZE]
        ) {
            $result->setPostSerializeHandler($config[ConfigUtil::POST_SERIALIZE]);
        }
    }

    /**
     * @param EntityConfig $result
     * @param array        $config
     */
    protected function setPostSerializeCollectionHandler(EntityConfig $result, array $config)
    {
        if (\array_key_exists(ConfigUtil::POST_SERIALIZE_COLLECTION, $config)
            && null !== $config[ConfigUtil::POST_SERIALIZE_COLLECTION]
        ) {
            $result->setPostSerializeCollectionHandler($config[ConfigUtil::POST_SERIALIZE_COLLECTION]);
        }
    }

    /**
     * @param EntityConfig $result
     * @param array        $config
     */
    protected function setExcludedFields(EntityConfig $result, array $config)
    {
        if (\array_key_exists(ConfigUtil::EXCLUDED_FIELDS, $config)
            && !empty($config[ConfigUtil::EXCLUDED_FIELDS])
        ) {
            $result->set(ConfigUtil::EXCLUDED_FIELDS, $config[ConfigUtil::EXCLUDED_FIELDS]);
        }
    }

    /**
     * @param EntityConfig $result
     * @param array        $config
     */
    protected function setRenamedFields(EntityConfig $result, array $config)
    {
        if (\array_key_exists(ConfigUtil::RENAMED_FIELDS, $config)
            && !empty($config[ConfigUtil::RENAMED_FIELDS])
        ) {
            $result->set(ConfigUtil::RENAMED_FIELDS, $config[ConfigUtil::RENAMED_FIELDS]);
        }
    }

    /**
     * @param FieldConfig $result
     * @param array       $config
     */
    protected function setPropertyPath(FieldConfig $result, array $config)
    {
        if (!empty($config[ConfigUtil::PROPERTY_PATH])) {
            $result->setPropertyPath($config[ConfigUtil::PROPERTY_PATH]);
        }
    }

    /**
     * @param FieldConfig $result
     * @param array       $config
     */
    protected function setExcluded(FieldConfig $result, array $config)
    {
        if (\array_key_exists(ConfigUtil::EXCLUDE, $config)
            && $config[ConfigUtil::EXCLUDE]
        ) {
            $result->setExcluded();
        }
    }

    /**
     * @param FieldConfig $result
     * @param array       $config
     */
    protected function setCollapsed(FieldConfig $result, array $config)
    {
        if (\array_key_exists(ConfigUtil::COLLAPSE, $config)
            && $config[ConfigUtil::COLLAPSE]
        ) {
            $result->setCollapsed();
        }
    }

    /**
     * @param FieldConfig $result
     * @param array        $config
     */
    protected function setCollapseField(FieldConfig $result, array $config)
    {
        if (\array_key_exists(ConfigUtil::COLLAPSE_FIELD, $config)) {
            $field = $config[ConfigUtil::COLLAPSE_FIELD];
            if ($field) {
                $result->getTargetEntity()->set(ConfigUtil::COLLAPSE_FIELD, $field);
            }
        }
    }

    /**
     * @param FieldConfig $result
     * @param array       $config
     */
    protected function setDataTransformers(FieldConfig $result, array $config)
    {
        if (!empty($config[ConfigUtil::DATA_TRANSFORMER])) {
            if (\is_string($config[ConfigUtil::DATA_TRANSFORMER])
                || \is_callable($config[ConfigUtil::DATA_TRANSFORMER])
            ) {
                $result->addDataTransformer($config[ConfigUtil::DATA_TRANSFORMER]);
            } else {
                foreach ($config[ConfigUtil::DATA_TRANSFORMER] as $dataTransformer) {
                    $result->addDataTransformer($dataTransformer);
                }
            }
        }
    }
}
