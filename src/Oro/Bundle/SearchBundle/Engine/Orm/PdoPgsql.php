<?php
namespace Oro\Bundle\SearchBundle\Engine\Orm;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\ClassMetadata;

use Oro\Bundle\SearchBundle\Query\Criteria\Criteria;
use Oro\Bundle\SearchBundle\Query\Query;

class PdoPgsql extends BaseDriver
{
    public $columns = [];
    public $needle;
    public $mode;

    /**
     * Init additional doctrine functions
     *
     * @param \Doctrine\ORM\EntityManager         $em
     * @param \Doctrine\ORM\Mapping\ClassMetadata $class
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
     * Sql plain query to create fulltext index for Postgresql.
     *
     * @return string
     */
    public static function getPlainSql()
    {
        return "CREATE INDEX value ON oro_search_index_text USING gin(to_tsvector('english', 'value'))";
    }

    /**
     * Create fulltext search string for string parameters (contains)
     *
     * @param integer $index
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
     * @param integer                    $index
     * @param string                     $fieldValue
     * @param string                     $searchCondition
     */
    protected function setFieldValueStringParameter(QueryBuilder $qb, $index, $fieldValue, $searchCondition)
    {
        $notContains = !in_array($searchCondition, [Query::OPERATOR_CONTAINS, Query::OPERATOR_EQUALS], true);
        $searchArray = explode(Query::DELIMITER, $fieldValue);

        foreach ($searchArray as $key => $string) {
            $searchArray[$key] = $string . ':*';
        }

        if ($notContains) {
            foreach ($searchArray as $key => $string) {
                $searchArray[$key] = '!' . $string;
            }
            $qb->setParameter('value' . $index, implode(' & ', $searchArray));
        } else {
            $qb->setParameter('value' . $index, implode(' | ', $searchArray));
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
        $joinAlias = $this->getJoinAlias(Query::TYPE_TEXT, $index);

        $qb->addSelect(sprintf('TsRank(%s.value, :value%s) as rankField%s', $joinAlias, $index, $index))
            ->addOrderBy(sprintf('rankField%s', $index), Criteria::DESC);
    }

    /**
     * {@inheritdoc}
     */
    protected function addOrderBy(Query $query, QueryBuilder $qb)
    {
        parent::addOrderBy($query, $qb);

        // all columns from order part must be in select
        if ($query->getOrderBy()) {
            $qb->addSelect('orderTable.value');
        }
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
