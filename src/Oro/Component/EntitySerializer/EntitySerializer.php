<?php

namespace Oro\Component\EntitySerializer;

use Doctrine\Common\Util\ClassUtils;
use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use Oro\Component\DoctrineUtils\ORM\QueryBuilderUtil;
use Oro\Component\EntitySerializer\Filter\EntityAwareFilterInterface;

/**
 * The serializer that loads data for primary and associated entities from the database
 * using the minimum possible number of queries and converts entities to an array
 * bases on a specified configuration.
 *
 * Things that can be improved:
 *  * by default the value of identifier field should be used
 *    for related entities (now it should be configured manually in serialization rules)
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

    /** @var SerializationHelper */
    protected $serializationHelper;

    /** @var DataAccessorInterface */
    protected $dataAccessor;

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

    /** @var EntityAwareFilterInterface */
    protected $fieldFilter;

    /**
     * @param DoctrineHelper        $doctrineHelper
     * @param SerializationHelper   $serializationHelper
     * @param DataAccessorInterface $dataAccessor
     * @param QueryFactory          $queryFactory
     * @param FieldAccessor         $fieldAccessor
     * @param ConfigNormalizer      $configNormalizer
     * @param ConfigConverter       $configConverter
     * @param DataNormalizer        $dataNormalizer
     */
    public function __construct(
        DoctrineHelper $doctrineHelper,
        SerializationHelper $serializationHelper,
        DataAccessorInterface $dataAccessor,
        QueryFactory $queryFactory,
        FieldAccessor $fieldAccessor,
        ConfigNormalizer $configNormalizer,
        ConfigConverter $configConverter,
        DataNormalizer $dataNormalizer
    ) {
        $this->doctrineHelper = $doctrineHelper;
        $this->serializationHelper = $serializationHelper;
        $this->dataAccessor = $dataAccessor;
        $this->queryFactory = $queryFactory;
        $this->fieldAccessor = $fieldAccessor;
        $this->configNormalizer = $configNormalizer;
        $this->configConverter = $configConverter;
        $this->dataNormalizer = $dataNormalizer;
    }

    /**
     * @param EntityAwareFilterInterface $filter
     */
    public function setFieldsFilter(EntityAwareFilterInterface $filter)
    {
        $this->fieldFilter = $filter;
    }

    /**
     * @param QueryBuilder       $qb      A query builder is used to get data
     * @param EntityConfig|array $config  Serialization rules
     * @param array              $context Options post serializers and data transformers have access to
     *
     * @return array
     */
    public function serialize(QueryBuilder $qb, $config, array $context = [])
    {
        $entityConfig = $this->normalizeConfig($config);

        $this->updateQuery($qb, $entityConfig);
        $data = $this->queryFactory->getQuery($qb, $entityConfig)->getResult();

        $hasMore = $this->preSerializeItems($data, $entityConfig, $qb->getMaxResults());
        $data = $this->serializeItems(
            (array)$data,
            $this->doctrineHelper->getRootEntityClass($qb),
            $entityConfig,
            $context
        );
        if ($hasMore) {
            $data[ConfigUtil::INFO_RECORD_KEY] = [ConfigUtil::HAS_MORE => true];
        }

        return $this->dataNormalizer->normalizeData($data, $entityConfig);
    }

    /**
     * @param object[]           $entities    The list of entities to be serialized
     * @param string             $entityClass The entity class name
     * @param EntityConfig|array $config      Serialization rules
     * @param array              $context     Options post serializers and data transformers have access to
     *
     * @return array
     */
    public function serializeEntities(array $entities, $entityClass, $config, array $context = [])
    {
        $entityConfig = $this->normalizeConfig($config);

        $data = $this->serializeItems($entities, $entityClass, $entityConfig, $context);

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
        if ($config instanceof EntityConfig) {
            $config = $config->toArray();
        }

        return $this->configConverter->convertConfig(
            $this->configNormalizer->normalizeConfig($config)
        );
    }

    /**
     * @param array        $items
     * @param EntityConfig $config
     * @param int|null     $limit
     *
     * @return bool
     */
    protected function preSerializeItems(array &$items, EntityConfig $config, $limit)
    {
        $hasMore = false;
        if (null !== $limit && $config->getHasMore() && count($items) > $limit) {
            $hasMore = true;
            $items = array_slice($items, 0, $limit);
        }

        return $hasMore;
    }

    /**
     * @param object[]     $entities    The list of entities to be serialized
     * @param string       $entityClass The entity class name
     * @param EntityConfig $config      Serialization rules
     * @param array        $context     Options post serializers and data transformers have access to
     * @param bool         $useIdAsKey  Defines whether the entity id should be used as a key of the result array
     *
     * @return array
     */
    protected function serializeItems(
        array $entities,
        $entityClass,
        EntityConfig $config,
        array $context,
        $useIdAsKey = false
    ) {
        if (empty($entities)) {
            return [];
        }

        $result = [];

        $idFieldName = $this->doctrineHelper->getEntityIdFieldName($entityClass);
        if ($useIdAsKey) {
            foreach ($entities as $entity) {
                $id = $this->dataAccessor->getValue($entity, $idFieldName);
                $result[$id] = $this->serializeItem($entity, $entityClass, $config, $context);
            }
        } else {
            foreach ($entities as $entity) {
                $result[] = $this->serializeItem($entity, $entityClass, $config, $context);
            }
        }

        $this->loadRelatedData(
            $result,
            $entityClass,
            $this->getEntityIds($entities, $idFieldName),
            $config,
            $context
        );

        $handler = $config->getPostSerializeHandler();
        if (null !== $handler) {
            foreach ($result as $key => $item) {
                $result[$key] = $this->serializationHelper->postSerialize($item, $handler, $context);
            }
        }

        return $this->serializationHelper->processPostSerializeCollection($result, $config, $context);
    }

    /**
     * @param mixed        $entity
     * @param string       $entityClass
     * @param EntityConfig $config
     * @param array        $context
     *
     * @return array
     *
     * @SuppressWarnings(PHPMD.NPathComplexity)
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    protected function serializeItem($entity, $entityClass, EntityConfig $config, array $context)
    {
        if (!$entity) {
            return [];
        }

        $result = [];
        $referenceFields = [];
        $entityMetadata = $this->doctrineHelper->getEntityMetadata($entityClass);
        $fields = $this->fieldAccessor->getFieldsToSerialize($entityClass, $config);
        foreach ($fields as $field) {
            $fieldConfig = $config->getField($field);
            $propertyPath = $this->getPropertyPath($field, $fieldConfig);
            $path = ConfigUtil::explodePropertyPath($propertyPath);
            $isReference = count($path) > 1;

            if (null !== $this->fieldFilter && !$isReference) {
                $isFieldAllowed = $this->fieldFilter->checkField($entity, $entityClass, $propertyPath);
                if (EntityAwareFilterInterface::FILTER_NOTHING !== $isFieldAllowed) {
                    if (EntityAwareFilterInterface::FILTER_VALUE === $isFieldAllowed) {
                        // return field but without value
                        $result[$field] = null;
                    }
                    continue;
                }
            }

            if ($isReference) {
                $referenceFields[$field] = $path;
                continue;
            }

            $value = null;
            if ($this->dataAccessor->tryGetValue($entity, $propertyPath, $value)) {
                if (null !== $value) {
                    if ($this->isAssociation($propertyPath, $entityMetadata, $fieldConfig)) {
                        if (is_object($value)) {
                            $targetConfig = $this->getTargetEntity($config, $field);
                            $targetEntityClass = $this->doctrineHelper->getAssociationTargetClass(
                                $entityMetadata,
                                $path
                            );
                            if (!$targetEntityClass) {
                                $targetEntityClass = ClassUtils::getClass($value);
                            }
                            $targetEntityId = $this->dataAccessor->getValue(
                                $value,
                                $this->doctrineHelper->getEntityIdFieldName($targetEntityClass)
                            );

                            $value = $this->serializeItem($value, $targetEntityClass, $targetConfig, $context);
                            $this->loadRelatedDataForOneEntity(
                                $value,
                                $targetEntityClass,
                                $targetEntityId,
                                $targetConfig,
                                $context
                            );
                        }
                    } else {
                        $value = $this->serializationHelper->transformValue(
                            $entityClass,
                            $field,
                            $value,
                            $context,
                            $fieldConfig
                        );
                    }
                }
                $result[$field] = $value;
            } elseif ($this->fieldAccessor->isMetadataProperty($propertyPath)) {
                $result[$field] = $this->fieldAccessor->getMetadataProperty(
                    $entity,
                    $propertyPath,
                    $entityMetadata
                );
            }
        }

        if (!empty($referenceFields)) {
            $result = $this->serializationHelper->handleFieldsReferencedToChildFields(
                $result,
                $entityClass,
                $config,
                $context,
                $referenceFields
            );
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
     * @param QueryBuilder $qb
     * @param EntityConfig $config
     */
    protected function updateQuery(QueryBuilder $qb, EntityConfig $config)
    {
        $rootAlias = $this->doctrineHelper->getRootAlias($qb);
        $entityClass = $this->doctrineHelper->getRootEntityClass($qb);
        $entityMetadata = $this->doctrineHelper->getEntityMetadata($entityClass);

        $qb->resetDQLPart('select');
        $this->updateSelectQueryPart($qb, $rootAlias, $entityClass, $config);

        $aliasCounter = 0;
        $fields = $this->fieldAccessor->getFields($entityClass, $config);
        foreach ($fields as $field) {
            $propertyPath = $this->getPropertyPath($field, $config->getField($field));
            if (!$entityMetadata->isAssociation($propertyPath)
                || $entityMetadata->isCollectionValuedAssociation($propertyPath)
            ) {
                continue;
            }

            $join = sprintf('%s.%s', $rootAlias, $propertyPath);
            $alias = $this->getExistingJoinAlias($qb, $rootAlias, $join);
            if (!$alias) {
                $alias = 'a' . ++$aliasCounter;
                $qb->leftJoin($join, $alias);
            }
            $this->updateSelectQueryPart(
                $qb,
                $alias,
                $entityMetadata->getAssociationTargetClass($propertyPath),
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
     * @param array        $context
     */
    protected function loadRelatedData(array &$result, $entityClass, $entityIds, EntityConfig $config, array $context)
    {
        $relatedData = [];
        $entityMetadata = $this->doctrineHelper->getEntityMetadata($entityClass);
        $fields = $this->fieldAccessor->getFields($entityClass, $config);
        foreach ($fields as $field) {
            $propertyPath = $this->getPropertyPath($field, $config->getField($field));
            if (!$entityMetadata->isCollectionValuedAssociation($propertyPath)) {
                continue;
            }

            $allowedIds = $entityIds;
            if (null !== $this->fieldFilter) {
                $allowedIds = $this->getAccessibleIds($entityIds, $entityClass, $propertyPath);
            }
            if (empty($allowedIds)) {
                continue;
            }

            $mapping = $entityMetadata->getAssociationMapping($propertyPath);
            $targetConfig = $this->getTargetEntity($config, $field);

            if ($this->isSingleStepLoading($mapping['targetEntity'], $targetConfig)) {
                $value = $this->loadRelatedItemsForSimpleEntity($allowedIds, $mapping, $targetConfig, $context);
            } else {
                $value = $this->loadRelatedItems($allowedIds, $mapping, $targetConfig, $context);
            }
            $relatedData[$field] = $value;
        }
        if (!empty($relatedData)) {
            $this->applyRelatedData($result, $entityClass, $config, $relatedData);
        }
    }

    /**
     * @param mixed        $entity
     * @param string       $entityClass
     * @param mixed        $entityId
     * @param EntityConfig $config
     * @param array        $context
     */
    protected function loadRelatedDataForOneEntity(
        &$entity,
        $entityClass,
        $entityId,
        EntityConfig $config,
        array $context
    ) {
        $items = [$entity];
        $this->loadRelatedData($items, $entityClass, [$entityId], $config, $context);
        $entity = $items[0];

        $handler = $config->getPostSerializeHandler();
        if (null !== $handler) {
            $entity = $this->serializationHelper->postSerialize($entity, $handler, $context);
        }
    }

    /**
     * @param array        $result
     * @param string       $entityClass
     * @param EntityConfig $config
     * @param array        $relatedData [field => [entityId => [field => value, ...], ...], ...]
     *
     * @throws \RuntimeException
     */
    protected function applyRelatedData(array &$result, $entityClass, EntityConfig $config, $relatedData)
    {
        $entityIdFieldName = $this->fieldAccessor->getIdField($entityClass, $config);
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
                    $items = $relatedItems[$entityId];
                    foreach ($items as $key => $relatedItem) {
                        if (ConfigUtil::INFO_RECORD_KEY !== $key) {
                            $resultItem[$field][] = $relatedItem;
                        }
                    }
                    if (isset($items[ConfigUtil::INFO_RECORD_KEY])) {
                        $resultItem[$field][ConfigUtil::INFO_RECORD_KEY] = $items[ConfigUtil::INFO_RECORD_KEY];
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
     * @param array        $context
     *
     * @return array [entityId => [field => value, ...], ...]
     */
    protected function loadRelatedItems($entityIds, $mapping, EntityConfig $config, array $context)
    {
        $entityClass = $mapping['targetEntity'];
        $limit = $config->getMaxResults();
        $bindings = $this->getRelatedItemsBindings($entityIds, $mapping, $config);

        $items = [];
        $resultFieldName = $this->getIdFieldNameIfIdOnlyRequested($config, $entityClass);
        $relatedItemIds = $this->getRelatedItemsIds($bindings, $limit);
        if (null !== $resultFieldName) {
            $handler = $config->getPostSerializeHandler();
            foreach ($relatedItemIds as $relatedItemId) {
                $item = [$resultFieldName => $relatedItemId];
                if (null !== $handler) {
                    $item = $this->serializationHelper->postSerialize($item, $handler, $context);
                }
                $items[$relatedItemId] = $item;
            }
            $items = $this->serializationHelper->processPostSerializeCollection($items, $config, $context);
        } else {
            $qb = $this->queryFactory->getRelatedItemsQueryBuilder($entityClass, $relatedItemIds);
            $this->updateQuery($qb, $config);
            $data = $this->queryFactory->getQuery($qb, $config)->getResult();
            if (!empty($data)) {
                $items = $this->serializeItems((array)$data, $entityClass, $config, $context, true);
            }
        }

        $result = [];
        if (!empty($items)) {
            foreach ($bindings as $entityId => $relatedEntityIds) {
                foreach ($relatedEntityIds as $relatedEntityId) {
                    if (isset($items[$relatedEntityId])) {
                        $result[$entityId][] = $items[$relatedEntityId];
                    }
                }
                if (null !== $limit
                    && $config->getHasMore()
                    && isset($result[$entityId])
                    && count($result[$entityId]) == $limit
                    && count($relatedEntityIds) > $limit
                ) {
                    $result[$entityId][ConfigUtil::INFO_RECORD_KEY] = [ConfigUtil::HAS_MORE => true];
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
        $fieldName = key($fields);
        $field = current($fields);
        if ($this->doctrineHelper->getEntityIdFieldName($entityClass) !== $field->getPropertyPath($fieldName)) {
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
            $relatedEntityIdType = $this->doctrineHelper->getEntityIdType($mapping['targetEntity']);
            foreach ($rows as $row) {
                $result[$row['entityId']][] = $this->getTypedEntityId($row['relatedEntityId'], $relatedEntityIdType);
            }
        }

        return $result;
    }

    /**
     * @param array    $bindings [entityId => relatedEntityId, ...]
     * @param int|null $limit
     *
     * @return array of unique ids of all related entities from $bindings array
     */
    protected function getRelatedItemsIds($bindings, $limit)
    {
        $result = [];
        foreach ($bindings as $ids) {
            $counter = 0;
            foreach ($ids as $id) {
                $counter++;
                if (null !== $limit && $counter > $limit) {
                    break;
                }

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
     * @param array        $context
     *
     * @return array [entityId => [field => value, ...], ...]
     */
    protected function loadRelatedItemsForSimpleEntity(
        $entityIds,
        $mapping,
        EntityConfig $config,
        array $context
    ) {
        $qb = $this->queryFactory->getToManyAssociationQueryBuilder($mapping, $entityIds);
        $orderBy = $config->getOrderBy();
        foreach ($orderBy as $field => $direction) {
            $qb->addOrderBy(QueryBuilderUtil::getField('r', $field), QueryBuilderUtil::getSortOrder($direction));
        }

        $entityClass = $mapping['targetEntity'];
        $fields = $this->fieldAccessor->getFieldsToSelect($entityClass, $config);
        $targetEntityMetadata = $this->doctrineHelper->getEntityMetadata($entityClass);
        $isObject = false;
        if ($targetEntityMetadata->hasInheritance()) {
            $isObject = true;
            $qb->addSelect(sprintf('partial r.{%s}', implode(',', $fields)));
            $items = $this->queryFactory->getQuery($qb, $config)->getResult();
        } else {
            foreach ($fields as $field) {
                $qb->addSelect(QueryBuilderUtil::getField('r', $field));
            }
            $items = $this->queryFactory->getQuery($qb, $config)->getArrayResult();
        }

        return $this->serializeRelatedItemsForSimpleEntity($items, $isObject, $entityClass, $config, $context);
    }

    /**
     * @param array        $items
     * @param bool         $isObject
     * @param string       $entityClass
     * @param EntityConfig $config
     * @param array        $context
     *
     * @return array [entityId => [field => value, ...], ...]
     */
    protected function serializeRelatedItemsForSimpleEntity(
        $items,
        $isObject,
        $entityClass,
        EntityConfig $config,
        array $context
    ) {
        $result = [];
        $handler = $config->getPostSerializeHandler();
        $collectionHandler = $config->getPostSerializeCollectionHandler();
        if (null === $collectionHandler) {
            foreach ($items as $item) {
                $serializeItem = $this->serializeItem($isObject ? $item[0] : $item, $entityClass, $config, $context);
                if (null !== $handler) {
                    $serializeItem = $this->serializationHelper->postSerialize($serializeItem, $handler, $context);
                }
                $result[$item['entityId']][] = $serializeItem;
            }
        } else {
            $resultMap = [];
            $serializedItems = [];
            foreach ($items as $key => $item) {
                $serializeItem = $this->serializeItem($isObject ? $item[0] : $item, $entityClass, $config, $context);
                if (null !== $handler) {
                    $serializeItem = $this->serializationHelper->postSerialize($serializeItem, $handler, $context);
                }
                $serializedItems[$key] = $serializeItem;
                $resultMap[$item['entityId']][] = $key;
            }
            $serializedItems = $this->serializationHelper->postSerializeCollection(
                $serializedItems,
                $collectionHandler,
                $context
            );
            foreach ($resultMap as $entityId => $itemsPerEntity) {
                foreach ($itemsPerEntity as $key) {
                    $result[$entityId][] = $serializedItems[$key];
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
        $fields = $this->fieldAccessor->getFields($entityClass, $config);
        foreach ($fields as $field) {
            $propertyPath = $this->getPropertyPath($field, $config->getField($field));
            if ($entityMetadata->isAssociation($propertyPath)) {
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
     * @param string           $fieldName
     * @param FieldConfig|null $fieldConfig
     *
     * @return string
     */
    protected function getPropertyPath($fieldName, FieldConfig $fieldConfig = null)
    {
        if (null === $fieldConfig) {
            return $fieldName;
        }

        return $fieldConfig->getPropertyPath($fieldName);
    }

    /**
     * @param EntityConfig $config
     * @param string       $field
     *
     * @return EntityConfig
     */
    protected function getTargetEntity(EntityConfig $config, $field)
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
     * Check access to a specified field for each entity object from the given $entityIds list
     * and returns ids only for entities for which this access is granted.
     *
     * @param array  $entityIds
     * @param string $entityClass
     * @param string $field
     *
     * @return array
     */
    private function getAccessibleIds(array $entityIds, $entityClass, $field)
    {
        $allowedIds = [];
        foreach ($entityIds as $entityId) {
            $isFieldAllowed = $this->fieldFilter->checkField(
                ['entityId' => $entityId],
                $entityClass,
                $field
            );
            if (EntityAwareFilterInterface::FILTER_NOTHING !== $isFieldAllowed) {
                continue;
            }

            $allowedIds[] = $entityId;
        }

        return $allowedIds;
    }
}
