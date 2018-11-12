<?php

namespace Oro\Bundle\SearchBundle\Engine\Orm;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\SearchBundle\Engine\Indexer;
use Oro\Bundle\SearchBundle\Query\Criteria\Criteria;
use Oro\Bundle\SearchBundle\Query\Query;
use Oro\Component\DoctrineUtils\ORM\QueryBuilderUtil;

/**
 * Mysql DB driver used to run search queries for ORM search engine
 */
class PdoMysql extends BaseDriver
{
    const ENGINE_MYISAM = 'MyISAM';
    const ENGINE_INNODB = 'InnoDB';

    /**
     * The value of ft_min_word_len
     *
     * @var integer
     */
    protected $fullTextMinWordLength;

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
            'MATCH_AGAINST',
            'Oro\Bundle\SearchBundle\Engine\Orm\PdoMysql\MatchAgainst'
        );

        parent::initRepo($em, $class);
    }

    /**
     * Sql plain query to create fulltext index for mySql.
     *
     * @param string $tableName
     * @param string $indexName
     *
     * @return string
     */
    public static function getPlainSql($tableName = 'oro_search_index_text', $indexName = 'value')
    {
        return sprintf('ALTER TABLE `%s` ADD FULLTEXT `%s` (`value`)', $tableName, $indexName);
    }

    /**
     * Add text search to qb
     *
     * @param  QueryBuilder $qb
     * @param  string       $index
     * @param  array        $searchCondition
     * @param  boolean      $setOrderBy
     *
     * @return string
     */
    public function addTextField(QueryBuilder $qb, $index, $searchCondition, $setOrderBy = true)
    {
        $fieldValue = $searchCondition['fieldValue'];
        $condition = $searchCondition['condition'];

        $words = array_filter(
            explode(' ', $this->filterTextFieldValue($searchCondition['fieldName'], $fieldValue)),
            'strlen'
        );

        switch ($condition) {
            case Query::OPERATOR_LIKE:
                $whereExpr = $this->createLikeExpr($qb, $searchCondition['fieldValue'], $index);
                break;

            case Query::OPERATOR_NOT_LIKE:
                $whereExpr = $this->createNotLikeExpr($qb, $searchCondition['fieldValue'], $index);
                break;

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

            case Query::OPERATOR_STARTS_WITH:
                $whereExpr = $this->createStartWithExpr($qb, $fieldValue, $index, $searchCondition);
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
     * Uses whole string for like expression. Does not operate on words.
     *
     * @param QueryBuilder $qb
     * @param string $fieldValue
     * @param string $index
     *
     * @return string
     */
    protected function createLikeExpr(QueryBuilder $qb, $fieldValue, $index)
    {
        $this->setLikeExpParameters($qb, $fieldValue, $index);
        return parent::createContainsStringQuery($index, false);
    }

    /**
     * Uses whole string for not like expression. Does not operate on words.
     *
     * @param QueryBuilder $qb
     * @param string $fieldValue
     * @param string $index
     *
     * @return string
     */
    protected function createNotLikeExpr(QueryBuilder $qb, $fieldValue, $index)
    {
        $this->setLikeExpParameters($qb, $fieldValue, $index);
        return parent::createNotContainsStringQuery($index, false);
    }

    /**
     * @param QueryBuilder $qb
     * @param string $fieldValue
     * @param string $index
     */
    protected function setLikeExpParameters(QueryBuilder $qb, $fieldValue, $index)
    {
        $parameterName = 'value' . $index;
        $parameterValue = '%' . $fieldValue . '%';

        $qb->setParameter($parameterName, $parameterValue);
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
            $this->fullTextMinWordLength = (int)$this->entityManager->getConnection()->fetchColumn(
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
        QueryBuilderUtil::checkIdentifier($index);
        $joinAlias      = $this->getJoinAlias($searchCondition['fieldType'], $index);
        $fieldName      = $searchCondition['fieldName'];
        $fieldParameter = 'field' . $index;
        $valueParameter = 'value' . $index;
        QueryBuilderUtil::checkIdentifier($joinAlias);

        $result = "MATCH_AGAINST($joinAlias.value, :$valueParameter 'IN BOOLEAN MODE') > 0";
        if ($words) {
            $qb->setParameter($valueParameter, implode('* ', $words) . '*');
        } else {
            $qb->setParameter($valueParameter, '');
        }

        if ($this->isConcreteField($fieldName)) {
            $result = $qb->expr()->andX(
                $result,
                "$joinAlias.field = :$fieldParameter"
            );
            $qb->setParameter($fieldParameter, $fieldName);
        }

        if ($setOrderBy) {
            $qb->addSelect(
                sprintf('MATCH_AGAINST(%s.value, :value%s) * search.weight as rankField%s', $joinAlias, $index, $index)
            )
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
     * @param string $index
     * @param array       $searchCondition
     *
     * @return string
     */
    protected function createLikeWordsExpr(
        QueryBuilder $qb,
        array $words,
        $index,
        array $searchCondition
    ) {
        QueryBuilderUtil::checkIdentifier($index);
        $joinAlias  = $this->getJoinAlias($searchCondition['fieldType'], $index);
        $fieldName  = $searchCondition['fieldName'];
        QueryBuilderUtil::checkIdentifier($joinAlias);

        $result = $qb->expr()->orX();
        foreach (array_values($words) as $key => $value) {
            $valueParameter = 'value' . $index . '_w' . $key;
            QueryBuilderUtil::checkIdentifier($valueParameter);
            $result->add($qb->expr()->like($joinAlias. '.value', ':' . $valueParameter));
            $qb->setParameter($valueParameter, "%$value%");
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
     * @param  string       $index
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

        // Need to clarify requirements for "not contains" in scope of CRM-215
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
     * @param  string       $index
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
     * @param QueryBuilder $qb
     * @param string $fieldValue
     * @param string $index
     * @param array $searchCondition
     * @return string
     */
    public function createStartWithExpr(
        QueryBuilder $qb,
        $fieldValue,
        $index,
        array $searchCondition
    ) {
        $joinAlias = $this->getJoinAlias($searchCondition['fieldType'], $index);

        $valueParameter = 'value' . $index;
        $qb->setParameter($valueParameter, $fieldValue . '%');

        return "$joinAlias.value LIKE :$valueParameter";
    }

    /**
     * @param  array $fieldName
     *
     * @return bool
     */
    protected function isConcreteField($fieldName)
    {
        return $fieldName !== '*';
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

    /**
     * {@inheritdoc}
     */
    protected function getTruncateQuery(AbstractPlatform $dbPlatform, $tableName)
    {
        if ($this->em->getConnection()->isTransactionActive()) {
            return sprintf('DELETE FROM %s', $tableName);
        }

        return parent::getTruncateQuery($dbPlatform, $tableName);
    }
}
