<?php
namespace Oro\Bundle\SearchBundle\Engine\Orm;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\QueryBuilder;

use Oro\Bundle\SearchBundle\Query\Criteria\Criteria;
use Oro\Bundle\SearchBundle\Query\Query;

class PdoPgsql extends BaseDriver
{
    public $columns = [];
    public $needle;
    public $mode;

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
     * Init additional doctrine functions
     *
     * @param EntityManager $em
     * @param ClassMetadata $class
     */
    public function initRepo(EntityManager $em, ClassMetadata $class)
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
     * {@inheritdoc}
     */
    public function addTextField(QueryBuilder $qb, $index, $searchCondition, $setOrderBy = true)
    {
        $useFieldName = $searchCondition['fieldName'] == '*' ? false : true;
        $fieldValue = $this->filterTextFieldValue($searchCondition['fieldValue']);

        // TODO Need to clarify search requirements in scope of CRM-214
        if ($searchCondition['condition'] === Query::OPERATOR_CONTAINS) {
            $searchString = $this->createContainsStringQuery($index, $useFieldName);
            $this->createContainsQueryParameter($qb, $index, $fieldValue, $setOrderBy);
        } elseif ($searchCondition['condition'] === Query::OPERATOR_EQUALS) {
            $searchString = $this->createEqualsStringQuery($index, $useFieldName);
            $this->createEqualsQueryParameter($qb, $index, $fieldValue, $setOrderBy);
        } elseif ($searchCondition['condition'] === Query::OPERATOR_STARTS_WITH) {
            $searchString = $this->createStartWithStringQuery($index, $useFieldName);
            $this->createStartWithStringParameter($qb, $index, $fieldValue, $setOrderBy);
        } else {
            $searchString = $this->createNotContainsStringQuery($index, $useFieldName);
            $this->createNotContainsQueryParameter($qb, $index, $fieldValue, $setOrderBy);
        }

        if ($useFieldName) {
            $qb->setParameter('field' . $index, $searchCondition['fieldName']);
        }

        if ($setOrderBy) {
            $this->setTextOrderBy($qb, $index);
        }

        return '(' . $searchString . ' ) ';
    }

    /**
     * Create fulltext search string for string parameters (contains)
     *
     * @param integer $index
     * @param bool $useFieldName
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

        $stringQuery = $joinAlias . '.value LIKE :startWithValue' . $index;

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
    protected function createStartWithStringParameter(QueryBuilder $qb, $index, $fieldValue, $isOrderBy)
    {
        $qb->setParameter('startWithValue' . $index, $fieldValue . '%');

        if ($isOrderBy) {
            $qb->setParameter('orderByValue' . $index, $fieldValue);
        }
    }

    /**
     * Create search string for string parameters (not contains)
     *
     * @param integer $index
     * @param bool $useFieldName
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
     * Set fulltext range order by
     *
     * @param QueryBuilder $qb
     * @param int $index
     */
    protected function setTextOrderBy(QueryBuilder $qb, $index)
    {
        $joinAlias = $this->getJoinAlias(Query::TYPE_TEXT, $index);

        $qb->addSelect(sprintf('TsRank(%s.value, :orderByValue%s) as rankField%s', $joinAlias, $index, $index))
            ->addOrderBy(sprintf('rankField%s', $index), Criteria::DESC);
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
}
