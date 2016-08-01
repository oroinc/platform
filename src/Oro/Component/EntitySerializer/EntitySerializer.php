<?php

namespace Oro\Component\EntitySerializer;

use Doctrine\Common\Util\ClassUtils;
use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;

/**
 * @todo: This is draft implementation of the entity serializer.
 *       It is expected that the full implementation will be done when new API component is implemented.
 * What need to do:
 *  * by default the value of identifier field should be used
 *    for related entities (now it should be configured manually in serialization rules)
 *  * add support for extended fields
 *
 * Example of serialization rules used in the $config parameter of
 * {@see serialize}, {@see serializeEntities} and {@see prepareQuery} methods:
 *
 *  [
 *      // exclude the 'email' field
 *      'fields' => [
 *          // exclude the 'email' field
 *          'email'        => ['exclude' => true]
 *          // serialize the 'status' many-to-one relation using the value of the 'name' field
 *          'status'       => ['fields' => 'name'],
 *          // order the 'phones' many-to-many relation by the 'primary' field and
 *          // serialize each phone as a pair of 'phone' and 'primary' field
 *          'phones'       => [
 *              'exclusion_policy' => 'all',
 *              'fields'           => [
 *                  'phone'     => null,
 *                  'isPrimary' => [
 *                      // as example we can convert boolean to Yes/No string
 *                      // the data transformer must implement either
 *                      // Symfony\Component\Form\DataTransformerInterface
 *                      // or Oro\Component\EntitySerializer\DataTransformerInterface
 *                      // Also several data transformers can be specified, for example
 *                      // 'data_transformer' => ['first_transformer_service_id', 'second_transformer_service_id'],
 *                      'data_transformer' => 'boolean_to_string_transformer_service_id',
 *                      // the "primary" field should be named as "isPrimary" in the result
 *                      'property_path' => 'primary'
 *                  ]
 *              ],
 *              'order_by'         => [
 *                  'primary' => 'DESC'
 *              ]
 *          ],
 *          'addresses'    => [
 *              'fields'          => [
 *                  'owner'   => ['exclude' => true],
 *                  'country' => ['fields' => 'name'],
 *                  'types'   => [
 *                      'fields' => 'name',
 *                      'order_by' => [
 *                          'name' => 'ASC'
 *                      ]
 *                  ]
 *              ]
 *          ]
 *      ]
 *  ]
 *
 * Example of the serialization result by this config (it is supposed that the serializing entity has
 * the following fields:
 *  id
 *  name
 *  email
 *  status -> many-to-one
 *      name
 *      label
 *  phones -> many-to-many
 *      id
 *      phone
 *      primary
 *  addresses -> many-to-many
 *      id
 *      owner -> many-to-one
 *      country -> many-to-one
 *          code,
 *          name
 *      types -> many-to-many
 *          name
 *          label
 *  [
 *      'id'        => 123,
 *      'name'      => 'John Smith',
 *      'status'    => 'active',
 *      'phones'    => [
 *          ['phone' => '123-123', 'primary' => true],
 *          ['phone' => '456-456', 'primary' => false]
 *      ],
 *      'addresses' => [
 *          ['country' => 'USA', 'types' => ['billing', 'shipping']]
 *      ]
 *  ]
 *
 * Special attributes:
 * * 'disable_partial_load' - Disables using of Doctrine partial object.
 *                            It can be helpful for entities with SINGLE_TABLE inheritance mapping
 * * 'hints'                - The list of Doctrine query hints. Each item can be a string or name/value pair.
 *                            Example:
 *                            'hints' => [
 *                                  'HINT_TRANSLATABLE',
 *                                  ['name' => 'HINT_CUSTOM_OUTPUT_WALKER', 'value' => 'Acme\AST_Walker_Class']
 *                            ]
 *
 * Metadata properties:
 * * '__discriminator__' - The discriminator value an entity.
 * * '__class__'         - FQCN of an entity.
 * An example of a metadata property usage:
 *  'fields' => [
 *      'type' => ['property_path' => '__discriminator__']
 *  ]
 *
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
class EntitySerializer
{
    /** @var DoctrineHelper */
    protected $doctrineHelper;

    /** @var DataAccessorInterface */
    protected $dataAccessor;

    /** @var DataTransformerInterface */
    protected $dataTransformer;

    /** @var QueryFactory */
    protected $queryFactory;

    /** @var FieldAccessor */
    protected $fieldAccessor;

    /** @var ConfigNormalizer */
    protected $configNormalizer;

    /** @var ConfigConverter */
    protected $configConverter;

    /** @var DataNormalizer */
    protected $dataNormalizer;

    /**
     * @param DoctrineHelper           $doctrineHelper
     * @param DataAccessorInterface    $dataAccessor
     * @param DataTransformerInterface $dataTransformer
     * @param QueryFactory             $queryFactory
     * @param FieldAccessor            $fieldAccessor
     * @param ConfigNormalizer         $configNormalizer
     * @param DataNormalizer           $dataNormalizer
     */
    public function __construct(
        DoctrineHelper $doctrineHelper,
        DataAccessorInterface $dataAccessor,
        DataTransformerInterface $dataTransformer,
        QueryFactory $queryFactory,
        FieldAccessor $fieldAccessor,
        ConfigNormalizer $configNormalizer,
        DataNormalizer $dataNormalizer
    ) {
        $this->doctrineHelper   = $doctrineHelper;
        $this->dataAccessor     = $dataAccessor;
        $this->dataTransformer  = $dataTransformer;
        $this->queryFactory     = $queryFactory;
        $this->fieldAccessor    = $fieldAccessor;
        $this->configNormalizer = $configNormalizer;
        $this->dataNormalizer   = $dataNormalizer;

        $this->configConverter = new ConfigConverter();
    }

    /**
     * @param QueryBuilder       $qb     A query builder is used to get data
     * @param EntityConfig|array $config Serialization rules
     *
     * @return array
     */
    public function serialize(QueryBuilder $qb, $config)
    {
        $entityConfig = $this->normalizeConfig($config);

        $this->updateQuery($qb, $entityConfig);
        $data = $this->queryFactory->getQuery($qb, $entityConfig)->getResult();
        $data = $this->serializeItems((array)$data, $this->doctrineHelper->getRootEntityClass($qb), $entityConfig);

        return $this->dataNormalizer->normalizeData($data, $entityConfig);
    }

    /**
     * @param object[]           $entities    The list of entities to be serialized
     * @param string             $entityClass The entity class name
     * @param EntityConfig|array $config      Serialization rules
     *
     * @return array
     */
    public function serializeEntities(array $entities, $entityClass, $config)
    {
        $entityConfig = $this->normalizeConfig($config);

        $data = $this->serializeItems($entities, $entityClass, $entityConfig);

        return $this->dataNormalizer->normalizeData($data, $entityConfig);
    }

    /**
     * @param QueryBuilder       $qb
     * @param EntityConfig|array $config
     */
    public function prepareQuery(QueryBuilder $qb, $config)
    {
        $this->updateQuery($qb, $this->normalizeConfig($config));
    }

    /**
     * @param EntityConfig|array $config
     *
     * @return EntityConfig
     */
    protected function normalizeConfig($config)
    {
        $normalizedConfig = $this->configNormalizer->normalizeConfig(
            $config instanceof EntityConfig ? $config->toArray() : $config
        );

        return $this->configConverter->convertConfig($normalizedConfig);
    }

    /**
     * @param object[]     $entities    The list of entities to be serialized
     * @param string       $entityClass The entity class name
     * @param EntityConfig $config      Serialization rules
     * @param bool         $useIdAsKey  Defines whether the entity id should be used as a key of the result array
     *
     * @return array
     */
    protected function serializeItems(array $entities, $entityClass, EntityConfig $config, $useIdAsKey = false)
    {
        if (empty($entities)) {
            return [];
        }

        $result = [];

        $idFieldName = $this->doctrineHelper->getEntityIdFieldName($entityClass);
        if ($useIdAsKey) {
            foreach ($entities as $entity) {
                $id          = $this->dataAccessor->getValue($entity, $idFieldName);
                $result[$id] = $this->serializeItem($entity, $entityClass, $config);
            }
        } else {
            foreach ($entities as $entity) {
                $result[] = $this->serializeItem($entity, $entityClass, $config);
            }
        }

        $this->loadRelatedData($result, $entityClass, $this->getEntityIds($entities, $idFieldName), $config);

        $postSerializeHandler = $config->getPostSerializeHandler();
        if (null !== $postSerializeHandler) {
            foreach ($result as &$resultItem) {
                $resultItem = $this->postSerialize($resultItem, $postSerializeHandler);
            }
        }

        return $result;
    }

    /**
     * @param mixed        $entity
     * @param string       $entityClass
     * @param EntityConfig $config
     *
     * @return array
     */
    protected function serializeItem($entity, $entityClass, EntityConfig $config)
    {
        if (!$entity) {
            return [];
        }

        $result         = [];
        $entityMetadata = $this->doctrineHelper->getEntityMetadata($entityClass);
        $resultFields   = $this->fieldAccessor->getFieldsToSerialize($entityClass, $config);
        foreach ($resultFields as $field) {
            $fieldConfig = $config->getField($field);

            $value = null;
            if ($this->dataAccessor->tryGetValue($entity, $field, $value)) {
                if ($this->isAssociation($field, $entityMetadata, $fieldConfig)) {
                    if (is_object($value)) {
                        $targetConfig = $this->getTargetEntity($config, $field);
                        if (null !== $targetConfig && !$targetConfig->isEmpty()) {
                            $targetEntityClass = $this->getAssociationTargetClass($field, $entityMetadata, $value);
                            $targetEntityId    = $this->dataAccessor->getValue(
                                $value,
                                $this->doctrineHelper->getEntityIdFieldName($targetEntityClass)
                            );

                            $value = $this->serializeItem($value, $targetEntityClass, $targetConfig);
                            $items = [$value];
                            $this->loadRelatedData($items, $targetEntityClass, [$targetEntityId], $targetConfig);
                            $value = reset($items);

                            $postSerializeHandler = $targetConfig->getPostSerializeHandler();
                            if (null !== $postSerializeHandler) {
                                $value = $this->postSerialize($value, $postSerializeHandler);
                            }
                        } else {
                            $value = $this->transformValue($entityClass, $field, $value, $fieldConfig);
                        }
                    }
                } else {
                    $value = $this->transformValue($entityClass, $field, $value, $fieldConfig);
                }
                $result[$field] = $value;
            } elseif (null !== $fieldConfig) {
                $propertyPath = $fieldConfig->getPropertyPath() ?: $field;
                if (ConfigUtil::isMetadataProperty($propertyPath)) {
                    $result[$field] = $this->fieldAccessor->getMetadataProperty(
                        $entity,
                        $propertyPath,
                        $entityMetadata
                    );
                }
            }
        }

        return $result;
    }

    /**
     * @param string           $fieldName
     * @param EntityMetadata   $entityMetadata
     * @param FieldConfig|null $fieldConfig
     *
     * @return bool
     */
    protected function isAssociation($fieldName, EntityMetadata $entityMetadata, FieldConfig $fieldConfig = null)
    {
        return
            $entityMetadata->isAssociation($fieldName)
            || (null !== $fieldConfig && $fieldConfig->getTargetEntity());
    }

    /**
     * @param string         $fieldName
     * @param EntityMetadata $entityMetadata
     * @param mixed          $value
     *
     * @return null|string
     */
    protected function getAssociationTargetClass($fieldName, EntityMetadata $entityMetadata, $value)
    {
        return $entityMetadata->isAssociation($fieldName)
            ? $entityMetadata->getAssociationTargetClass($fieldName)
            : ClassUtils::getClass($value);
    }

    /**
     * @param QueryBuilder $qb
     * @param EntityConfig $config
     */
    protected function updateQuery(QueryBuilder $qb, EntityConfig $config)
    {
        $rootAlias      = $this->doctrineHelper->getRootAlias($qb);
        $entityClass    = $this->doctrineHelper->getRootEntityClass($qb);
        $entityMetadata = $this->doctrineHelper->getEntityMetadata($entityClass);

        $qb->resetDQLPart('select');
        $this->updateSelectQueryPart($qb, $rootAlias, $entityClass, $config);

        $aliasCounter = 0;
        $fields       = $this->fieldAccessor->getFields($entityClass, $config);
        foreach ($fields as $field) {
            if (!$entityMetadata->isAssociation($field) || $entityMetadata->isCollectionValuedAssociation($field)) {
                continue;
            }

            $join  = sprintf('%s.%s', $rootAlias, $field);
            $alias = $this->getExistingJoinAlias($qb, $rootAlias, $join);
            if (!$alias) {
                $alias = 'a' . ++$aliasCounter;
                $qb->leftJoin($join, $alias);
            }
            $this->updateSelectQueryPart(
                $qb,
                $alias,
                $entityMetadata->getAssociationTargetClass($field),
                $this->getTargetEntity($config, $field),
                true
            );
        }
    }

    /**
     * @param QueryBuilder $qb
     * @param string       $rootAlias
     * @param string       $join
     *
     * @return string|null
     */
    protected function getExistingJoinAlias(QueryBuilder $qb, $rootAlias, $join)
    {
        $joins = $qb->getDQLPart('join');
        if (!empty($joins[$rootAlias])) {
            /** @var Query\Expr\Join $item */
            foreach ($joins[$rootAlias] as $item) {
                if ($item->getJoin() === $join) {
                    return $item->getAlias();
                }
            }
        }

        return null;
    }

    /**
     * @param QueryBuilder $qb
     * @param string       $alias
     * @param string       $entityClass
     * @param EntityConfig $config
     * @param bool         $withAssociations
     */
    protected function updateSelectQueryPart(
        QueryBuilder $qb,
        $alias,
        $entityClass,
        EntityConfig $config,
        $withAssociations = false
    ) {
        if ($config->isPartialLoadEnabled()) {
            $fields = $this->fieldAccessor->getFieldsToSelect($entityClass, $config, $withAssociations);
            $qb->addSelect(sprintf('partial %s.{%s}', $alias, implode(',', $fields)));
        } else {
            $qb->addSelect($alias);
        }
    }

    /**
     * @param array        $result
     * @param string       $entityClass
     * @param array        $entityIds
     * @param EntityConfig $config
     */
    protected function loadRelatedData(array &$result, $entityClass, $entityIds, EntityConfig $config)
    {
        $relatedData    = [];
        $entityMetadata = $this->doctrineHelper->getEntityMetadata($entityClass);
        $fields         = $this->fieldAccessor->getFields($entityClass, $config);
        foreach ($fields as $field) {
            if (!$entityMetadata->isCollectionValuedAssociation($field)) {
                continue;
            }

            $mapping      = $entityMetadata->getAssociationMapping($field);
            $targetConfig = $this->getTargetEntity($config, $field);

            $relatedData[$field] = $this->isSingleStepLoading($mapping['targetEntity'], $targetConfig)
                ? $this->loadRelatedItemsForSimpleEntity($entityIds, $mapping, $targetConfig)
                : $this->loadRelatedItems($entityIds, $mapping, $targetConfig);
        }
        if (!empty($relatedData)) {
            $this->applyRelatedData($result, $entityClass, $relatedData);
        }
    }

    /**
     * @param array  $result
     * @param string $entityClass
     * @param array  $relatedData [field => [entityId => [field => value, ...], ...], ...]
     *
     * @throws \RuntimeException
     */
    protected function applyRelatedData(array &$result, $entityClass, $relatedData)
    {
        $entityIdFieldName = $this->doctrineHelper->getEntityIdFieldName($entityClass);
        foreach ($result as &$resultItem) {
            if (!array_key_exists($entityIdFieldName, $resultItem)) {
                throw new \RuntimeException(
                    sprintf('The result item does not contain the entity identifier. Entity: %s.', $entityClass)
                );
            }
            $entityId = $resultItem[$entityIdFieldName];
            foreach ($relatedData as $field => $relatedItems) {
                $resultItem[$field] = [];
                if (!empty($relatedItems[$entityId])) {
                    foreach ($relatedItems[$entityId] as $relatedItem) {
                        $resultItem[$field][] = $relatedItem;
                    }
                }
            }
        }
    }

    /**
     * @param string       $entityClass
     * @param EntityConfig $config
     *
     * @return bool
     */
    protected function isSingleStepLoading($entityClass, EntityConfig $config)
    {
        return
            null === $config->getMaxResults()
            && !$this->hasAssociations($entityClass, $config);
    }

    /**
     * @param array        $entityIds
     * @param array        $mapping
     * @param EntityConfig $config
     *
     * @return array [entityId => [field => value, ...], ...]
     */
    protected function loadRelatedItems($entityIds, $mapping, EntityConfig $config)
    {
        $result = [];

        $entityClass = $mapping['targetEntity'];
        $bindings = $this->getRelatedItemsBindings($entityIds, $mapping, $config);

        $items = [];
        $resultFieldName = $this->getIdFieldNameIfIdOnlyRequested($config, $entityClass);
        if (null !== $resultFieldName) {
            $postSerializeHandler = $config->getPostSerializeHandler();
            $relatedItemIds = $this->getRelatedItemsIds($bindings);
            foreach ($relatedItemIds as $relatedItemId) {
                $relatedItem = [$resultFieldName => $relatedItemId];
                if (null !== $postSerializeHandler) {
                    $relatedItem = $this->postSerialize($relatedItem, $postSerializeHandler);
                }
                $items[$relatedItemId] = $relatedItem;
            }
        } else {
            $qb = $this->queryFactory->getRelatedItemsQueryBuilder(
                $entityClass,
                $this->getRelatedItemsIds($bindings)
            );
            $this->updateQuery($qb, $config);
            $data = $this->queryFactory->getQuery($qb, $config)->getResult();
            if (!empty($data)) {
                $items = $this->serializeItems((array)$data, $entityClass, $config, true);
            }
        }
        if (!empty($items)) {
            foreach ($bindings as $entityId => $relatedEntityIds) {
                foreach ($relatedEntityIds as $relatedEntityId) {
                    if (isset($items[$relatedEntityId])) {
                        $result[$entityId][] = $items[$relatedEntityId];
                    }
                }
            }
        }

        return $result;
    }

    /**
     * @param EntityConfig $config
     * @param string       $entityClass
     *
     * @return string|null The name of result field if only identity field should be returned;
     *                     otherwise, NULL
     */
    protected function getIdFieldNameIfIdOnlyRequested(EntityConfig $config, $entityClass)
    {
        if (!$config->isExcludeAll()) {
            return null;
        }
        $fields = $config->getFields();
        if (count($fields) !== 1) {
            return null;
        }
        reset($fields);
        /** @var FieldConfig $field */
        list($fieldName, $field) = each($fields);
        $targetConfig = $field->getTargetEntity();
        if (null !== $targetConfig && !$targetConfig->isEmpty()) {
            return null;
        }

        $propertyPath = $field->getPropertyPath() ?: $fieldName;
        if ($this->doctrineHelper->getEntityIdFieldName($entityClass) !== $propertyPath) {
            return null;
        }

        return $fieldName;
    }

    /**
     * @param array        $entityIds
     * @param array        $mapping
     * @param EntityConfig $config
     *
     * @return array [entityId => [relatedEntityId, ...], ...]
     */
    protected function getRelatedItemsBindings($entityIds, $mapping, EntityConfig $config)
    {
        $rows = $this->queryFactory->getRelatedItemsIds($mapping, $entityIds, $config);

        $result = [];
        if (!empty($rows)) {
            $relatedEntityIdType = $this->getEntityIdType($mapping['targetEntity']);
            foreach ($rows as $row) {
                $result[$row['entityId']][] = $this->getTypedEntityId($row['relatedEntityId'], $relatedEntityIdType);
            }
        }

        return $result;
    }

    /**
     * @param array $bindings [entityId => relatedEntityId, ...]
     *
     * @return array of unique ids of all related entities from $bindings array
     */
    protected function getRelatedItemsIds($bindings)
    {
        $result = [];
        foreach ($bindings as $ids) {
            foreach ($ids as $id) {
                if (!isset($result[$id])) {
                    $result[$id] = $id;
                }
            }
        }

        return array_values($result);
    }

    /**
     * @param array        $entityIds
     * @param array        $mapping
     * @param EntityConfig $config
     *
     * @return array [entityId => [field => value, ...], ...]
     */
    protected function loadRelatedItemsForSimpleEntity($entityIds, $mapping, EntityConfig $config)
    {
        $qb = $this->queryFactory->getToManyAssociationQueryBuilder($mapping, $entityIds);

        $orderBy = $config->getOrderBy();
        foreach ($orderBy as $field => $direction) {
            $qb->addOrderBy(sprintf('r.%s', $field), $direction);
        }

        $result = [];

        $entityClass = $mapping['targetEntity'];
        $targetEntityMetadata = $this->doctrineHelper->getEntityMetadata($entityClass);
        if ($targetEntityMetadata->hasInheritance()) {
            $fields = $this->fieldAccessor->getFieldsToSelect($entityClass, $config);
            $qb->addSelect(sprintf('partial r.{%s}', implode(',', $fields)));
            $items = $this->queryFactory->getQuery($qb, $config)->getResult();
            $postSerializeHandler = $config->getPostSerializeHandler();
            if (null !== $postSerializeHandler) {
                foreach ($items as $item) {
                    $result[$item['entityId']][] = $this->postSerialize(
                        $this->serializeItem($item[0], $entityClass, $config),
                        $postSerializeHandler
                    );
                }
            } else {
                foreach ($items as $item) {
                    $result[$item['entityId']][] = $this->serializeItem($item[0], $entityClass, $config);
                }
            }
        } else {
            $fields = $this->fieldAccessor->getFieldsToSerialize($entityClass, $config);
            foreach ($fields as $field) {
                $qb->addSelect(sprintf('r.%s', $field));
            }
            $items = $this->queryFactory->getQuery($qb, $config)->getArrayResult();
            $postSerializeHandler = $config->getPostSerializeHandler();
            if (null !== $postSerializeHandler) {
                foreach ($items as $item) {
                    $result[$item['entityId']][] = $this->postSerialize(
                        $this->serializeItem($item, $entityClass, $config),
                        $postSerializeHandler
                    );
                }
            } else {
                foreach ($items as $item) {
                    $result[$item['entityId']][] = $this->serializeItem($item, $entityClass, $config);
                }
            }
        }

        return $result;
    }

    /**
     * @param string       $entityClass
     * @param EntityConfig $config
     *
     * @return bool
     */
    protected function hasAssociations($entityClass, EntityConfig $config)
    {
        $entityMetadata = $this->doctrineHelper->getEntityMetadata($entityClass);
        $fields         = $this->fieldAccessor->getFields($entityClass, $config);
        foreach ($fields as $field) {
            if ($entityMetadata->isAssociation($field)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param object[] $entities    A list of entities
     * @param string   $idFieldName The name of entity identifier field
     *
     * @return array of unique ids of all entities from $entities array
     */
    protected function getEntityIds($entities, $idFieldName)
    {
        $ids = [];
        foreach ($entities as $entity) {
            $id = $this->dataAccessor->getValue($entity, $idFieldName);
            if (!isset($ids[$id])) {
                $ids[$id] = $id;
            }
        }

        return array_values($ids);
    }

    /**
     * @param string $entityClass
     *
     * @return string|null
     */
    protected function getEntityIdType($entityClass)
    {
        $metadata = $this->doctrineHelper->getEntityMetadata($entityClass);

        return $metadata->getFieldType($metadata->getSingleIdentifierFieldName());
    }

    /**
     * @param mixed  $value
     * @param string $type
     *
     * @return mixed
     */
    protected function getTypedEntityId($value, $type)
    {
        if (Type::INTEGER === $type || Type::SMALLINT === $type) {
            $value = (int)$value;
        }

        return $value;
    }

    /**
     * @param EntityConfig $config
     * @param string       $field
     *
     * @return EntityConfig
     */
    public function getTargetEntity(EntityConfig $config, $field)
    {
        $fieldConfig = $config->getField($field);
        if (null === $fieldConfig) {
            return new InternalEntityConfig();
        }

        $targetConfig = $fieldConfig->getTargetEntity();
        if (null === $targetConfig) {
            $targetConfig = new InternalEntityConfig();
            $fieldConfig->setTargetEntity($targetConfig);
        }

        return $targetConfig;
    }

    /**
     * @param string           $entityClass
     * @param string           $fieldName
     * @param mixed            $fieldValue
     * @param FieldConfig|null $fieldConfig
     *
     * @return mixed
     */
    protected function transformValue($entityClass, $fieldName, $fieldValue, FieldConfig $fieldConfig = null)
    {
        return $this->dataTransformer->transform(
            $entityClass,
            $fieldName,
            $fieldValue,
            null !== $fieldConfig ? $fieldConfig->toArray(true) : []
        );
    }

    /**
     * @param array    $item
     * @param callable $handler
     *
     * @return array
     */
    protected function postSerialize(array $item, $handler)
    {
        // @deprecated since 1.9. New signature of 'post_serialize' callback is function (array $item) : array
        // Old signature was function (array &$item) : void
        // The following implementation supports both new and old signature of the callback
        // Remove this implementation when a support of old signature will not be required
        if ($handler instanceof \Closure) {
            $handleResult = $handler($item);
            if (null !== $handleResult) {
                $item = $handleResult;
            }
        } else {
            $item = call_user_func($handler, $item);
        }

        /* New implementation, uncomment it when a support of old signature will not be required
        $item = call_user_func($handler, $item);
        */

        return $item;
    }
}
