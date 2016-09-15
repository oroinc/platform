<?php

namespace Oro\Bundle\SearchBundle\Engine\Orm;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\EntityManager;

use Oro\Bundle\SearchBundle\Entity\AbstractItem;
use Oro\Bundle\SearchBundle\Query\Criteria\Criteria;
use Oro\Bundle\SearchBundle\Query\Query;

/**
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
abstract class BaseDriver
{
    /**
     * @var string
     */
    protected $entityName;

    /**
     * @var \Doctrine\ORM\EntityManager
     */
    protected $em;

    /**
     * @var array
     */
    protected $associationMappings;

    /**
     * @param \Doctrine\ORM\EntityManager         $em
     * @param \Doctrine\ORM\Mapping\ClassMetadata $class
     * @throws \InvalidArgumentException
     */
    public function initRepo(EntityManager $em, ClassMetadata $class)
    {
        if (!is_a($class->name, AbstractItem::class, true)) {
            throw new \InvalidArgumentException(
                'ClassMetadata doesn\'t represent Oro\Bundle\SearchBundle\Entity\Item class or its descendant'
            );
        }

        $this->associationMappings = $class->associationMappings;
        $this->entityName = $class->name;
        $this->em         = $em;
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
        return $this->em->createQueryBuilder()
            ->select($alias)
            ->from($this->entityName, $alias);
    }

    /**
     * Search query by Query builder object
     * Can contains duplicates and we can not use HYDRATE_OBJECT because of performance issue. Will be fixed in BAP-7166
     *
     * @param \Oro\Bundle\SearchBundle\Query\Query $query
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
     * @param \Oro\Bundle\SearchBundle\Query\Query $query
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
     * Truncate all entities
     *
     * @throws \Exception
     */
    public function truncateIndex()
    {
        /** @var Connection $connection */
        $connection = $this->em->getConnection();
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
     * @param integer                    $index
     * @param array                      $searchCondition
     * @param boolean                    $setOrderBy
     *
     * @return string
     */
    public function addTextField(QueryBuilder $qb, $index, $searchCondition, $setOrderBy = true)
    {
        $useFieldName = $searchCondition['fieldName'] == '*' ? false : true;
        $fieldValue   = $this->filterTextFieldValue($searchCondition['fieldValue']);

        // TODO Need to clarify search requirements in scope of CRM-214
        if (in_array($searchCondition['condition'], [Query::OPERATOR_CONTAINS, Query::OPERATOR_EQUALS])) {
            $searchString = $this->createContainsStringQuery($index, $useFieldName);
        } else {
            $searchString = $this->createNotContainsStringQuery($index, $useFieldName);
        }

        $this->setFieldValueStringParameter($qb, $index, $fieldValue, $searchCondition['condition']);

        if ($useFieldName) {
            $qb->setParameter('field' . $index, $searchCondition['fieldName']);
        }

        if ($setOrderBy) {
            $this->setTextOrderBy($qb, $index);
        }

        return '(' . $searchString . ' ) ';
    }

    /**
     * @param string $fieldType
     * @param int    $index
     *
     * @return string
     */
    public function getJoinAlias($fieldType, $index)
    {
        return sprintf('%sField%s', $fieldType, $index);
    }

    /**
     * Returns an unique ID hash, used for SQL aliases
     *
     * @return string
     */
    public function getUniqueId()
    {
        return str_replace('.', '_', uniqid('', true));
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
     * @param AbstractPlatform $dbPlatform
     * @param Connection       $connection
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
     * @param Connection       $connection
     * @param string           $entityName
     */
    protected function truncateTable(AbstractPlatform $dbPlatform, Connection $connection, $entityName)
    {
        /** @var ClassMetadata $metadata */
        $metadata = $this->em->getClassMetadata($entityName);
        $query    = $this->getTruncateQuery($dbPlatform, $metadata->getTableName());
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
     * @param array|string $fieldValue
     *
     * @return array|string
     */
    protected function filterTextFieldValue($fieldValue)
    {
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

        return $stringQuery . $joinAlias . '.value LIKE :value' . $index;
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

        return $stringQuery . $joinAlias . '.value NOT LIKE :value' . $index;
    }

    /**
     * Set string parameter for qb
     *
     * @param \Doctrine\ORM\QueryBuilder $qb
     * @param integer                    $index
     * @param string                     $fieldValue
     * @param string                     $searchCondition
     */
    protected function setFieldValueStringParameter(QueryBuilder $qb, $index, $fieldValue, $searchCondition)
    {
        $qb->setParameter('value' . $index, '%' . str_replace(' ', '%', $fieldValue) . '%');
    }

    /**
     * Add non string search to qb
     *
     * @param \Doctrine\ORM\QueryBuilder $qb
     * @param integer                    $index
     * @param array                      $searchCondition
     *
     * @return string
     */
    public function addNonTextField(QueryBuilder $qb, $index, $searchCondition)
    {
        $value     = $searchCondition['fieldValue'];
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
        $this->applyWhereToQB($query, $qb, $setOrderBy);

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
        $aliases = $query->getSelectAliases();

        if (empty($selects)) {
            return;
        }

        foreach ($selects as $select) {
            list($type, $name) = Criteria::explodeFieldTypeName($select);

            $uniqIndex = $this->getUniqueId();
            $joinField = $this->getJoinField($type);
            $joinAlias = $this->getJoinAlias($type, $uniqIndex);

            $withClause = sprintf('%s.field = :param%s', $joinAlias, $uniqIndex);

            $qb->leftJoin($joinField, $joinAlias, Join::WITH, $withClause)
                ->setParameter('param' . $uniqIndex, $name);

            $alias = isset($aliases[$select]) ? $aliases[$select] : $name;

            $qb->addSelect($joinAlias . '.value as ' . $alias);
        }
    }

    /**
     * Parses and applies the FROM part to the search engine's
     * query.
     *
     * @param \Oro\Bundle\SearchBundle\Query\Query $query
     * @param \Doctrine\ORM\QueryBuilder           $qb
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
            $qb->andWhere($visitor->dispatch($whereExpression));
        }
    }

    /**
     * Applies the ORDER BY part from the Query to the
     * search engine's query.
     *
     * @param \Oro\Bundle\SearchBundle\Query\Query $query
     * @param \Doctrine\ORM\QueryBuilder           $qb
     */
    protected function applyOrderByToQB(Query $query, QueryBuilder $qb)
    {
        $orderBy = $query->getCriteria()->getOrderings();

        if ($orderBy) {
            $direction = reset($orderBy);
            list($fieldType, $fieldName) = Criteria::explodeFieldTypeName(key($orderBy));
            $orderRelation = $fieldType . 'Fields';
            $qb->leftJoin('search.' . $orderRelation, 'orderTable', 'WITH', 'orderTable.field = :orderField')
                ->orderBy('orderTable.value', $direction)
                ->setParameter('orderField', $fieldName);
            $qb->addSelect('orderTable.value');
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
}
