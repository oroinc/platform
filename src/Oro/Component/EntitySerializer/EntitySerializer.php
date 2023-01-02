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
    protected DoctrineHelper $doctrineHelper;
    protected SerializationHelper $serializationHelper;
    protected DataAccessorInterface $dataAccessor;
    protected QueryFactory $queryFactory;
    protected FieldAccessor $fieldAccessor;
    protected ConfigNormalizer $configNormalizer;
    protected ConfigConverter $configConverter;
    private DataNormalizer $dataNormalizer;
    private ConfigAccessor $configAccessor;
    private QueryModifier $queryModifier;
    private ?FieldFilterInterface $fieldFilter = null;

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

    public function setFieldFilter(FieldFilterInterface $filter): void
    {
        $this->fieldFilter = $filter;
    }

    public function serialize(
        QueryBuilder $qb,
        EntityConfig|array $config,
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

    public function serializeEntities(
        array $entities,
        string $entityClass,
        EntityConfig|array $config,
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

    public function prepareQuery(QueryBuilder $qb, EntityConfig|array $config): void
    {
        $this->queryModifier->updateQuery($qb, $this->normalizeConfig($config));
    }

    protected function normalizeConfig(EntityConfig|array $config): EntityConfig
    {
        if ($config instanceof EntityConfig) {
            $config = $config->toArray();
        }

        return $this->configConverter->convertConfig(
            $this->configNormalizer->normalizeConfig($config)
        );
    }

    protected function preSerializeItems(array &$items, EntityConfig $config, ?int $limit): bool
    {
        $hasMore = false;
        if (null !== $limit && $config->getHasMore() && \count($items) > $limit) {
            $hasMore = true;
            $items = \array_slice($items, 0, $limit);
        }

        return $hasMore;
    }

    protected function serializeItems(
        array $entities,
        string $entityClass,
        EntityConfig $config,
        array $context,
        bool $useIdAsKey = false,
        bool $skipPostSerializationForPrimaryEntities = false
    ): array {
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

        $entityIds = $this->getEntityIds($entities, $idFieldName);
        $this->loadRelatedData($result, $entityClass, $entityIds, $config, $context);

        if (!$skipPostSerializationForPrimaryEntities) {
            $result = $this->serializationHelper->processPostSerializeItems($result, $config, $context);
        }

        return $result;
    }

    /**
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    protected function serializeItem(mixed $entity, string $entityClass, EntityConfig $config, array $context): array
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
            $isReference = \count($path) > 1;

            if (null !== $this->fieldFilter && !$isReference) {
                $fieldCheckResult = \is_object($entity)
                    ? $this->fieldFilter->checkField($entity, $entityClass, $property)
                    : null;
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

    protected function tryGetValue(
        mixed &$value,
        object|array $entity,
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

    private function isAssociation(
        string $fieldName,
        EntityMetadata $entityMetadata,
        FieldConfig $fieldConfig = null
    ): bool {
        return
            (ConfigUtil::IGNORE_PROPERTY_PATH !== $fieldName && $entityMetadata->isAssociation($fieldName))
            || (null !== $fieldConfig && null !== $fieldConfig->getTargetEntity());
    }

    protected function getQuery(QueryBuilder $qb, EntityConfig $config): Query
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
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    protected function loadRelatedData(
        array &$result,
        string $entityClass,
        array $entityIds,
        EntityConfig $config,
        array $context
    ): void {
        $relatedData = [];
        $toOneRelatedData = [];
        $entityMetadata = $this->doctrineHelper->getEntityMetadata($entityClass);
        $fields = $this->fieldAccessor->getFields($entityClass, $config);
        foreach ($fields as $field) {
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
                if (null !== $relatedValue) {
                    if ($associationQuery->isCollection()) {
                        $relatedData[$field] = $relatedValue;
                    } else {
                        $toOneRelatedData[$field] = $relatedValue;
                    }
                }
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
                        if (null !== $relatedValue) {
                            $relatedData[$field] = $relatedValue;
                        }
                    }
                }
            }
        }
        if (!empty($relatedData)) {
            $this->applyRelatedData($result, $entityClass, $config, $relatedData);
        }
        if (!empty($toOneRelatedData)) {
            $this->applyToOneRelatedData($result, $entityClass, $config, $toOneRelatedData);
        }
    }

    protected function loadRelatedCollectionValuedAssociationData(
        EntityConfig $config,
        array $associationMapping,
        string $field,
        array $entityIds,
        array $context
    ): ?array {
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
            $this->loadRelatedItemsIds(
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

    protected function loadRelatedCollectionValuedAssociationDataForCustomAssociation(
        EntityConfig $config,
        AssociationQuery $associationQuery,
        string $entityClass,
        string $field,
        array $entityIds,
        array $context
    ): ?array {
        $targetEntityClass = $associationQuery->getTargetEntityClass();
        $targetConfig = $this->configAccessor->getTargetEntity($config, $field);

        if (!$associationQuery->isCollection() || $this->isSingleStepLoading($targetEntityClass, $targetConfig)) {
            $dataQb = clone $associationQuery->getQueryBuilder();
            $this->queryFactory->initializeAssociationQueryBuilder(
                $dataQb,
                $entityClass,
                $entityIds,
                !$associationQuery->isCollection()
            );

            return $this->loadRelatedItemsForSimpleEntity($dataQb, $targetEntityClass, $targetConfig, $context);
        }

        return $this->loadRelatedItems(
            $this->loadRelatedItemsIds(
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

    protected function loadRelatedDataForOneEntity(
        mixed &$entity,
        string $entityClass,
        mixed $entityId,
        EntityConfig $config,
        array $context
    ): void {
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
     */
    private function applyRelatedData(
        array &$result,
        string $entityClass,
        EntityConfig $config,
        array $relatedData
    ): void {
        $entityIdFieldName = $this->fieldAccessor->getIdField($entityClass, $config);
        foreach ($result as &$resultItem) {
            if (!\array_key_exists($entityIdFieldName, $resultItem)) {
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
     * @param array        $result
     * @param string       $entityClass
     * @param EntityConfig $config
     * @param array        $relatedData [field => [entityId => [field => value, ...], ...], ...]
     */
    private function applyToOneRelatedData(
        array &$result,
        string $entityClass,
        EntityConfig $config,
        array $relatedData
    ): void {
        $entityIdFieldName = $this->fieldAccessor->getIdField($entityClass, $config);
        foreach ($result as &$resultItem) {
            if (!\array_key_exists($entityIdFieldName, $resultItem)) {
                throw new \RuntimeException(sprintf(
                    'The result item does not contain the entity identifier. Entity: %s.',
                    $entityClass
                ));
            }
            $entityId = $resultItem[$entityIdFieldName];
            foreach ($relatedData as $field => $relatedItems) {
                $resultItem[$field] = !empty($relatedItems[$entityId])
                    ? reset($relatedItems[$entityId])
                    : null;
            }
        }
    }

    private function isSingleStepLoading(string $entityClass, EntityConfig $config): bool
    {
        return
            null === $config->getMaxResults()
            && !$this->hasAssociations($entityClass, $config);
    }

    /**
     * @param QueryBuilder $qb
     * @param string       $entityClass
     * @param string       $targetEntityClass
     * @param array        $entityIds
     * @param EntityConfig $config
     *
     * @return array [['entityId' => mixed, 'relatedEntityId' => mixed], ...]
     */
    protected function loadRelatedItemsIds(
        QueryBuilder $qb,
        string $entityClass,
        string $targetEntityClass,
        array $entityIds,
        EntityConfig $config
    ): array {
        $orderBy = $config->getOrderBy();
        foreach ($orderBy as $field => $direction) {
            $qb->addOrderBy(QueryBuilderUtil::getField('r', $field), QueryBuilderUtil::getSortOrder($direction));
        }

        return $this->queryFactory->getRelatedItemsIds($qb, $entityClass, $targetEntityClass, $entityIds, $config);
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
    protected function loadRelatedItems(
        array $relatedItemsIds,
        string $entityClass,
        EntityConfig $config,
        array $context
    ): array {
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
                    && isset($result[$entityId])
                    && $config->getHasMore()
                    && \count($result[$entityId]) === $limit
                    && \count($relatedEntityIds) > $limit
                ) {
                    $result[$entityId][ConfigUtil::INFO_RECORD_KEY] = [ConfigUtil::HAS_MORE => true];
                }
            }
        }

        return $result;
    }

    private function getIdFieldNameIfIdOnlyRequested(EntityConfig $config, string $entityClass): ?string
    {
        if (!$config->isExcludeAll()) {
            return null;
        }
        $fields = $config->getFields();
        if (\count($fields) !== 1) {
            return null;
        }
        reset($fields);
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
    private function getRelatedItemsBindings(array $relatedItemsIds, string $entityClass): array
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

    private function getRelatedItemsIds(array $bindings, ?int $limit): array
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
        string $entityClass,
        EntityConfig $config,
        array $context
    ): array {
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
        array $items,
        bool $isObject,
        string $entityClass,
        EntityConfig $config,
        array $context
    ): array {
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

    private function hasAssociations(string $entityClass, EntityConfig $config): bool
    {
        $entityMetadata = $this->doctrineHelper->getEntityMetadata($entityClass);
        $fields = $this->fieldAccessor->getFields($entityClass, $config);
        foreach ($fields as $field) {
            $fieldConfig = $config->getField($field);
            $property = $this->configAccessor->getPropertyPath($field, $fieldConfig);
            if ($this->isAssociation($property, $entityMetadata, $fieldConfig)) {
                return true;
            }
        }

        return false;
    }

    private function getEntityIds(array $entities, string $idFieldName): array
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

    private function getTypedEntityId(mixed $value, string $type): mixed
    {
        if (Types::INTEGER === $type || Types::SMALLINT === $type) {
            $value = (int)$value;
        }

        return $value;
    }

    /**
     * Check access to a specified field for each entity object from the given $entityIds list
     * and returns ids only for entities for which the access is granted.
     */
    private function getAccessibleIds(array $entityIds, string $entityClass, string $field): array
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
