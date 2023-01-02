<?php

namespace Oro\Component\EntitySerializer;

/**
 * Provides a method to convert normalized configuration of the EntityConfig object.
 *
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
class ConfigConverter
{
    public function convertConfig(array $config): EntityConfig
    {
        $result = new InternalEntityConfig();
        $this->buildEntityConfig($result, $config);

        return $result;
    }

    protected function buildEntityConfig(EntityConfig $result, array $config): void
    {
        $this->setExclusionPolicy($result, $config);
        $this->setPartialLoad($result, $config);
        $this->setHints($result, $config);
        $this->setInnerJoinAssociations($result, $config);
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

    protected function buildFieldConfig(FieldConfig $result, array $config): void
    {
        $this->setPropertyPath($result, $config);
        $this->setExcluded($result, $config);
        $this->setCollapsed($result, $config);
        $this->setDataTransformers($result, $config);
        $this->setAssociationQuery($result, $config);

        $targetEntity = new InternalEntityConfig();
        $this->buildEntityConfig($targetEntity, $config);
        if (!$targetEntity->isEmpty()) {
            $result->setTargetEntity($targetEntity);
        }

        $this->setCollapseField($result, $config);
    }

    protected function setExclusionPolicy(EntityConfig $result, array $config): void
    {
        if (!empty($config[ConfigUtil::EXCLUSION_POLICY])
            && ConfigUtil::EXCLUSION_POLICY_NONE !== $config[ConfigUtil::EXCLUSION_POLICY]
        ) {
            $result->setExclusionPolicy($config[ConfigUtil::EXCLUSION_POLICY]);
        }
    }

    protected function setPartialLoad(EntityConfig $result, array $config): void
    {
        if (\array_key_exists(ConfigUtil::DISABLE_PARTIAL_LOAD, $config)
            && $config[ConfigUtil::DISABLE_PARTIAL_LOAD]
        ) {
            $result->disablePartialLoad();
        }
    }

    protected function setHints(EntityConfig $result, array $config): void
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

    protected function setInnerJoinAssociations(EntityConfig $result, array $config): void
    {
        if (!empty($config[ConfigUtil::INNER_JOIN_ASSOCIATIONS])) {
            $result->setInnerJoinAssociations($config[ConfigUtil::INNER_JOIN_ASSOCIATIONS]);
        }
    }

    protected function setOrderBy(EntityConfig $result, array $config): void
    {
        if (!empty($config[ConfigUtil::ORDER_BY])) {
            $result->setOrderBy($config[ConfigUtil::ORDER_BY]);
        }
    }

    protected function setMaxResults(EntityConfig $result, array $config): void
    {
        if (\array_key_exists(ConfigUtil::MAX_RESULTS, $config)
            && null !== $config[ConfigUtil::MAX_RESULTS]
        ) {
            $result->setMaxResults($config[ConfigUtil::MAX_RESULTS]);
        }
    }

    protected function setHasMore(EntityConfig $result, array $config): void
    {
        if (\array_key_exists(ConfigUtil::HAS_MORE, $config)
            && $config[ConfigUtil::HAS_MORE]
        ) {
            $result->setHasMore(true);
        }
    }

    protected function setPostSerializeHandler(EntityConfig $result, array $config): void
    {
        if (\array_key_exists(ConfigUtil::POST_SERIALIZE, $config)
            && null !== $config[ConfigUtil::POST_SERIALIZE]
        ) {
            $result->setPostSerializeHandler($config[ConfigUtil::POST_SERIALIZE]);
        }
    }

    protected function setPostSerializeCollectionHandler(EntityConfig $result, array $config): void
    {
        if (\array_key_exists(ConfigUtil::POST_SERIALIZE_COLLECTION, $config)
            && null !== $config[ConfigUtil::POST_SERIALIZE_COLLECTION]
        ) {
            $result->setPostSerializeCollectionHandler($config[ConfigUtil::POST_SERIALIZE_COLLECTION]);
        }
    }

    protected function setExcludedFields(EntityConfig $result, array $config): void
    {
        if (\array_key_exists(ConfigUtil::EXCLUDED_FIELDS, $config)
            && !empty($config[ConfigUtil::EXCLUDED_FIELDS])
        ) {
            $result->set(ConfigUtil::EXCLUDED_FIELDS, $config[ConfigUtil::EXCLUDED_FIELDS]);
        }
    }

    protected function setRenamedFields(EntityConfig $result, array $config): void
    {
        if (\array_key_exists(ConfigUtil::RENAMED_FIELDS, $config)
            && !empty($config[ConfigUtil::RENAMED_FIELDS])
        ) {
            $result->set(ConfigUtil::RENAMED_FIELDS, $config[ConfigUtil::RENAMED_FIELDS]);
        }
    }

    protected function setPropertyPath(FieldConfig $result, array $config): void
    {
        if (!empty($config[ConfigUtil::PROPERTY_PATH])) {
            $result->setPropertyPath($config[ConfigUtil::PROPERTY_PATH]);
        }
    }

    protected function setExcluded(FieldConfig $result, array $config): void
    {
        if (\array_key_exists(ConfigUtil::EXCLUDE, $config)
            && $config[ConfigUtil::EXCLUDE]
        ) {
            $result->setExcluded();
        }
    }

    protected function setCollapsed(FieldConfig $result, array $config): void
    {
        if (\array_key_exists(ConfigUtil::COLLAPSE, $config)
            && $config[ConfigUtil::COLLAPSE]
        ) {
            $result->setCollapsed();
        }
    }

    protected function setCollapseField(FieldConfig $result, array $config): void
    {
        if (\array_key_exists(ConfigUtil::COLLAPSE_FIELD, $config)) {
            $field = $config[ConfigUtil::COLLAPSE_FIELD];
            if ($field) {
                $result->getTargetEntity()->set(ConfigUtil::COLLAPSE_FIELD, $field);
            }
        }
    }

    protected function setDataTransformers(FieldConfig $result, array $config): void
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

    protected function setAssociationQuery(FieldConfig $result, array $config): void
    {
        if (isset($config[ConfigUtil::ASSOCIATION_QUERY])) {
            $result->set(ConfigUtil::ASSOCIATION_QUERY, $config[ConfigUtil::ASSOCIATION_QUERY]);
        }
    }
}
