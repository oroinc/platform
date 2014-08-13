<?php
namespace Oro\Bundle\SearchBundle\Engine\Orm;

use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\ClassMetadata;

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
        return "CREATE INDEX string_fts ON oro_search_index_text USING gin(to_tsvector('english', 'value'))";
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
        $stringQuery = '(TsvectorTsquery(textField.value, :non_value' . $index . ')) = TRUE';

        if ($useFieldName) {
            $stringQuery .= ' AND textField.field = :field' . $index;
        }

        $stringQuery .= ' AND TsRank(textField.value, :value' . $index . ') > ' . Query::FINITY;

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
        $stringQuery = '(TsvectorTsquery(textField.value, :value' . $index . ')) = TRUE';

        if ($useFieldName) {
            $stringQuery .= ' AND textField.field = :field' . $index;
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
        $notContains = $searchCondition != Query::OPERATOR_CONTAINS;
        $searchArray = explode(Query::DELIMITER, $fieldValue);

        foreach ($searchArray as $key => $string) {
            $searchArray[$key] = $string . ':*';
        }

        if ($notContains) {
            foreach ($searchArray as $key => $string) {
                $searchArray[$key] = '!' . $string;
            }
        }

        $qb->setParameter('value' . $index, implode(' & ', $searchArray));

        if (!$notContains) {
            $qb->setParameter('non_value' . $index, implode(' | ', $searchArray));
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
        $qb->select(
            [
                'search as item',
                'textField',
                'TsRank(textField.value, :value' . $index . ') AS rankField'
            ]
        );
        $qb->orderBy('rankField', 'DESC');
    }
}
