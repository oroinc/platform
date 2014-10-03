<?php

namespace Oro\Bundle\SearchBundle\Engine\Orm;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\EntityManager;

use Oro\Bundle\SearchBundle\Query\Query;

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
     * @param \Doctrine\ORM\EntityManager         $em
     * @param \Doctrine\ORM\Mapping\ClassMetadata $class
     */
    public function initRepo(EntityManager $em, ClassMetadata $class)
    {
        $this->entityName = $class->name;
        $this->em = $em;
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
        if ($query->getMaxResults() > 0) {
            $qb->setMaxResults($query->getMaxResults());
        }

        // set first result offset
        if ($query->getFirstResult() > 0) {
            $qb->setFirstResult($query->getFirstResult());
        }

        return $qb
            ->getQuery()
            ->getResult();
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

        return (int) $qb->getQuery()->getSingleScalarResult();
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
     * @param AbstractPlatform $dbPlatform
     * @param Connection $connection
     */
    protected function truncateEntities(AbstractPlatform $dbPlatform, Connection $connection)
    {
        $this->truncateTable($dbPlatform, $connection, 'OroSearchBundle:Item');
        $this->truncateTable($dbPlatform, $connection, 'OroSearchBundle:IndexDecimal');
        $this->truncateTable($dbPlatform, $connection, 'OroSearchBundle:IndexText');
        $this->truncateTable($dbPlatform, $connection, 'OroSearchBundle:IndexInteger');
        $this->truncateTable($dbPlatform, $connection, 'OroSearchBundle:IndexDatetime');
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
        $metadata = $this->em->getClassMetadata($entityName);
        $query = $this->getTruncateQuery($dbPlatform, $metadata->getTableName()) ;
        $connection->executeUpdate($query);
    }

    /**
     * @param AbstractPlatform $dbPlatform
     * @param string $tableName
     * @return string
     */
    protected function getTruncateQuery(AbstractPlatform $dbPlatform, $tableName)
    {
        return $dbPlatform->getTruncateTableSql($tableName);
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
    protected function addTextField(QueryBuilder $qb, $index, $searchCondition, $setOrderBy = true)
    {
        $useFieldName = $searchCondition['fieldName'] == '*' ? false : true;
        $fieldValue = $this->filterTextFieldValue($searchCondition['fieldValue']);

        // TODO Need to clarify search requirements in scope of CRM-214
        if ($searchCondition['condition'] == Query::OPERATOR_CONTAINS) {
            $searchString = $this->createContainsStringQuery($index, $useFieldName);
        } else {
            $searchString = $this->createNotContainsStringQuery($index, $useFieldName);
        }
        $whereExpr = $searchCondition['type'] . ' (' . $searchString . ')';

        $this->setFieldValueStringParameter($qb, $index, $fieldValue, $searchCondition['condition']);

        if ($useFieldName) {
            $qb->setParameter('field' . $index, $searchCondition['fieldName']);
        }

        if ($setOrderBy) {
            $this->setTextOrderBy($qb, $index);
        }

        return $whereExpr;
    }

    /**
     * @param array|string $fieldValue
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
    protected function addNonTextField(QueryBuilder $qb, $index, $searchCondition)
    {
        $joinAlias = $this->getJoinAlias($searchCondition['fieldType'], $index);
        $qb->setParameter('field' . $index, $searchCondition['fieldName']);
        $qb->setParameter('value' . $index, $searchCondition['fieldValue']);

        return $searchCondition['type'] . ' (' . $this->createNonTextQuery(
            $joinAlias,
            $index,
            $searchCondition['condition']
        ) . ')';
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
    protected function createNonTextQuery($joinAlias, $index, $condition)
    {
        if ($condition == Query::OPERATOR_IN) {
            $searchString
                = $joinAlias . '.field= :field' . $index . ' AND ' . $joinAlias . '.value ' . $condition . ' (:value'
                . $index . ')';
        } elseif ($condition == Query::OPERATOR_NOT_IN) {
            $searchString
                =
                $joinAlias . '.field= :field' . $index . ' AND ' . $joinAlias . '.value NOT IN (:value' . $index . ')';
        } else {
            $searchString
                = $joinAlias . '.field= :field' . $index . ' AND ' . $joinAlias . '.value ' . $condition . ' :value'
                . $index;
        }

        return $searchString;
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

        $this->setFrom($query, $qb);

        $whereExpr = array();
        if (count($query->getOptions())) {
            foreach ($query->getOptions() as $index => $searchCondition) {
                $joinField = sprintf('search.%sFields', $searchCondition['fieldType']);
                $joinAlias = $this->getJoinAlias($searchCondition['fieldType'], $index);
                $qb->leftJoin($joinField, $joinAlias);

                if ($searchCondition['fieldType'] == Query::TYPE_TEXT) {
                    if ($searchCondition['fieldValue'] === '') {
                        $whereExpr[] = $joinAlias . '.field = :field' . $index;
                        $qb->setParameter('field' . $index, $searchCondition['fieldName']);
                    } else {
                        $whereExpr[] = $this->addTextField($qb, $index, $searchCondition, $setOrderBy);
                    }
                } else {
                    $whereExpr[] = $this->addNonTextField($qb, $index, $searchCondition);
                }
            }
            if (substr($whereExpr[0], 0, 3) == 'and') {
                $whereExpr[0] = substr($whereExpr[0], 3, strlen($whereExpr[0]));
            }

            $qb->andWhere(implode(' ', $whereExpr));
        }

        if ($setOrderBy) {
            $this->addOrderBy($query, $qb);
        }

        return $qb;
    }

    /**
     * @param string $fieldType
     * @param int $index
     * @return string
     */
    protected function getJoinAlias($fieldType, $index)
    {
        return sprintf('%sField%s', $fieldType, $index);
    }

    /**
     * Set from parameters from search query
     *
     * @param \Oro\Bundle\SearchBundle\Query\Query $query
     * @param \Doctrine\ORM\QueryBuilder           $qb
     */
    protected function setFrom(Query $query, QueryBuilder $qb)
    {
        $useFrom = true;
        foreach ($query->getFrom() as $from) {
            if ($from == '*') {
                $useFrom = false;
            }
        }
        if ($useFrom) {
            $qb->andWhere($qb->expr()->in('search.alias', $query->getFrom()));
        }
    }

    /**
     * Set order by for search query
     *
     * @param \Oro\Bundle\SearchBundle\Query\Query $query
     * @param \Doctrine\ORM\QueryBuilder           $qb
     */
    protected function addOrderBy(Query $query, QueryBuilder $qb)
    {
        $orderBy = $query->getOrderBy();

        if ($orderBy) {
            $orderRelation = $query->getOrderType() . 'Fields';
            $qb->leftJoin('search.' . $orderRelation, 'orderTable', 'WITH', 'orderTable.field = :orderField')
                ->orderBy('orderTable.value', $query->getOrderDirection())
                ->setParameter('orderField', $orderBy);
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
