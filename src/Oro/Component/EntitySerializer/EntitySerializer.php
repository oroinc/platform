<?php

namespace Oro\Component\EntitySerializer;

use Doctrine\Common\Util\ClassUtils;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use Oro\Component\DoctrineUtils\ORM\QueryBuilderUtil;

/**
 * The serializer that loads data for primary and associated entities from the database
 * using the minimum possible number of queries and converts entities to an array
 * bases on a specified configuration.
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
 * * 'disable_partial_load' - Disables using of Doctrine partial objects.
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
 * The top level algorithm of the entity serializer:
 * - load primary entities
 * - iterate through the loaded entities and serialize each entity:
 *     - call value transformer for each field
 *     - serialize each to-one association
 *       and do the following for each association that should be expanded:
 *         - for each to-many association:
 *             - load IDs of target entities
 *             - if target entities should be expanded:
 *                 - load target entities and serialize each target entity
 *             - call "postSerialize" for each target entity
 *             - call "postSerializeCollection" for the collection of target entities
 *         - call "postSerialize" for the target entity
 *         - call "postSerializeCollection" for the target entity
 *     - do nothing for to-many association
 * - for each to-many association:
 *     - load IDs of target entities
 *     - if target entities should be expanded:
 *         - load target entities and serialize each target entity
 *     - call "postSerialize" for each target entity
 *     - call "postSerializeCollection" for the collection of target entities
 * - call "postSerialize" for each primary entity
 * - call "postSerializeCollection" for the collection of primary entities
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

    /** @var FieldFilterInterface */
    protected $fieldFilter;

    /** @var ConfigAccessor */
    private $configAccessor;

    /** @var QueryModifier */
    private $queryModifier;

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
        $this->configAccessor = new ConfigAccessor();
        $this->queryModifier = new QueryModifier(
            $this->doctrineHelper,
            $this->fieldAccessor,
            $this->configAccessor
        );
    }

    public function setFieldFilter(FieldFilterInterface $filter)
    {
        $this->fieldFilter = $filter;
    }

    /**
     * @param QueryBuilder       $qb      A query builder is used to get data
     * @param EntityConfig|array $config  Serialization rules
     * @param array              $context Options post serializers and data transformers have access to
     * @param bool               $skipPostSerializationForPrimaryEntities
     *
     * @return array
     */
    public function serialize(
        QueryBuilder $qb,
        $config,
        array $context = [],
        bool $skipPostSerializationForPrimaryEntities = false
    ): array {
        $entityConfig = $this->normalizeConfig($config);

        $this->queryModifier->updateQuery($qb, $entityConfig);
        $data = $this->getQuery($qb, $entityConfig)->getResult();

        $hasMore = $this->preSerializeItems($data, $entityConfig, $qb->getMaxResults());
        $data = $this->serializeItems(
            (array)$data,
            $this->doctrineHelper->getRootEntityClass($qb),
            $entityConfig,
            $context,
            $skipPostSerializationForPrimaryEntities
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
     * @param bool               $skipPostSerializationForPrimaryEntities
     *
     * @return array
     */
    public function serializeEntities(
        array $entities,
        string $entityClass,
        $config,
        array $context = [],
        bool $skipPostSerializationForPrimaryEntities = false
    ): array {
        $entityConfig = $this->normalizeConfig($config);

        $data = $this->serializeItems(
            $entities,
            $entityClass,
            $entityConfig,
            $context,
            $skipPostSerializationForPrimaryEntities
        );

        return $this->dataNormalizer->normalizeData($data, $entityConfig);
    }

    /**
     * @param QueryBuilder       $qb
     * @param EntityConfig|array $config
     */
    public function prepareQuery(QueryBuilder $qb, $config)
    {
        $this->queryModifier->updateQuery($qb, $this->normalizeConfig($config));
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
            $items = \array_slice($items, 0, $limit);
        }

        return $hasMore;
    }

    /**
     * @param object[]     $entities    The list of entities to be serialized
     * @param string       $entityClass The entity class name
     * @param EntityConfig $config      Serialization rules
     * @param array        $context     Options post serializers and data transformers have access to
     * @param bool         $useIdAsKey  Defines whether the entity id should be used as a key of the result array
     * @param bool         $skipPostSerializationForPrimaryEntities
     *
     * @return array
     */
    protected function serializeItems(
        array $entities,
        $entityClass,
        EntityConfig $config,
        array $context,
        $useIdAsKey = false,
        bool $skipPostSerializationForPrimaryEntities = false
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

        if (!$skipPostSerializationForPrimaryEntities) {
            $result = $this->serializationHelper->processPostSerializeItems($result, $config, $context);
        }

        return $result;
    }

    /**
     * @param mixed        $entity
     * @param string       $entityClass
     * @param EntityConfig $config
     * @param array        $context
     *
     * @return array
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
            $property = $this->configAccessor->getPropertyPath($field, $fieldConfig);
            $path = ConfigUtil::explodePropertyPath($property);
            $isReference = count($path) > 1;

            if (null !== $this->fieldFilter && !$isReference) {
                $fieldCheckResult = $this->fieldFilter->checkField($entity, $entityClass, $property);
                if (null !== $fieldCheckResult) {
                    if (false === $fieldCheckResult) {
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

            $v = null;
            if ($this->tryGetValue($v, $entity, $field, $property, $fieldConfig, $entityMetadata, $config, $context)) {
                $result[$field] = $v;
            } elseif ($this->fieldAccessor->isMetadataProperty($property)) {
                $result[$field] = $this->fieldAccessor->getMetadataProperty($entity, $property, $entityMetadata);
            }
        }

        if (!empty($referenceFields)) {
            $result = $this->serializationHelper->handleFieldsReferencedToChildFields(
                $result,
                $config,
                $context,
                $referenceFields
            );
        }

        return $result;
    }

    /**
     * @param mixed            $value
     * @param object           $entity
     * @param string           $field
     * @param string           $property
     * @param FieldConfig|null $fieldConfig
     * @param EntityMetadata   $entityMetadata
     * @param EntityConfig     $config
     * @param array            $context
     *
     * @return bool
     */
    protected function tryGetValue(
        &$value,
        $entity,
        string $field,
        string $property,
        ?FieldConfig $fieldConfig,
        EntityMetadata $entityMetadata,
        EntityConfig $config,
        array $context
    ): bool {
        if (!$this->dataAccessor->tryGetValue($entity, $property, $value)) {
            return false;
        }

        if ($this->isAssociation($property, $entityMetadata, $fieldConfig)) {
            if (\is_object($value)) {
                $targetConfig = $this->configAccessor->getTargetEntity($config, $field);
                $targetEntityClass = $entityMetadata->isAssociation($property)
                    ? $entityMetadata->getAssociationTargetClass($property)
                    : ClassUtils::getClass($value);

                $value = $this->serializeItem($value, $targetEntityClass, $targetConfig, $context);
                if (null === $this->getIdFieldNameIfIdOnlyRequested($targetConfig, $targetEntityClass)) {
                    $targetEntityId = $this->dataAccessor->getValue(
                        $value,
                        $this->fieldAccessor->getIdField($targetEntityClass, $targetConfig)
                    );
                    $this->loadRelatedDataForOneEntity(
                        $value,
                        $targetEntityClass,
                        $targetEntityId,
                        $targetConfig,
                        $context
                    );
                }
            }
        } elseif (null !== $value) {
            $value = $this->serializationHelper->transformValue($value, $context, $fieldConfig);
        }

        return true;
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
            || (null !== $fieldConfig && null !== $fieldConfig->getTargetEntity());
    }

    /**
     * @param QueryBuilder $qb
     * @param EntityConfig $config
     *
     * @return Query
     */
    protected function getQuery(QueryBuilder $qb, EntityConfig $config)
    {
        $query = $this->queryFactory->getQuery($qb, $config);
        if ($config->isPartialLoadEnabled()) {
            if (!$query->hasHint(Query::HINT_FORCE_PARTIAL_LOAD)) {
                $query->setHint(Query::HINT_FORCE_PARTIAL_LOAD, true);
            } elseif (false === $query->getHint(Query::HINT_FORCE_PARTIAL_LOAD)) {
                /**
                 * Doctrine considers any value except NULL as enabled HINT_FORCE_PARTIAL_LOAD hint
                 * @see \Doctrine\ORM\UnitOfWork::createEntity
                 */
                $query->setHint(Query::HINT_FORCE_PARTIAL_LOAD, null);
            }
        }

        return $query;
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
            $relatedValue = null;
            $associationQuery = $this->configAccessor->getAssociationQuery($config, $field);
            if (null !== $associationQuery) {
                $relatedValue = $this->loadRelatedCollectionValuedAssociationDataForCustomAssociation(
                    $config,
                    $associationQuery,
                    $entityClass,
                    $field,
                    $entityIds,
                    $context
                );
            } else {
                $propertyPath = $this->configAccessor->getPropertyPath($field, $config->getField($field));
                if ($entityMetadata->isCollectionValuedAssociation($propertyPath)) {
                    $accessibleIds = $this->getAccessibleIds($entityIds, $entityClass, $propertyPath);
                    if (!empty($accessibleIds)) {
                        $relatedValue = $this->loadRelatedCollectionValuedAssociationData(
                            $config,
                            $entityMetadata->getAssociationMapping($propertyPath),
                            $field,
                            $accessibleIds,
                            $context
                        );
                    }
                }
            }
            if (null !== $relatedValue) {
                $relatedData[$field] = $relatedValue;
            }
        }
        if (!empty($relatedData)) {
            $this->applyRelatedData($result, $entityClass, $config, $relatedData);
        }
    }

    /**
     * @param EntityConfig $config
     * @param array        $associationMapping
     * @param string       $field
     * @param array        $entityIds
     * @param array        $context
     *
     * @return array|null
     */
    protected function loadRelatedCollectionValuedAssociationData(
        EntityConfig $config,
        array $associationMapping,
        $field,
        $entityIds,
        array $context
    ) {
        $targetEntityClass = $associationMapping['targetEntity'];
        $targetConfig = $this->configAccessor->getTargetEntity($config, $field);

        if ($this->isSingleStepLoading($targetEntityClass, $targetConfig)) {
            return $this->loadRelatedItemsForSimpleEntity(
                $this->queryFactory->getToManyAssociationQueryBuilder($associationMapping, $entityIds),
                $targetEntityClass,
                $targetConfig,
                $context
            );
        }

        return $this->loadRelatedItems(
            $this->queryFactory->getRelatedItemsIds(
                $this->queryFactory->getNotInitializedToManyAssociationQueryBuilder($associationMapping),
                $associationMapping['sourceEntity'],
                $targetEntityClass,
                $entityIds,
                $targetConfig
            ),
            $targetEntityClass,
            $targetConfig,
            $context
        );
    }

    /**
     * @param EntityConfig     $config
     * @param AssociationQuery $associationQuery
     * @param string           $entityClass
     * @param string           $field
     * @param array            $entityIds
     * @param array            $context
     *
     * @return array|null
     */
    protected function loadRelatedCollectionValuedAssociationDataForCustomAssociation(
        EntityConfig $config,
        AssociationQuery $associationQuery,
        $entityClass,
        $field,
        $entityIds,
        array $context
    ) {
        $targetEntityClass = $associationQuery->getTargetEntityClass();
        $targetConfig = $this->configAccessor->getTargetEntity($config, $field);

        if ($this->isSingleStepLoading($targetEntityClass, $targetConfig)) {
            $dataQb = clone $associationQuery->getQueryBuilder();
            $this->queryFactory->initializeToManyAssociationQueryBuilder($dataQb, $entityClass, $entityIds);

            return $this->loadRelatedItemsForSimpleEntity(
                $dataQb,
                $targetEntityClass,
                $targetConfig,
                $context
            );
        }

        return $this->loadRelatedItems(
            $this->queryFactory->getRelatedItemsIds(
                clone $associationQuery->getQueryBuilder(),
                $entityClass,
                $targetEntityClass,
                $entityIds,
                $targetConfig
            ),
            $targetEntityClass,
            $targetConfig,
            $context
        );
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
        $items = $this->serializationHelper->processPostSerializeItems($items, $config, $context);
        $entity = reset($items);
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
                throw new \RuntimeException(sprintf(
                    'The result item does not contain the entity identifier. Entity: %s.',
                    $entityClass
                ));
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
     * @param array        $relatedItemsIds [['entityId' => mixed, 'relatedEntityId' => mixed], ...]
     * @param string       $entityClass
     * @param EntityConfig $config
     * @param array        $context
     *
     * @return array [entityId => [field => value, ...], ...]
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    protected function loadRelatedItems($relatedItemsIds, $entityClass, EntityConfig $config, array $context)
    {
        $limit = $config->getMaxResults();
        $bindings = $this->getRelatedItemsBindings($relatedItemsIds, $entityClass);

        $items = [];
        $resultFieldName = $this->getIdFieldNameIfIdOnlyRequested($config, $entityClass);
        $relatedItemIds = $this->getRelatedItemsIds($bindings, $limit);
        if (null !== $resultFieldName) {
            foreach ($relatedItemIds as $relatedItemId) {
                $items[$relatedItemId] = [$resultFieldName => $relatedItemId];
            }
            $items = $this->serializationHelper->processPostSerializeItems($items, $config, $context);
        } else {
            $qb = $this->queryFactory->getRelatedItemsQueryBuilder($entityClass, $relatedItemIds);
            $this->queryModifier->updateQuery($qb, $config);
            $data = $this->getQuery($qb, $config)->getResult();
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
     * @param array  $relatedItemsIds [['entityId' => mixed, 'relatedEntityId' => mixed], ...]
     * @param string $entityClass
     *
     * @return array [entityId => [relatedEntityId, ...], ...]
     */
    protected function getRelatedItemsBindings($relatedItemsIds, $entityClass)
    {
        $result = [];
        if (!empty($relatedItemsIds)) {
            $relatedEntityIdType = $this->doctrineHelper->getEntityIdType($entityClass);
            foreach ($relatedItemsIds as $row) {
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
     * @param QueryBuilder $qb
     * @param string       $entityClass
     * @param EntityConfig $config
     * @param array        $context
     *
     * @return array [entityId => [field => value, ...], ...]
     */
    protected function loadRelatedItemsForSimpleEntity(
        QueryBuilder $qb,
        $entityClass,
        EntityConfig $config,
        array $context
    ) {
        $orderBy = $config->getOrderBy();
        foreach ($orderBy as $field => $direction) {
            $qb->addOrderBy(QueryBuilderUtil::getField('r', $field), QueryBuilderUtil::getSortOrder($direction));
        }

        $targetEntityMetadata = $this->doctrineHelper->getEntityMetadata($entityClass);
        $isObject = false;
        if ($targetEntityMetadata->hasInheritance()) {
            $isObject = true;
            $this->queryModifier->updateSelectQueryPart($qb, 'r', $entityClass, $config);
            $items = $this->getQuery($qb, $config)->getResult();
        } else {
            $fields = $this->fieldAccessor->getFieldsToSelect($entityClass, $config);
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
        $resultMap = [];
        $serializedItems = [];
        foreach ($items as $key => $item) {
            $serializedItems[$key] = $this->serializeItem(
                $isObject ? $item[0] : $item,
                $entityClass,
                $config,
                $context
            );
            $resultMap[$item['entityId']][] = $key;
        }
        $serializedItems = $this->serializationHelper->processPostSerializeItems(
            $serializedItems,
            $config,
            $context
        );

        $result = [];
        foreach ($resultMap as $entityId => $itemsPerEntity) {
            foreach ($itemsPerEntity as $key) {
                $result[$entityId][] = $serializedItems[$key];
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
            $propertyPath = $this->configAccessor->getPropertyPath($field, $config->getField($field));
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
        if (Types::INTEGER === $type || Types::SMALLINT === $type) {
            $value = (int)$value;
        }

        return $value;
    }

    /**
     * Check access to a specified field for each entity object from the given $entityIds list
     * and returns ids only for entities for which the access is granted.
     *
     * @param array  $entityIds
     * @param string $entityClass
     * @param string $field
     *
     * @return array
     */
    private function getAccessibleIds(array $entityIds, $entityClass, $field)
    {
        if (null === $this->fieldFilter) {
            return $entityIds;
        }

        $accessibleIds = [];
        $em = $this->doctrineHelper->getEntityManager($entityClass);
        foreach ($entityIds as $entityId) {
            $fieldCheckResult = $this->fieldFilter->checkField(
                $em->getReference($entityClass, $entityId),
                $entityClass,
                $field
            );
            if (null === $fieldCheckResult) {
                $accessibleIds[] = $entityId;
            }
        }

        return $accessibleIds;
    }
}
