<?php

namespace Oro\Bundle\SearchBundle\Engine\Orm;

use Carbon\Carbon;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Platforms\PostgreSqlPlatform;
use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\Query\Expr\Select;
use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\SearchBundle\Entity\AbstractItem;
use Oro\Bundle\SearchBundle\Exception\ExpressionSyntaxError;
use Oro\Bundle\SearchBundle\Query\Criteria\Criteria;
use Oro\Bundle\SearchBundle\Query\Query;
use Oro\Component\DoctrineUtils\ORM\QueryBuilderUtil;

/**
 * Abstract DB driver used to run search queries for ORM search engine
 *
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
abstract class BaseDriver implements DBALPersisterInterface
{
    const EXPRESSION_TYPE_OR  = 'OR';
    const EXPRESSION_TYPE_AND = 'AND';

    /**
     * @var string
     */
    protected $entityName;

    /**
     * @deprecated Please use the entityManager property instead
     *
     * @var EntityManagerInterface
     */
    protected $em;

    /**
     * @var EntityManagerInterface
     */
    protected $entityManager;

    /**
     * @var array
     */
    protected $associationMappings;

    /**
     * Oro\Bundle\SearchBundle\Entity\AbstractItem data to be stored into db
     *
     * @var array
     * [
     *     spl_object_hash => [
     *         'id'         => id of the item,
     *         'entity'     => class of the entity,
     *         'alias'      => alias of the entity,
     *         'record_id'  => id of the entity,
     *         'title'      => title of the entity,
     *         'changed'    => changed attribute,
     *         'created_at' => when the Item was created,
     *         'updated_at' => when the Item was updated,
     *     ],
     *     ...
     * ]
     */
    private $writeableItemData = [];

    /**
     * Doctrine types for columns in $writeableItemData
     *
     * @var array
     * [
     *     'insert' => [
     *         'integer',
     *         ...
     *     ],
     *     'update' => [
     *         'string',
     *         ...
     *     ],
     * ]
     */
    private $writeableItemTypes = [];

    /**
     * We need this to prevent reuse of object hashs
     *
     * @var AbstractItem[]
     */
    private $writeableItems = [];

    /**
     * @var array
     * [
     *     'table_name' => [
     *         'columns' => array of Index column names to insert
     *         'types'   => array of doctrine types of columns to insert for all values
     *         'data' => [
     *             [
     *                 'itemRef' => string reference (object hash) to item
     *                              so we can retrieve AbstractItem::id after insert
     *                 'values'  => values for 'columns' for one record to insert
     *             ],
     *             ...
     *         ],
     *     ],
     *     ...
     * ]
     */
    private $indexInsertData = [];

    /**
     * @var array
     * [
     *     'table_name' => [
     *         'types' =>  array of doctrine types of columns to insert for one record
     *         'data'  => [
     *             [
     *                 'itemRef' => string reference (object hash) to item
     *                              so we can retrieve AbstractItem::id after insert
     *                 'values'  => values for 'columns' for one record to update
     *             ],
     *             ...
     *         ],
     *     ],
     *     ...
     * ]
     */
    private $indexUpdateData = [];

    /**
     * @param EntityManagerInterface $em
     * @param ClassMetadata $class
     * @throws \InvalidArgumentException
     */
    public function initRepo(EntityManagerInterface $em, ClassMetadata $class)
    {
        if (!is_a($class->name, AbstractItem::class, true)) {
            throw new \InvalidArgumentException(
                'ClassMetadata doesn\'t represent Oro\Bundle\SearchBundle\Entity\Item class or its descendant'
            );
        }

        $this->associationMappings = $class->associationMappings;
        $this->entityName          = $class->name;
        $this->em                  = $em;
        $this->entityManager       = $em;
    }

    /**
     * Create a new QueryBuilder instance that is prepopulated for this entity name
     *
     * @param string $alias
     *
     * @return QueryBuilder $qb
     */
    public function createQueryBuilder($alias)
    {
        return $this->entityManager->createQueryBuilder()
            ->select($alias)
            ->from($this->entityName, $alias);
    }

    /**
     * Search query by Query builder object
     * Can contains duplicates and we can not use HYDRATE_OBJECT because of performance issue. Will be fixed in BAP-7166
     *
     * @param Query $query
     *
     * @return array
     */
    public function search(Query $query)
    {
        $qb = $this->getRequestQB($query);
        $qb->distinct(true);

        // set max results count
        if ($query->getCriteria()->getMaxResults() > 0) {
            $qb->setMaxResults($query->getCriteria()->getMaxResults());
        }

        // set first result offset
        if ($query->getCriteria()->getFirstResult() > 0) {
            $qb->setFirstResult($query->getCriteria()->getFirstResult());
        }

        return $qb
            ->getQuery()
            ->getArrayResult();
    }

    /**
     * Get count of records without limit parameters in query
     *
     * @param Query $query
     *
     * @return integer
     */
    public function getRecordsCount(Query $query)
    {
        $qb = $this->getRequestQB($query, false);
        $qb->select($qb->expr()->countDistinct('search.id'));

        return (int)$qb->getQuery()->getSingleScalarResult();
    }

    /**
     * @param Query $query
     * @return array
     */
    public function getAggregatedData(Query $query)
    {
        $aggregatedData = [];
        foreach ($query->getAggregations() as $name => $options) {
            $field = $options['field'];
            $function = $options['function'];
            $fieldName = Criteria::explodeFieldTypeName($field)[1];
            QueryBuilderUtil::checkField($fieldName);

            // prepare query builder to apply grouping
            $aggregatedQuery = clone $query;
            $aggregatedQuery->select($field);
            $queryBuilder = $this->getRequestQB($aggregatedQuery, false);
            /** @var Select $fieldSelectExpression */
            $fieldSelectExpression = $queryBuilder->getDQLPart('select')[1];
            $fieldSelect = (string)$fieldSelectExpression;
            $queryBuilder->resetDQLPart('select');

            switch ($function) {
                case Query::AGGREGATE_FUNCTION_COUNT:
                    $queryBuilder->select([
                        $fieldSelect,
                        sprintf('%s as countValue', $queryBuilder->expr()->countDistinct('search.id'))
                    ]);
                    $queryBuilder->groupBy($fieldName);

                    foreach ($queryBuilder->getQuery()->getArrayResult() as $row) {
                        $key = $row[$fieldName];
                        // skip null values to maintain similar behaviour cross all engines
                        if (null !== $key) {
                            $aggregatedData[$name][(string)$key] = (int)$row['countValue'];
                        }
                    }
                    break;

                case Query::AGGREGATE_FUNCTION_MAX:
                case Query::AGGREGATE_FUNCTION_MIN:
                case Query::AGGREGATE_FUNCTION_AVG:
                case Query::AGGREGATE_FUNCTION_SUM:
                    $fieldSelect = explode(' as ', $fieldSelect)[0];
                    $queryBuilder->select(sprintf('%s(%s)', $function, $fieldSelect));
                    $aggregatedData[$name] = (float)$queryBuilder->getQuery()->getSingleScalarResult();
                    break;

                default:
                    throw new \LogicException(sprintf('Aggregating function %s is not supported', $function));
            }
        }

        return $aggregatedData;
    }

    /**
     * Truncate all entities
     *
     * @throws \Exception
     */
    public function truncateIndex()
    {
        /** @var Connection $connection */
        $connection = $this->entityManager->getConnection();
        $dbPlatform = $connection->getDatabasePlatform();
        $connection->beginTransaction();
        try {
            $this->truncateEntities($dbPlatform, $connection);
            $connection->commit();
        } catch (\Exception $e) {
            $connection->rollback();
            throw $e;
        }
    }

    /**
     * Add text search to qb
     *
     * @param \Doctrine\ORM\QueryBuilder $qb
     * @param string                     $index
     * @param array                      $searchCondition
     * @param boolean                    $setOrderBy
     *
     * @return string
     */
    abstract public function addTextField(QueryBuilder $qb, $index, $searchCondition, $setOrderBy = true);

    /**
     * @param string $fieldType
     * @param string|int $index
     *
     * @return string
     */
    public function getJoinAlias($fieldType, $index)
    {
        return sprintf('%sField%s', $fieldType, $index);
    }

    /**
     * Returning the DQL name of the search attribute entity
     * for given type.
     *
     * @param string $type
     * @return string
     */
    public function getJoinField($type)
    {
        return sprintf('search.%sFields', $type);
    }

    /**
     * Returns an unique (in terms of single query) alias & index, used for SQL aliases for joins
     *
     * @param string|array $fieldName
     * @param string $type
     * @param array $existingAliases
     * @return array
     */
    public function getJoinAttributes($fieldName, $type, array $existingAliases)
    {
        if (is_array($fieldName)) {
            $fieldName = implode('_', $fieldName);
        }

        $i = 0;
        do {
            $i++;
            $index = $fieldName . '_' . $i;
            $joinAlias = $this->getJoinAlias($type, $index);
        } while (in_array($joinAlias, $existingAliases));

        return [$joinAlias, $index, $i];
    }

    /**
     * @param AbstractPlatform $dbPlatform
     * @param Connection $connection
     */
    protected function truncateEntities(AbstractPlatform $dbPlatform, Connection $connection)
    {
        $this->truncateTable($dbPlatform, $connection, $this->entityName);
        $this->truncateTable($dbPlatform, $connection, $this->associationMappings['textFields']['targetEntity']);
        $this->truncateTable($dbPlatform, $connection, $this->associationMappings['integerFields']['targetEntity']);
        $this->truncateTable($dbPlatform, $connection, $this->associationMappings['decimalFields']['targetEntity']);
        $this->truncateTable($dbPlatform, $connection, $this->associationMappings['datetimeFields']['targetEntity']);
    }

    /**
     * Truncate query for table
     *
     * @param AbstractPlatform $dbPlatform
     * @param Connection $connection
     * @param string $entityName
     */
    protected function truncateTable(AbstractPlatform $dbPlatform, Connection $connection, $entityName)
    {
        /** @var ClassMetadata $metadata */
        $metadata = $this->entityManager->getClassMetadata($entityName);
        $query = $this->getTruncateQuery($dbPlatform, $metadata->getTableName());
        $connection->executeUpdate($query);
    }

    /**
     * @param AbstractPlatform $dbPlatform
     * @param string           $tableName
     *
     * @return string
     */
    protected function getTruncateQuery(AbstractPlatform $dbPlatform, $tableName)
    {
        return $dbPlatform->getTruncateTableSql($tableName);
    }

    /**
     * @param string $fieldName
     * @param array|string $fieldValue
     * @return array|string
     */
    protected function filterTextFieldValue($fieldName, $fieldValue)
    {
        // BB-7272: do not clear fields other than `all_text_*`
        if (strpos($fieldName, 'all_text') !== 0) {
            return $fieldValue;
        }

        if (is_string($fieldValue)) {
            $fieldValue = Query::clearString($fieldValue);
        } elseif (is_array($fieldValue)) {
            foreach ($fieldValue as $key => $value) {
                $fieldValue[$key] = Query::clearString($value);
            }
        }

        return $fieldValue;
    }

    /**
     * Create search string for string parameters (contains)
     *
     * @param integer $index
     * @param bool    $useFieldName
     *
     * @return string
     */
    protected function createContainsStringQuery($index, $useFieldName = true)
    {
        $joinAlias = $this->getJoinAlias(Query::TYPE_TEXT, $index);

        $stringQuery = '';
        if ($useFieldName) {
            $stringQuery = $joinAlias . '.field = :field' . $index . ' AND ';
        }

        return sprintf('%s LOWER(%s.value) LIKE LOWER(:value%s)', $stringQuery, $joinAlias, $index);
    }

    /**
     * Create search string for string parameters (not contains)
     *
     * @param integer $index
     * @param bool    $useFieldName
     *
     * @return string
     */
    protected function createNotContainsStringQuery($index, $useFieldName = true)
    {
        $joinAlias = $this->getJoinAlias(Query::TYPE_TEXT, $index);

        $stringQuery = '';
        if ($useFieldName) {
            $stringQuery = $joinAlias . '.field = :field' . $index . ' AND ';
        }

        return sprintf('%s LOWER(%s.value) NOT LIKE LOWER(:value%s)', $stringQuery, $joinAlias, $index);
    }

    /**
     * Add non string search to qb
     *
     * @param QueryBuilder $qb
     * @param string $index
     * @param array $searchCondition
     *
     * @return string
     */
    public function addNonTextField(QueryBuilder $qb, $index, $searchCondition)
    {
        $value = $searchCondition['fieldValue'];
        $joinAlias = $this->getJoinAlias($searchCondition['fieldType'], $index);
        $qb->setParameter('field' . $index, $searchCondition['fieldName']);
        $qb->setParameter('value' . $index, $value);

        return $this->createNonTextQuery(
            $joinAlias,
            $index,
            $searchCondition['condition'],
            is_array($searchCondition['fieldName']) ? 'in' : '='
        );
    }

    /**
     * Create search string for non string parameters
     *
     * @param $joinAlias
     * @param $index
     * @param $condition
     * @param $operator
     *
     * @return string
     */
    protected function createNonTextQuery($joinAlias, $index, $condition, $operator)
    {
        $openBrackets  = '';
        $closeBrackets = '';
        if ($operator === 'in') {
            $openBrackets  = '(';
            $closeBrackets = ')';
        }

        switch ($condition) {
            case Query::OPERATOR_IN:
            case Query::OPERATOR_NOT_IN:
                $queryString = '(%s.field %s %s :field%s %s AND %s.value %s (:value%s))';
                break;
            default:
                $queryString = '(%s.field %s %s :field%s %s AND %s.value %s :value%s)';
        }

        return sprintf(
            $queryString,
            $joinAlias,
            $operator,
            $openBrackets,
            $index,
            $closeBrackets,
            $joinAlias,
            $condition !== Query::OPERATOR_NOT_IN ? $condition : 'not in',
            $index
        );
    }

    /**
     * @param \Oro\Bundle\SearchBundle\Query\Query $query
     * @param boolean                              $setOrderBy
     *
     * @return \Doctrine\ORM\QueryBuilder
     */
    protected function getRequestQB(Query $query, $setOrderBy = true)
    {
        $qb = $this->createQueryBuilder('search')
            ->select('search as item');

        $this->applySelectToQB($query, $qb);
        $this->applyFromToQB($query, $qb);

        // set order part in where only if no query ordering defined
        $setOrderByInWhere = $setOrderBy && !$query->getCriteria()->getOrderings();
        $this->applyWhereToQB($query, $qb, $setOrderByInWhere);

        if ($setOrderBy) {
            $this->applyOrderByToQB($query, $qb);
        }

        return $qb;
    }

    /**
     * Parses and applies the SELECT's columns (if selected)
     * from the casual query into the search index query.
     *
     * @param Query        $query
     * @param QueryBuilder $qb
     */
    protected function applySelectToQB(Query $query, QueryBuilder $qb)
    {
        $selects = $query->getSelect();

        if (empty($selects)) {
            return;
        }

        foreach ($selects as $select) {
            list($type, $name) = Criteria::explodeFieldTypeName($select);
            QueryBuilderUtil::checkIdentifier($name);
            QueryBuilderUtil::checkIdentifier($type);

            $joinField = $this->getJoinField($type);
            list($joinAlias, $uniqIndex) = $this->getJoinAttributes($name, $type, $qb->getAllAliases());
            QueryBuilderUtil::checkIdentifier($joinAlias);
            QueryBuilderUtil::checkIdentifier($uniqIndex);

            $param = sprintf('param%s', $uniqIndex);
            $withClause = sprintf('%s.field = :%s', $joinAlias, $param);

            $qb->leftJoin($joinField, $joinAlias, Join::WITH, $withClause)
                ->setParameter($param, $name);

            $qb->addSelect($joinAlias . '.value as ' . $name);
        }
    }

    /**
     * Parses and applies the FROM part to the search engine's
     * query.
     *
     * @param Query $query
     * @param QueryBuilder $qb
     */
    protected function applyFromToQB(Query $query, QueryBuilder $qb)
    {
        $useFrom = true;
        foreach ($query->getFrom() as $from) {
            if ($from === '*') {
                $useFrom = false;
            }
        }
        if ($useFrom) {
            $qb->andWhere($qb->expr()->in('search.alias', $query->getFrom()));
        }
    }

    /**
     * Parses and applies the WHERE expressions from the DQL
     * to the search engine's query.
     *
     * @param Query        $query
     * @param QueryBuilder $qb
     * @param string       $setOrderBy
     */
    protected function applyWhereToQB(Query $query, QueryBuilder $qb, $setOrderBy)
    {
        $criteria = $query->getCriteria();

        $whereExpression = $criteria->getWhereExpression();
        if ($whereExpression) {
            $visitor = new OrmExpressionVisitor($this, $qb, $setOrderBy);
            $whereCondition = $visitor->dispatch($whereExpression);
            if ($whereCondition) {
                $qb->andWhere($whereCondition);
            }
        }
    }

    /**
     * Applies the ORDER BY part from the Query to the
     * search engine's query.
     *
     * @param Query $query
     * @param QueryBuilder $qb
     */
    protected function applyOrderByToQB(Query $query, QueryBuilder $qb)
    {
        $orderBy = $query->getCriteria()->getOrderings();

        if ($orderBy) {
            $direction = reset($orderBy);
            list($fieldType, $fieldName) = Criteria::explodeFieldTypeName(key($orderBy));
            QueryBuilderUtil::checkIdentifier($fieldType);
            $orderRelation = $fieldType . 'Fields';
            $qb->leftJoin('search.' . $orderRelation, 'orderTable', 'WITH', 'orderTable.field = :orderField')
                ->orderBy('orderTable.value', QueryBuilderUtil::getSortOrder($direction))
                ->setParameter('orderField', $fieldName);
            $qb->addSelect('orderTable.value');
        } else {
            $qb->orderBy('search.weight', Criteria::DESC)
                ->addOrderBy('search.id', Criteria::DESC);
        }
    }

    /**
     * Set fulltext range order by
     *
     * @param \Doctrine\ORM\QueryBuilder $qb
     * @param int                        $index
     */
    protected function setTextOrderBy(QueryBuilder $qb, $index)
    {
    }

    /**
     * @param integer $index
     * @param array $searchCondition
     *
     * @return string
     */
    public function addFilteringField($index, $searchCondition)
    {
        $condition = $searchCondition['condition'];
        $joinAlias = $this->getJoinAlias($searchCondition['fieldType'], $index);

        switch ($condition) {
            case Query::OPERATOR_EXISTS:
                return "$joinAlias.id IS NOT NULL";

            case Query::OPERATOR_NOT_EXISTS:
                return "$joinAlias.id IS NULL";

            default:
                throw new ExpressionSyntaxError(
                    sprintf('Unsupported operator "%s"', $condition)
                );
        }
    }

    /**
     * Stores all data taken from Items given by 'writeItem' method
     */
    public function flushWrites()
    {
        $connection = $this->getConnection();

        $this->processItems($connection);
        $multiInsertQueryData = [];
        $this->fillQueryData($connection, $multiInsertQueryData);

        $this->runMultiInserts($connection, $multiInsertQueryData);
        $this->runUpdates($connection, $this->indexUpdateData);

        $this->writeableItems = [];
        $this->writeableItemData = [];
        $this->indexInsertData = [];
        $this->indexUpdateData = [];
    }

    /**
     * Adds AbstractItem of which data will be stored when 'flushWrites' method is called
     *
     * @param AbstractItem $item
     */
    public function writeItem(AbstractItem $item)
    {
        $this->populateItem($item);
        $this->populateIndexByType($item->getIntegerFields(), $item, 'integer');
        $this->populateIndexByType($item->getTextFields(), $item, 'text');
        $this->populateIndexByType($item->getDecimalFields(), $item, 'decimal');
        $this->populateIndexByType($item->getDatetimeFields(), $item, 'datetime');
    }

    /**
     * Prepares index data for queries to be stored
     *
     * @param Connection $connection
     * @param array $multiInsertQueryData
     */
    private function fillQueryData(Connection $connection, array &$multiInsertQueryData)
    {
        foreach ($this->indexInsertData as $table => $data) {
            $insertValues = [];
            foreach ($data['data'] as $record) {
                $record['values']['item_id'] = $this->writeableItemData[$record['itemRef']]['id'];
                foreach ($record['values'] as $value) {
                    array_push($insertValues, $value);
                }
            }

            if ($insertValues) {
                $multiInsertQueryData[$table] = [
                    'query' => sprintf(
                        'INSERT INTO %s (%s) VALUES %s',
                        $connection->quoteIdentifier($table),
                        implode(', ', array_map([$connection, 'quoteIdentifier'], $data['columns'])),
                        implode(
                            ', ',
                            array_fill(
                                0,
                                count($data['data']),
                                sprintf('(%s)', implode(', ', array_fill(0, count($data['columns']), '?')))
                            )
                        )
                    ),
                    'values' => $insertValues,
                    'types' => $data['types'],
                ];
            }
        }

        foreach ($this->indexUpdateData as $table => $data) {
            foreach ($data['data'] as $record) {
                $record['values']['item_id'] = $this->writeableItemData[$record['itemRef']]['id'];
            }
        }
    }

    /**
     * Runs multi inserts taken from $multiInsertQueryData argument
     *
     * @param Connection $connection
     * @param array $multiInsertQueryData
     */
    private function runMultiInserts(Connection $connection, array $multiInsertQueryData)
    {
        foreach ($multiInsertQueryData as $data) {
            $connection->executeQuery($data['query'], $data['values'], $data['types']);
        }
    }

    /**
     * Runs updates taken from $updateQueryData argument
     *
     * @param Connection $connection
     * @param array $updateQueryData
     */
    private function runUpdates(Connection $connection, array $updateQueryData)
    {
        foreach ($updateQueryData as $table => $data) {
            foreach ($data['data'] as $record) {
                $connection->update(
                    $connection->quoteIdentifier($table),
                    $record['values'],
                    ['id' => $record['values']['id']],
                    $data['types']
                );
            }
        }
    }

    /**
     * Stores items from $this->writeableItemData and updates their ids
     *
     * @param Connection $connection
     */
    private function processItems(Connection $connection)
    {
        $now = Carbon::now();

        if (empty($this->writeableItems)) {
            return;
        }

        $tablePlain = $this->getEntityTable(current($this->writeableItems));
        $table = $connection->quoteIdentifier($tablePlain);

        foreach ($this->writeableItemData as &$data) {
            $data['updated_at'] = $now;
            if (isset($data['id'])) {
                $connection->update(
                    $table,
                    $data,
                    ['id' => $data['id']],
                    $this->writeableItemTypes['update']
                );
            } else {
                $data['created_at'] = $now;
                $connection->insert(
                    $table,
                    $data,
                    $this->writeableItemTypes['insert']
                );
                $data['id'] = $connection->lastInsertId(
                    $this->getSequenceName($tablePlain, $connection)
                );
            }
        }
    }

    /**
     * Converts $item into array and stores the result in the object
     *
     * @param AbstractItem $item
     */
    private function populateItem(AbstractItem $item)
    {
        $this->writeableItems[spl_object_hash($item)] = $item;

        if (!$this->writeableItemTypes) {
            $this->writeableItemTypes = [
                'insert' => [
                    Type::STRING,
                    Type::STRING,
                    Type::INTEGER,
                    Type::STRING,
                    Type::DECIMAL,
                    Type::BOOLEAN,
                    Type::DATETIME,
                    Type::DATETIME,
                ],
                'update' => [
                    Type::INTEGER,
                    Type::STRING,
                    Type::STRING,
                    Type::INTEGER,
                    Type::STRING,
                    Type::DECIMAL,
                    Type::BOOLEAN,
                    Type::DATETIME,
                    Type::DATETIME,
                ],
            ];
        }

        if ($item->getId()) {
            $this->writeableItemData[spl_object_hash($item)] = [
                'id' => $item->getId(),
                'entity' => $item->getEntity(),
                'alias' => $item->getAlias(),
                'record_id' => $item->getRecordId(),
                'title' => $item->getTitle(),
                'weight' => $item->getWeight(),
                'changed' => $item->getChanged(),
                'created_at' => $item->getCreatedAt(),
                'updated_at' => $item->getUpdatedAt(),
            ];
        } else {
            $this->writeableItemData[spl_object_hash($item)] = [
                'entity' => $item->getEntity(),
                'alias' => $item->getAlias(),
                'record_id' => $item->getRecordId(),
                'title' => $item->getTitle(),
                'weight' => $item->getWeight(),
                'changed' => $item->getChanged(),
                'created_at' => $item->getCreatedAt(),
                'updated_at' => $item->getUpdatedAt(),
            ];
        }
    }

    /**
     * Converts indexes of $item into objects and stores them in the object
     *
     * @param Collection   $fields
     * @param AbstractItem $item
     * @param string       $type
     */
    private function populateIndexByType(Collection $fields, AbstractItem $item, $type)
    {
        if ($fields->isEmpty()) {
            return;
        }

        $table = $this->getIndexTable($item, $type);

        if (!isset($this->indexUpdateData[$table])) {
            $this->indexUpdateData[$table] = [
                'data'  => [],
                'types' => [
                    Type::INTEGER,
                    Type::STRING,
                    $type,
                    Type::INTEGER,
                ],
            ];
            $this->indexInsertData[$table] = [
                'columns' => ['field', 'value', 'item_id'],
                'data'  => [],
                'types' => [],
            ];
        }

        foreach ($fields as $field) {
            if ($field->getId()) {
                $this->indexUpdateData[$table]['data'][] = [
                    'itemRef' => spl_object_hash($item),
                    'values' => [
                        'id' => $field->getId(),
                        'field' => $field->getField(),
                        'value' => $field->getValue(),
                        'item_id' => $item->getId(),
                    ],
                ];
            } else {
                $this->indexInsertData[$table]['data'][] = [
                    'itemRef' => spl_object_hash($item),
                    'values' => [
                        'field' => $field->getField(),
                        'value' => $field->getValue(),
                        'item_id' => $item->getId(),
                    ],
                ];

                array_push(
                    $this->indexInsertData[$table]['types'],
                    Type::STRING,
                    $type,
                    Type::INTEGER
                );
            }
        }
        $fields->clear();
    }

    /**
     * @return Connection
     */
    private function getConnection()
    {
        return $this->entityManager
            ->getConnection();
    }

    /**
     * @param AbstractItem $item
     * @return string
     */
    private function getEntityTable(AbstractItem $item)
    {
        return $this->entityManager
            ->getClassMetadata(get_class($item))
            ->getTableName();
    }

    /**
     * @param AbstractItem $item
     * @param string       $type
     * @return string
     */
    private function getIndexTable($item, $type)
    {
        $tableName = $this->getEntityTable($item);

        $parts = explode('_', $tableName);

        array_pop($parts);

        // hack for classic search index tables
        if ($parts[1] === 'search') {
            $parts[] = 'index';
        }

        $parts[] = $type;

        return implode('_', $parts);
    }

    /**
     * @param string     $entityTable
     * @param Connection $connection
     * @return null|string
     */
    private function getSequenceName($entityTable, Connection $connection)
    {
        if (!$connection->getDatabasePlatform() instanceof PostgreSqlPlatform) {
            return null;
        }

        $parts = explode('_', $entityTable);

        $parts[] = 'id';
        $parts[] = 'seq';

        return implode('_', $parts);
    }
}
