<?php
namespace Oro\Bundle\SearchBundle\Engine\Orm;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\ClassMetadata;

use Oro\Bundle\SearchBundle\Engine\Indexer;
use Oro\Bundle\SearchBundle\Query\Criteria\Criteria;
use Oro\Bundle\SearchBundle\Query\Query;

class PdoMysql extends BaseDriver
{
    const ENGINE_MYISAM = 'MyISAM';

    /**
     * The value of ft_min_word_len
     *
     * @var integer
     */
    protected $fullTextMinWordLength;

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
            'MATCH_AGAINST',
            'Oro\Bundle\SearchBundle\Engine\Orm\PdoMysql\MatchAgainst'
        );

        parent::initRepo($em, $class);
    }

    /**
     * Sql plain query to create fulltext index for mySql.
     *
     * @return string
     */
    public static function getPlainSql()
    {
        return "ALTER TABLE `oro_search_index_text` ADD FULLTEXT `value` ( `value`)";
    }

    /**
     * Add text search to qb
     *
     * @param  QueryBuilder $qb
     * @param  integer      $index
     * @param  array        $searchCondition
     * @param  boolean      $setOrderBy
     *
     * @return string
     */
    public function addTextField(QueryBuilder $qb, $index, $searchCondition, $setOrderBy = true)
    {
        $fieldValue = $searchCondition['fieldValue'];
        $condition = $searchCondition['condition'];
        $words = $this->getWords($this->filterTextFieldValue($fieldValue), $condition);

        switch ($condition) {
            case Query::OPERATOR_CONTAINS:
                $whereExpr  = $this->createMatchAgainstWordsExpr($qb, $words, $index, $searchCondition, $setOrderBy);
                $shortWords = $this->getWordsLessThanFullTextMinWordLength($words);
                if ($shortWords) {
                    $whereExpr = $qb->expr()->orX(
                        $whereExpr,
                        $this->createLikeWordsExpr($qb, $shortWords, $index, $searchCondition)
                    );
                }
                break;

            case Query::OPERATOR_NOT_CONTAINS:
                $whereExpr = $this->createNotLikeWordsExpr($qb, $words, $index, $searchCondition);
                break;

            case Query::OPERATOR_EQUALS:
                $whereExpr = $this->createCompareStringExpr($qb, $fieldValue, $index, $searchCondition);
                break;

            default:
                $whereExpr = $this->createCompareStringExpr($qb, $fieldValue, $index, $searchCondition, '!=');
                break;
        }

        return '(' . $whereExpr . ')';
    }

    /**
     * Get array of words retrieved from $value string
     *
     * @param  string $value
     * @param  string $searchCondition
     *
     * @return array
     */
    protected function getWords($value, $searchCondition)
    {
        $results = array_filter(explode(' ', $value));
        $results = array_map(
            function ($word) use ($searchCondition) {
                if ($searchCondition === Query::OPERATOR_CONTAINS && filter_var($word, FILTER_VALIDATE_EMAIL)) {
                    $word = sprintf('"%s"', $word);
                }

                return $word;
            },
            $results
        );

        return $results;
    }

    /**
     * Get words that have length less than $this->fullTextMinWordLength
     *
     * @param  array $words
     *
     * @return array
     */
    protected function getWordsLessThanFullTextMinWordLength(array $words)
    {
        $length = $this->getFullTextMinWordLength();

        $words = array_filter(
            $words,
            function ($value) use ($length) {
                if (filter_var($value, FILTER_VALIDATE_INT)) {
                    return true;
                }

                return mb_strlen($value) < $length;
            }
        );

        return array_unique($words);
    }

    /**
     * @return int
     */
    protected function getFullTextMinWordLength()
    {
        if (null === $this->fullTextMinWordLength) {
            $this->fullTextMinWordLength = (int)$this->em->getConnection()->fetchColumn(
                "SHOW VARIABLES LIKE 'ft_min_word_len'",
                [],
                1
            );
        }

        return $this->fullTextMinWordLength;
    }

    /**
     * Creates expression like MATCH_AGAINST(textField.value, :value0 'IN BOOLEAN MODE') and adds parameters
     * to $qb.
     *
     * @param  QueryBuilder $qb
     * @param  array        $words
     * @param  string       $index
     * @param  array        $searchCondition
     * @param  bool         $setOrderBy
     *
     * @return string
     */
    protected function createMatchAgainstWordsExpr(
        QueryBuilder $qb,
        array $words,
        $index,
        array $searchCondition,
        $setOrderBy = true
    ) {
        $joinAlias      = $this->getJoinAlias($searchCondition['fieldType'], $index);
        $fieldName      = $searchCondition['fieldName'];
        $fieldParameter = 'field' . $index;
        $valueParameter = 'value' . $index;

        $result = "MATCH_AGAINST($joinAlias.value, :$valueParameter 'IN BOOLEAN MODE') > 0";
        $qb->setParameter($valueParameter, implode('* ', $words) . '*');

        if ($this->isConcreteField($fieldName)) {
            $result = $qb->expr()->andX(
                $result,
                "$joinAlias.field = :$fieldParameter"
            );
            $qb->setParameter($fieldParameter, $fieldName);
        }

        if ($setOrderBy) {
            $qb->addSelect(sprintf('MATCH_AGAINST(%s.value, :value%s) as rankField%s', $joinAlias, $index, $index))
                ->addOrderBy(sprintf('rankField%s', $index), Criteria::DESC);
        }

        return (string)$result;
    }

    /**
     * Creates expression like (textField.value LIKE :value0_w0 OR textField.value LIKE :value0_w1)
     * and adds parameters to $qb.
     *
     * @param QueryBuilder $qb
     * @param array        $words
     * @param              $index
     * @param  array       $searchCondition
     *
     * @return string
     */
    protected function createLikeWordsExpr(
        QueryBuilder $qb,
        array $words,
        $index,
        array $searchCondition
    ) {
        $joinAlias  = $this->getJoinAlias($searchCondition['fieldType'], $index);
        $fieldName  = $searchCondition['fieldName'];

        $result = $qb->expr()->orX();
        foreach (array_values($words) as $key => $value) {
            $valueParameter = 'value' . $index . '_w' . $key;
            $result->add("$joinAlias.value LIKE :$valueParameter");
            $qb->setParameter($valueParameter, $value . '%');
        }
        if ($this->isConcreteField($fieldName) && !$this->isAllDataField($fieldName)) {
            $fieldParameter = 'field' . $index;
            $result         = $qb->expr()->andX($result, "$joinAlias.field = :$fieldParameter");
            $qb->setParameter($fieldParameter, $fieldName);
        }

        return (string)$result;
    }

    /**
     * @param  QueryBuilder $qb
     * @param  int          $index
     * @param  array        $words
     * @param  array        $searchCondition
     *
     * @return string
     */
    protected function createNotLikeWordsExpr(
        QueryBuilder $qb,
        array $words,
        $index,
        array $searchCondition
    ) {
        $joinAlias      = $this->getJoinAlias($searchCondition['fieldType'], $index);
        $fieldName      = $searchCondition['fieldName'];
        $fieldParameter = 'field' . $index;
        $valueParameter = 'value' . $index;

        // TODO Need to clarify requirements for "not contains" in scope of CRM-215
        $qb->setParameter($valueParameter, '%' . implode('%', $words) . '%');

        $whereExpr = "$joinAlias.value NOT LIKE :$valueParameter";
        if ($this->isConcreteField($fieldName)) {
            $whereExpr .= " AND $joinAlias.field = :$fieldParameter";
            $qb->setParameter($fieldParameter, $fieldName);
        }

        return $whereExpr;
    }

    /**
     * @param  QueryBuilder $qb
     * @param  int          $index
     * @param  string       $value
     * @param  array        $searchCondition
     * @param  string       $operator
     *
     * @return string
     */
    public function createCompareStringExpr(
        QueryBuilder $qb,
        $value,
        $index,
        array $searchCondition,
        $operator = '='
    ) {
        $joinAlias      = $this->getJoinAlias($searchCondition['fieldType'], $index);
        $fieldName      = $searchCondition['fieldName'];
        $fieldParameter = 'field' . $index;
        $valueParameter = 'value' . $index;

        $qb->setParameter($valueParameter, $value);

        $whereExpr = "$joinAlias.value $operator :$valueParameter";
        if ($this->isConcreteField($fieldName)) {
            $whereExpr .= " AND $joinAlias.field = :$fieldParameter";
            $qb->setParameter($fieldParameter, $fieldName);
        }

        return $whereExpr;
    }

    /**
     * @param  array $fieldName
     *
     * @return bool
     */
    protected function isConcreteField($fieldName)
    {
        return $fieldName === '*' ? false : true;
    }

    /**
     * @param  array $fieldName
     *
     * @return bool
     */
    protected function isAllDataField($fieldName)
    {
        return $fieldName === Indexer::TEXT_ALL_DATA_FIELD;
    }

    /**
     * {@inheritdoc}
     */
    protected function truncateEntities(AbstractPlatform $dbPlatform, Connection $connection)
    {
        $connection->query('SET FOREIGN_KEY_CHECKS=0');

        parent::truncateEntities($dbPlatform, $connection);

        $connection->query('SET FOREIGN_KEY_CHECKS=1');
    }
}
