<?php

namespace Oro\Bundle\SoapBundle\Serializer;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\QueryBuilder;

/**
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

    /**
     * @param ManagerRegistry          $doctrine
     * @param DataAccessorInterface    $dataAccessor
     * @param DataTransformerInterface $dataTransformer
     */
    public function __construct(
        ManagerRegistry $doctrine,
        DataAccessorInterface $dataAccessor,
        DataTransformerInterface $dataTransformer
    ) {
        $this->doctrineHelper  = new DoctrineHelper($doctrine);
        $this->dataAccessor    = $dataAccessor;
        $this->dataTransformer = $dataTransformer;
    }

    /**
     * @param QueryBuilder $qb     A query builder is used to get data
     * @param array        $config Serialization rules
     *
     * @return array
     */
    public function serialize(QueryBuilder $qb, $config)
    {
        $this->prepareQuery($qb, $config);
        $data = $qb->getQuery()->getResult();

        return $this->serializeEntities((array)$data, $this->doctrineHelper->getRootEntityClass($qb), $config);
    }

    /**
     * @param object[] $entities
     * @param string   $entityClass
     * @param array    $config
     * @param boolean  $useIdAsKey
     *
     * @return array
     */
    public function serializeEntities(array $entities, $entityClass, $config, $useIdAsKey = false)
    {
        $result = [];
        if (!empty($entities)) {
            $getIdMethodName = $useIdAsKey
                ? $this->getEntityIdGetter($entityClass)
                : null;
            foreach ($entities as $entity) {
                $item = $this->serializeItem($entity, $entityClass, $config);
                if ($getIdMethodName) {
                    $result[$entity->$getIdMethodName()] = $item;
                } else {
                    $result[] = $item;
                }
            }
            $relatedData = $this->loadRelatedData(
                $entityClass,
                $this->getEntityIds($entities, $entityClass),
                $config
            );
            $this->applyRelatedData($result, $entityClass, $relatedData, $config);

            if (isset($config['post_serialize'])) {
                $postSerialize = $config['post_serialize'];
                foreach ($result as &$resultItem) {
                    $postSerialize($resultItem);
                }
            }
        }

        return $result;
    }

    /**
     * @param QueryBuilder $qb
     * @param array        $config
     */
    public function prepareQuery(QueryBuilder $qb, $config)
    {
        $rootAlias      = $this->doctrineHelper->getRootAlias($qb);
        $entityClass    = $this->doctrineHelper->getRootEntityClass($qb);
        $entityMetadata = $this->doctrineHelper->getEntityMetadata($entityClass);

        $qb->resetDQLPart('select');
        $this->updateSelectQueryPart($qb, $rootAlias, $entityClass, $config);

        $aliasCounter = 0;
        $fields       = $this->getFields($entityClass, $config);
        foreach ($fields as $field) {
            if (!$entityMetadata->isAssociation($field) || $entityMetadata->isCollectionValuedAssociation($field)) {
                continue;
            }

            $targetConfig = isset($config['fields'][$field])
                ? $config['fields'][$field]
                : [];

            $alias = 'a' . ++$aliasCounter;
            $qb->leftJoin(sprintf('%s.%s', $rootAlias, $field), $alias);
            $this->updateSelectQueryPart(
                $qb,
                $alias,
                $entityMetadata->getAssociationTargetClass($field),
                $targetConfig,
                true
            );
        }
    }

    /**
     * @param object $entity
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
        $resultFields   = $this->getFieldsToSerialize($entityClass, $config);
        foreach ($resultFields as $field) {
            $value = null;
            if ($this->dataAccessor->tryGetValue($entity, $field, $value)) {
                if ($entityMetadata->isAssociation($field)) {
                    if ($value !== null) {
                        $targetConfig = isset($config['fields'][$field])
                            ? $config['fields'][$field]
                            : [];
                        if (!empty($targetConfig['fields']) && is_string($targetConfig['fields'])) {
                            $value = $this->dataAccessor->getValue($value, $targetConfig['fields']);
                            $value = $this->dataTransformer->transformValue($value);
                        } elseif ($this->isExcludeAll($targetConfig)) {
                            $value = $this->serializeItem(
                                $value,
                                $entityMetadata->getAssociationTargetClass($field),
                                $targetConfig
                            );
                        } else {
                            $value = $this->dataTransformer->transformValue($value);
                        }
                    }
                } else {
                    $value = $this->dataTransformer->transformValue($value);
                }
                $result[$field] = $value;
            }
        }

        return $result;
    }

    /**
     * @param QueryBuilder $qb
     * @param string       $alias
     * @param string       $entityClass
     * @param array        $config
     * @param boolean      $withAssociations
     */
    public function updateSelectQueryPart(QueryBuilder $qb, $alias, $entityClass, $config, $withAssociations = false)
    {
        $entityMetadata = $this->doctrineHelper->getEntityMetadata($entityClass);
        $fields         = array_filter(
            $this->getFields($entityClass, $config),
            function ($field) use ($entityMetadata, $withAssociations) {
                return $withAssociations
                    ? !$entityMetadata->isCollectionValuedAssociation($field)
                    : !$entityMetadata->isAssociation($field);
            }
        );
        // make sure identifier fields are added
        foreach ($this->getEntityIdFieldNames($entityClass) as $field) {
            if (!in_array($field, $fields)) {
                $fields[] = $field;
            }
        }
        $qb->addSelect(sprintf('partial %s.{%s}', $alias, implode(',', $fields)));
    }

    /**
     * @param array $entityIds
     * @param array $mapping
     *
     * @return QueryBuilder
     */
    protected function getToManyAssociationQueryBuilder($entityIds, $mapping)
    {
        $entityIdField = $this->getEntityIdFieldName($mapping['sourceEntity']);

        $qb = $this->doctrineHelper->getEntityRepository($mapping['targetEntity'])
            ->createQueryBuilder('r')
            ->select(sprintf('e.%s as entityId', $entityIdField))
            ->where(sprintf('e.%s IN (:ids)', $entityIdField))
            ->setParameter('ids', $entityIds);
        if ($mapping['mappedBy'] && $mapping['type'] === ClassMetadata::ONE_TO_MANY) {
            $qb->innerJoin($mapping['sourceEntity'], 'e', 'WITH', sprintf('r.%s = e', $mapping['mappedBy']));
        } else {
            $qb->innerJoin($mapping['sourceEntity'], 'e', 'WITH', sprintf('r MEMBER OF e.%s', $mapping['fieldName']));
        }

        return $qb;
    }

    /**
     * @param array $entityIds
     * @param array $mapping
     *
     * @return array
     */
    protected function getRelatedItemsBindings($entityIds, $mapping)
    {
        $qb = $this->getToManyAssociationQueryBuilder($entityIds, $mapping)
            ->addSelect(sprintf('r.%s as relatedEntityId', $this->getEntityIdFieldName($mapping['targetEntity'])));

        $rows = $qb->getQuery()->getScalarResult();

        $result = [];
        foreach ($rows as $row) {
            $result[$row['entityId']][] = $row['relatedEntityId'];
        }

        return $result;
    }

    /**
     * @param array $bindings
     *
     * @return array
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
     * @param string $entityClass
     * @param array  $entityIds
     * @param array  $config
     *
     * @return array
     */
    protected function loadRelatedData($entityClass, $entityIds, $config)
    {
        $relatedData    = [];
        $entityMetadata = $this->doctrineHelper->getEntityMetadata($entityClass);
        $fields         = $this->getFields($entityClass, $config);
        foreach ($fields as $field) {
            if (!$entityMetadata->isCollectionValuedAssociation($field)) {
                continue;
            }

            $mapping      = $entityMetadata->getAssociationMapping($field);
            $targetConfig = isset($config['fields'][$field])
                ? $config['fields'][$field]
                : [];

            $relatedData[$field] = $this->hasAssociations($mapping['targetEntity'], $targetConfig)
                ? $this->loadRelatedItems($entityIds, $mapping, $targetConfig)
                : $this->loadRelatedItemsForSimpleEntity($entityIds, $mapping, $targetConfig);
        }

        return $relatedData;
    }

    /**
     * @param array $entityIds
     * @param array $mapping
     * @param array $config
     *
     * @return array
     */
    protected function loadRelatedItems($entityIds, $mapping, $config)
    {
        $entityClass = $mapping['targetEntity'];
        $bindings    = $this->getRelatedItemsBindings($entityIds, $mapping);
        $qb          = $this->doctrineHelper->getEntityRepository($entityClass)
            ->createQueryBuilder('r')
            ->where(sprintf('r.%s IN (:ids)', $this->getEntityIdFieldName($entityClass)))
            ->setParameter('ids', $this->getRelatedItemsIds($bindings));
        $this->prepareQuery($qb, $config);
        $data = $qb->getQuery()->getResult();

        $result = [];
        if (!empty($data)) {
            $items = $this->serializeEntities((array)$data, $entityClass, $config, true);
            foreach ($bindings as $entityId => $relatedEntityIds) {
                foreach ($relatedEntityIds as $relatedEntityId) {
                    $result[$entityId][] = $items[$relatedEntityId];
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
     * @return array
     */
    protected function loadRelatedItemsForSimpleEntity($entityIds, $mapping, $config)
    {
        $qb = $this->getToManyAssociationQueryBuilder($entityIds, $mapping);
        if (!empty($config['orderBy'])) {
            foreach ($config['orderBy'] as $field => $order) {
                $qb->addOrderBy(sprintf('r.%s', $field), $order);
            }
        }
        $fields = $this->getFieldsToSerialize($mapping['targetEntity'], $config);
        foreach ($fields as $field) {
            $qb->addSelect(sprintf('r.%s', $field));
        }

        $items = $qb->getQuery()->getArrayResult();

        $result = [];
        foreach ($items as $item) {
            $result[$item['entityId']][] = array_intersect_key($item, array_fill_keys($fields, true));
        }

        return $result;
    }

    /**
     * @param array  $result
     * @param string $entityClass
     * @param array  $relatedData
     * @param array  $config
     *
     * @throws \RuntimeException
     */
    protected function applyRelatedData(array &$result, $entityClass, $relatedData, $config)
    {
        $entityIdFieldName = $this->getEntityIdFieldName($entityClass);
        foreach ($result as &$resultItem) {
            if (!isset($resultItem[$entityIdFieldName]) && !array_key_exists($entityIdFieldName, $resultItem)) {
                throw new \RuntimeException(
                    sprintf('The result item does not contain the entity identifier. Entity: %s.', $entityClass)
                );
            }
            $entityId = $resultItem[$entityIdFieldName];
            foreach ($relatedData as $field => $relatedItems) {
                $resultItem[$field] = [];
                if (empty($relatedItems[$entityId])) {
                    continue;
                }
                $targetConfig = isset($config['fields'][$field])
                    ? $config['fields'][$field]
                    : [];
                foreach ($relatedItems[$entityId] as $relatedItem) {
                    $resultItem[$field][] = !empty($targetConfig['fields']) && is_string($targetConfig['fields'])
                        ? $relatedItem[$targetConfig['fields']]
                        : $relatedItem;
                }
            }
        }
    }

    /**
     * @param array $config
     *
     * @return boolean
     */
    protected function isExcludeAll($config)
    {
        return
            (isset($config['exclusion_policy']) && $config['exclusion_policy'] === 'all')
            || (!empty($config['fields']) && is_string($config['fields']));
    }

    /**
     * @param array $config
     *
     * @return string[]
     */
    protected function getExcludedFields($config)
    {
        return !empty($config['excluded_fields'])
            ? $config['excluded_fields']
            : [];
    }

    /**
     * @param string $entityClass
     * @param array  $config
     *
     * @return string[]
     */
    protected function getFields($entityClass, $config)
    {
        if ($this->isExcludeAll($config)) {
            if (empty($config['fields'])) {
                $fields = [];
            } elseif (is_string($config['fields'])) {
                $fields = [$config['fields']];
            } else {
                $fields = array_keys($config['fields']);
            }
        } else {
            $entityMetadata = $this->doctrineHelper->getEntityMetadata($entityClass);
            $fields         = array_filter(
                array_merge($entityMetadata->getFieldNames(), $entityMetadata->getAssociationNames()),
                function ($field) use ($entityClass) {
                    return $this->dataAccessor->hasGetter($entityClass, $field);
                }
            );
        }
        $excludedFields = $this->getExcludedFields($config);
        if (!empty($excludedFields)) {
            $fields = array_diff($fields, $excludedFields);
        }

        return $fields;
    }

    /**
     * @param string $entityClass
     * @param array  $config
     *
     * @return string[]
     */
    protected function getFieldsToSerialize($entityClass, $config)
    {
        $entityMetadata = $this->doctrineHelper->getEntityMetadata($entityClass);

        return array_filter(
            $this->getFields($entityClass, $config),
            function ($field) use ($entityMetadata) {
                return !$entityMetadata->isCollectionValuedAssociation($field);
            }
        );
    }

    /**
     * @param string $entityClass
     * @param array  $config
     *
     * @return boolean
     */
    protected function hasAssociations($entityClass, $config)
    {
        $entityMetadata = $this->doctrineHelper->getEntityMetadata($entityClass);
        $fields         = $this->getFields($entityClass, $config);
        foreach ($fields as $field) {
            if ($entityMetadata->isAssociation($field)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param object[] $entities
     * @param string   $entityClass
     *
     * @return array
     */
    protected function getEntityIds($entities, $entityClass)
    {
        $ids             = [];
        $getIdMethodName = $this->getEntityIdGetter($entityClass);
        foreach ($entities as $entity) {
            $id = $entity->$getIdMethodName();
            if (!isset($ids[$id])) {
                $ids[$id] = $id;
            }
        }

        return array_values($ids);
    }

    /**
     * @param string $entityClass
     *
     * @return string[]
     */
    protected function getEntityIdFieldNames($entityClass)
    {
        return $this->doctrineHelper->getEntityMetadata($entityClass)->getIdentifierFieldNames();
    }

    /**
     * @param string $entityClass
     *
     * @return string
     */
    protected function getEntityIdFieldName($entityClass)
    {
        return $this->doctrineHelper->getEntityMetadata($entityClass)->getSingleIdentifierFieldName();
    }

    /**
     * @param string $entityClass
     *
     * @return string
     */
    protected function getEntityIdGetter($entityClass)
    {
        return 'get' . ucfirst($this->getEntityIdFieldName($entityClass));
    }
}
