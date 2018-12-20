<?php

namespace Oro\Bundle\SearchBundle\Engine\Orm;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\SearchBundle\Query\Criteria\Criteria;
use Oro\Bundle\SearchBundle\Query\Query;

/**
 * PostgreSQL DB driver used to run search queries for ORM search engine
 */
class PdoPgsql extends BaseDriver
{
    /** @var array */
    public $columns = [];

    /** @var string */
    public $needle;

    /** @var string */
    public $mode;

    /**
     * Init additional doctrine functions
     *
     * @param EntityManagerInterface $em
     * @param ClassMetadata $class
     */
    public function initRepo(EntityManagerInterface $em, ClassMetadata $class)
    {
        $ormConfig = $em->getConfiguration();
        $ormConfig->addCustomStringFunction(
            'TsvectorTsquery',
            'Oro\Bundle\SearchBundle\Engine\Orm\PdoPgsql\TsvectorTsquery'
        );
        $ormConfig->addCustomStringFunction('TsRank', 'Oro\Bundle\SearchBundle\Engine\Orm\PdoPgsql\TsRank');

        parent::initRepo($em, $class);
    }

    /**
     * Sql plain query to create fulltext index for Postgresql.
     *
     * @param string $tableName
     * @param string $indexName
     *
     * @return string
     */
    public static function getPlainSql($tableName = 'oro_search_index_text', $indexName = 'value')
    {
        return sprintf('CREATE INDEX %s ON %s USING gin(to_tsvector(\'english\', value))', $indexName, $tableName);
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
    public function addTextField(QueryBuilder $qb, $index, $searchCondition, $setOrderBy = true)
    {
        $useFieldName = $searchCondition['fieldName'] !== '*';
        $condition = $searchCondition['condition'];

        $fieldValue = $this->filterTextFieldValue($searchCondition['fieldName'], $searchCondition['fieldValue']);

        switch ($condition) {
            case Query::OPERATOR_LIKE:
                $searchString = parent::createContainsStringQuery($index, $useFieldName);
                $setOrderBy = false;
                break;

            case Query::OPERATOR_NOT_LIKE:
                $searchString = parent::createNotContainsStringQuery($index, $useFieldName);
                $setOrderBy = false;
                break;

            case Query::OPERATOR_CONTAINS:
                $searchString = $this->createContainsStringQuery($index, $useFieldName);
                break;

            case Query::OPERATOR_NOT_CONTAINS:
                $searchString = $this->createNotContainsStringQuery($index, $useFieldName);
                break;

            case Query::OPERATOR_STARTS_WITH:
                $searchString = $this->createStartWithStringQuery($index, $useFieldName);
                break;

            case Query::OPERATOR_EQUALS:
                $searchString = $this->createCompareStringQuery($index, $useFieldName);
                break;

            default:
                $searchString = $this->createCompareStringQuery($index, $useFieldName, '!=');
                break;
        }

        $this->setFieldValueStringParameter($qb, $index, $fieldValue, $condition);

        if ($useFieldName) {
            $qb->setParameter('field' . $index, $searchCondition['fieldName']);
        }

        if ($setOrderBy) {
            $this->setTextOrderBy($qb, $index);
        }

        return '(' . $searchString . ' ) ';
    }

    /**
     * Create search string for string parameters (contains)
     *
     * @param string $index
     * @param bool    $useFieldName
     * @param string  $operator
     *
     * @return string
     */
    protected function createCompareStringQuery($index, $useFieldName = true, $operator = '=')
    {
        $joinAlias = $this->getJoinAlias(Query::TYPE_TEXT, $index);

        $stringQuery = '';
        if ($useFieldName) {
            $stringQuery = $joinAlias . '.field = :field' . $index . ' AND ';
        }

        return $stringQuery . $joinAlias . '.value ' . $operator . ' :value' . $index;
    }

    /**
     * Create fulltext search string for string parameters (contains)
     *
     * @param string $index
     * @param bool    $useFieldName
     *
     * @return string
     */
    protected function createContainsStringQuery($index, $useFieldName = true)
    {
        $joinAlias = $this->getJoinAlias(Query::TYPE_TEXT, $index);

        $stringQuery = '(TsvectorTsquery(' . $joinAlias . '.value, :value' . $index . ')) = TRUE';

        if ($useFieldName) {
            $stringQuery .= ' AND ' . $joinAlias . '.field = :field' . $index;
        }

        $stringQuery .= ' AND TsRank(' . $joinAlias . '.value, :value' . $index . ') > ' . Query::FINITY;

        return $stringQuery;
    }

    /**
     * @param QueryBuilder $qb
     * @param string $index
     * @param string $fieldValue
     * @param bool $isOrderBy
     */
    protected function createContainsQueryParameter(QueryBuilder $qb, $index, $fieldValue, $isOrderBy)
    {
        $searchArray = explode(Query::DELIMITER, $fieldValue);

        foreach ($searchArray as $key => $string) {
            $searchArray[$key] = $string . ':*';
        }

        $stringParameter = implode(' | ', $searchArray);

        $qb->setParameter('value' . $index, $stringParameter);

        if ($isOrderBy) {
            $qb->setParameter('orderByValue' . $index, $stringParameter);
        }
    }

    /**
     * @param QueryBuilder $qb
     * @param string $index
     * @param string $fieldValue
     * @param bool $isOrderBy
     */
    protected function createNotContainsQueryParameter(QueryBuilder $qb, $index, $fieldValue, $isOrderBy)
    {
        $searchArray = explode(Query::DELIMITER, $fieldValue);

        foreach ($searchArray as $key => $string) {
            $searchArray[$key] = '!' . $string;
        }

        $stringParameter = implode(' & ', $searchArray);

        $qb->setParameter('value' . $index, $stringParameter);

        if ($isOrderBy) {
            $qb->setParameter('orderByValue' . $index, $stringParameter);
        }
    }

    /**
     * Create search string for string parameters (not contains)
     *
     * @param string $index
     * @param bool    $useFieldName
     *
     * @return string
     */
    protected function createNotContainsStringQuery($index, $useFieldName = true)
    {
        $joinAlias = $this->getJoinAlias(Query::TYPE_TEXT, $index);

        $stringQuery = '(TsvectorTsquery(' . $joinAlias . '.value, :value' . $index . ')) = TRUE';

        if ($useFieldName) {
            $stringQuery .= ' AND ' . $joinAlias . '.field = :field' . $index;
        }

        return $stringQuery;
    }

    /**
     * Set string parameter for qb
     *
     * @param \Doctrine\ORM\QueryBuilder $qb
     * @param string                     $index
     * @param string                     $fieldValue
     * @param string                     $searchCondition
     */
    protected function setFieldValueStringParameter(QueryBuilder $qb, $index, $fieldValue, $searchCondition)
    {
        if (in_array($searchCondition, [Query::OPERATOR_CONTAINS, Query::OPERATOR_NOT_CONTAINS], true) && $fieldValue) {
            $searchArray = explode(Query::DELIMITER, $fieldValue);

            foreach ($searchArray as $key => $string) {
                $searchArray[$key] = $string . ':*';
            }

            if ($searchCondition === Query::OPERATOR_NOT_CONTAINS) {
                foreach ($searchArray as $key => $string) {
                    $searchArray[$key] = '!' . $string;
                }
                $qb->setParameter('value' . $index, implode(' & ', $searchArray));
            } else {
                $qb->setParameter('value' . $index, implode(' | ', $searchArray));
            }
        } elseif ($searchCondition === Query::OPERATOR_STARTS_WITH) {
            $qb->setParameter('value' . $index, $fieldValue . '%');
        } elseif ($searchCondition === Query::OPERATOR_LIKE || $searchCondition === Query::OPERATOR_NOT_LIKE) {
            $qb->setParameter('value' . $index, '%' . $fieldValue . '%');
        } else {
            $qb->setParameter('value' . $index, $fieldValue);
        }
    }

    /**
     * Set fulltext range order by
     *
     * @param QueryBuilder $qb
     * @param string $index
     */
    protected function setTextOrderBy(QueryBuilder $qb, $index)
    {
        $joinAlias = $this->getJoinAlias(Query::TYPE_TEXT, $index);

        $qb->addSelect(
            sprintf('TsRank(%s.value, :quotedValue%s) * search.weight as rankField%s', $joinAlias, $index, $index)
        )
           ->addOrderBy(sprintf('rankField%s', $index), Criteria::DESC);

        $parameter = $qb->getParameter(sprintf('value%s', $index));
        $quotedValue = sprintf('\'%s\'', $parameter->getValue());

        $qb->setParameter(sprintf('quotedValue%s', $index), $quotedValue);
    }

    /**
     * {@inheritdoc}
     */
    protected function getTruncateQuery(AbstractPlatform $dbPlatform, $tableName)
    {
        $query = parent::getTruncateQuery($dbPlatform, $tableName);

        // cascade required to perform truncate of related entities
        if (strpos($query, ' CASCADE') === false) {
            $query .= ' CASCADE';
        }

        return $query;
    }

    /**
     * @param string $index
     * @param bool $useFieldName
     * @return string
     */
    public function createEqualsStringQuery($index, $useFieldName)
    {
        $joinAlias = $this->getJoinAlias(Query::TYPE_TEXT, $index);

        $stringQuery = $joinAlias . '.value = :equals' . $index;

        if ($useFieldName) {
            $stringQuery .= ' AND ' . $joinAlias . '.field = :field' . $index;
        }

        return $stringQuery;
    }

    /**
     * @param QueryBuilder $qb
     * @param string $index
     * @param string $fieldValue
     * @param bool $isOrderBy
     */
    public function createEqualsQueryParameter(QueryBuilder $qb, $index, $fieldValue, $isOrderBy)
    {
        $qb->setParameter('equals' . $index, $fieldValue);

        if ($isOrderBy) {
            $qb->setParameter('orderByValue' . $index, $fieldValue);
        }
    }

    /**
     * @param string $index
     * @param bool $useFieldName
     * @return string
     */
    protected function createStartWithStringQuery($index, $useFieldName)
    {
        $joinAlias = $this->getJoinAlias(Query::TYPE_TEXT, $index);

        $stringQuery = $joinAlias . '.value LIKE :value' . $index;

        if ($useFieldName) {
            $stringQuery .= ' AND ' . $joinAlias . '.field = :field' . $index;
        }

        return $stringQuery;
    }
}
