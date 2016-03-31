<?php

namespace Oro\Component\EntitySerializer;

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
    }

    /**
     * @param QueryBuilder $qb     A query builder is used to get data
     * @param array        $config Serialization rules
     *
     * @return array
     */
    public function serialize(QueryBuilder $qb, $config)
    {
        $config = $this->configNormalizer->normalizeConfig($config);

        $this->updateQuery($qb, $config);
        $data = $this->queryFactory->getQuery($qb, $config)->getResult();
        $data = $this->serializeItems((array)$data, $this->doctrineHelper->getRootEntityClass($qb), $config);

        return $this->dataNormalizer->normalizeData($data, $config);
    }

    /**
     * @param object[] $entities    The list of entities to be serialized
     * @param string   $entityClass The entity class name
     * @param array    $config      Serialization rules
     *
     * @return array
     */
    public function serializeEntities(array $entities, $entityClass, $config)
    {
        $config = $this->configNormalizer->normalizeConfig($config);
        $data   = $this->serializeItems($entities, $entityClass, $config);

        return $this->dataNormalizer->normalizeData($data, $config);
    }

    /**
     * @param QueryBuilder $qb
     * @param array        $config
     */
    public function prepareQuery(QueryBuilder $qb, $config)
    {
        $this->updateQuery($qb, $this->configNormalizer->normalizeConfig($config));
    }

    /**
     * @param object[] $entities    The list of entities to be serialized
     * @param string   $entityClass The entity class name
     * @param array    $config      Serialization rules
     * @param bool     $useIdAsKey  Defines whether the entity id should be used as a key of the result array
     *
     * @return array
     */
    protected function serializeItems(array $entities, $entityClass, $config, $useIdAsKey = false)
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

        if (isset($config[ConfigUtil::POST_SERIALIZE])) {
            $callback = $config[ConfigUtil::POST_SERIALIZE];
            foreach ($result as &$resultItem) {
                $resultItem = $this->postSerialize($resultItem, $callback);
            }
        }

        return $result;
    }

    /**
     * @param mixed  $entity
     * @param string $entityClass
     * @param array  $config
     *
     * @return array
     */
    protected function serializeItem($entity, $entityClass, $config)
    {
        if (!$entity) {
            return [];
        }

        $result         = [];
        $entityMetadata = $this->doctrineHelper->getEntityMetadata($entityClass);
        $resultFields   = $this->fieldAccessor->getFieldsToSerialize($entityClass, $config);
        foreach ($resultFields as $field) {
            $value = null;
            if ($this->dataAccessor->tryGetValue($entity, $field, $value)) {
                $targetConfig = ConfigUtil::getFieldConfig($config, $field);
                if ($entityMetadata->isAssociation($field)) {
                    if ($value !== null) {
                        if (!empty($targetConfig[ConfigUtil::FIELDS])) {
                            $targetEntityClass = $entityMetadata->getAssociationTargetClass($field);
                            $targetEntityId    = $this->dataAccessor->getValue(
                                $value,
                                $this->doctrineHelper->getEntityIdFieldName($targetEntityClass)
                            );

                            $value = $this->serializeItem($value, $targetEntityClass, $targetConfig);
                            $items = [$value];
                            $this->loadRelatedData($items, $targetEntityClass, [$targetEntityId], $targetConfig);
                            $value = reset($items);
                            if (isset($targetConfig[ConfigUtil::POST_SERIALIZE])) {
                                $value = $this->postSerialize($value, $targetConfig[ConfigUtil::POST_SERIALIZE]);
                            }
                        } else {
                            $value = $this->dataTransformer->transform(
                                $entityClass,
                                $field,
                                $value,
                                $targetConfig
                            );
                        }
                    }
                } else {
                    $value = $this->dataTransformer->transform(
                        $entityClass,
                        $field,
                        $value,
                        $targetConfig
                    );
                }
                $result[$field] = $value;
            } elseif ($this->fieldAccessor->isMetadataProperty($field)) {
                $result[$field] = $this->fieldAccessor->getMetadataProperty($entity, $field, $entityMetadata);
            }
        }

        return $result;
    }

    /**
     * @param QueryBuilder $qb
     * @param array        $config
     */
    protected function updateQuery(QueryBuilder $qb, $config)
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
                ConfigUtil::getFieldConfig($config, $field),
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
     * @param array        $config
     * @param bool         $withAssociations
     */
    protected function updateSelectQueryPart(QueryBuilder $qb, $alias, $entityClass, $config, $withAssociations = false)
    {
        if (ConfigUtil::isPartialAllowed($config)) {
            $fields = $this->fieldAccessor->getFieldsToSelect($entityClass, $config, $withAssociations);
            $qb->addSelect(sprintf('partial %s.{%s}', $alias, implode(',', $fields)));
        } else {
            $qb->addSelect($alias);
        }
    }

    /**
     * @param array  $result
     * @param string $entityClass
     * @param array  $entityIds
     * @param array  $config
     */
    protected function loadRelatedData(array &$result, $entityClass, $entityIds, $config)
    {
        $relatedData    = [];
        $entityMetadata = $this->doctrineHelper->getEntityMetadata($entityClass);
        $fields         = $this->fieldAccessor->getFields($entityClass, $config);
        foreach ($fields as $field) {
            if (!$entityMetadata->isCollectionValuedAssociation($field)) {
                continue;
            }

            $mapping      = $entityMetadata->getAssociationMapping($field);
            $targetConfig = ConfigUtil::getFieldConfig($config, $field);

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
     * @param string $entityClass
     * @param array  $config
     *
     * @return bool
     */
    protected function isSingleStepLoading($entityClass, $config)
    {
        return
            (!isset($config[ConfigUtil::MAX_RESULTS]) || $config[ConfigUtil::MAX_RESULTS] < 0)
            && !$this->hasAssociations($entityClass, $config);
    }

    /**
     * @param array $entityIds
     * @param array $mapping
     * @param array $config
     *
     * @return array [entityId => [field => value, ...], ...]
     */
    protected function loadRelatedItems($entityIds, $mapping, $config)
    {
        $entityClass = $mapping['targetEntity'];
        $bindings    = $this->getRelatedItemsBindings($entityIds, $mapping, $config);
        $qb          = $this->queryFactory->getRelatedItemsQueryBuilder(
            $entityClass,
            $this->getRelatedItemsIds($bindings)
        );
        $this->updateQuery($qb, $config);
        $data = $this->queryFactory->getQuery($qb, $config)->getResult();

        $result = [];
        if (!empty($data)) {
            $items = $this->serializeItems((array)$data, $entityClass, $config, true);
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
     * @param array $entityIds
     * @param array $mapping
     * @param array $config
     *
     * @return array [entityId => relatedEntityId, ...]
     */
    protected function getRelatedItemsBindings($entityIds, $mapping, $config)
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
     * @param array $entityIds
     * @param array $mapping
     * @param array $config
     *
     * @return array [entityId => [field => value, ...], ...]
     */
    protected function loadRelatedItemsForSimpleEntity($entityIds, $mapping, $config)
    {
        $qb = $this->queryFactory->getToManyAssociationQueryBuilder($mapping, $entityIds);
        foreach (ConfigUtil::getArrayValue($config, ConfigUtil::ORDER_BY) as $field => $order) {
            $qb->addOrderBy(sprintf('r.%s', $field), $order);
        }
        $fields = $this->fieldAccessor->getFieldsToSerialize($mapping['targetEntity'], $config);
        foreach ($fields as $field) {
            $qb->addSelect(sprintf('r.%s', $field));
        }

        $items = $this->queryFactory->getQuery($qb, $config)->getArrayResult();

        $result      = [];
        $entityClass = $mapping['targetEntity'];
        if (isset($config[ConfigUtil::POST_SERIALIZE])) {
            $callback = $config[ConfigUtil::POST_SERIALIZE];
            foreach ($items as $item) {
                $result[$item['entityId']][] = $this->postSerialize(
                    $this->serializeItem($item, $entityClass, $config),
                    $callback
                );
            }
        } else {
            foreach ($items as $item) {
                $result[$item['entityId']][] = $this->serializeItem($item, $entityClass, $config);
            }
        }

        return $result;
    }

    /**
     * @param string $entityClass
     * @param array  $config
     *
     * @return bool
     */
    protected function hasAssociations($entityClass, $config)
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
     * @param array    $item
     * @param callable $callback
     *
     * @return array
     */
    protected function postSerialize(array $item, $callback)
    {
        // @deprecated since 1.9. New signature of 'post_serialize' callback is function (array $item) : array
        // Old signature was function (array &$item) : void
        // The following implementation supports both new and old signature of the callback
        // Remove this implementation when a support of old signature will not be required
        if ($callback instanceof \Closure) {
            $handleResult = $callback($item);
            if (null !== $handleResult) {
                $item = $handleResult;
            }
        } else {
            $item = call_user_func($callback, $item);
        }

        /* New implementation, uncomment it when a support of old signature will not be required
        $item = call_user_func($callback, $item);
        */

        return $item;
    }
}
