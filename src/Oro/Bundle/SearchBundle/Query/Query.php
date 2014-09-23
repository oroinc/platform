<?php

namespace Oro\Bundle\SearchBundle\Query;

use Doctrine\Common\Persistence\ObjectManager;
use Oro\Bundle\SearchBundle\Entity\IndexText;

class Query
{
    const SELECT = 'select';

    const ORDER_ASC = 'asc';
    const ORDER_DESC = 'desc';

    const KEYWORD_FROM = 'from';
    const KEYWORD_WHERE = 'where';
    const KEYWORD_AND = 'and';
    const KEYWORD_OR = 'or';
    const KEYWORD_OFFSET = 'offset';
    const KEYWORD_MAX_RESULTS = 'max_results';
    const KEYWORD_ORDER_BY = 'order_by';

    const OPERATOR_EQUALS = '=';
    const OPERATOR_NOT_EQUALS = '!=';
    const OPERATOR_GREATER_THAN = '>';
    const OPERATOR_GREATER_THAN_EQUALS = '>=';
    const OPERATOR_LESS_THAN = '<';
    const OPERATOR_LESS_THAN_EQUALS = '<=';
    const OPERATOR_CONTAINS = '~';
    const OPERATOR_NOT_CONTAINS = '!~';
    const OPERATOR_IN = 'in';
    const OPERATOR_NOT_IN = '!in';

    const TYPE_TEXT = 'text';
    const TYPE_INTEGER = 'integer';
    const TYPE_DATETIME = 'datetime';
    const TYPE_DECIMAL = 'decimal';

    const INFINITY = 10000000;
    const FINITY   = 0.000001;

    const DELIMITER = ' ';

    /**
     * @var array
     */
    protected $options;

    /**
     * @var  string
     */
    protected $query;

    /**
     * @var int
     */
    protected $maxResults;

    /**
     * @var int
     */
    protected $firstResult;

    /**
     * @var array
     */
    protected $from;

    /**
     * @var string
     */
    protected $orderType;

    /**
     * @var string
     */
    protected $orderBy;

    /**
     * @var string
     */
    protected $orderDirection;

    /**
     * @var array
     */
    protected $mappingConfig;

    /**
     * @var array
     */
    protected $fields;

    /**
     * @var ObjectManager
     */
    private $em;

    public function __construct($queryType = null)
    {
        if ($queryType) {
            $this->createQuery($queryType);
        }

        $this->options    = array();
        $this->maxResults = 0;
        $this->from       = false;
    }

    /**
     * Get entity class name from alias
     *
     * @param $aliasName
     *
     * @return bool|string
     */
    public function getEntityByAlias($aliasName)
    {
        foreach ($this->mappingConfig as $entity => $config) {
            if (isset($config['alias']) && $config['alias'] == $aliasName) {
                return $entity;
            }
        }

        return false;
    }

    /**
     * Set mapping config parameters
     *
     * @param array $mappingConfig
     */
    public function setMappingConfig($mappingConfig)
    {
        $fields = array();

        foreach ($mappingConfig as $entity => $config) {
            foreach ($config['fields'] as $field) {
                if (isset($field['relation_fields'])) {
                    $fields = $this->mapRelationFields($fields, $field, $entity);
                } elseif (isset($field['target_fields']) && count($field['target_fields']) > 0) {
                    $fields = $this->mapTargetFields($fields, $field, $entity);
                }
            }
        }

        $this->fields        = $fields;
        $this->mappingConfig = $mappingConfig;
    }

    /**
     * @param ObjectManager $em
     */
    public function setEntityManager(ObjectManager $em)
    {
        $this->em = $em;
    }

    /**
     * Init query
     *
     * @param string $query
     *
     * @return Query
     */
    public function createQuery($query)
    {
        $this->query = $query;

        return $this;
    }

    /**
     * Insert entities array to query from
     *
     * @param array|string $entities
     *
     * @return Query
     */
    public function from($entities)
    {
        if (!is_array($entities)) {
            $entities = [$entities];
        }

        $this->from = $entities;

        return $this;
    }

    /**
     * Add "AND WHERE" parameter
     *
     * @param string $fieldName
     * @param string $condition
     * @param string $fieldValue
     * @param string $fieldType
     *
     * @return Query
     */
    public function andWhere($fieldName, $condition, $fieldValue, $fieldType = self::TYPE_TEXT)
    {
        return $this->where(self::KEYWORD_AND, $fieldName, $condition, $fieldValue, $fieldType);
    }

    /**
     * Add "OR WHERE" parameter
     *
     * @param string $fieldName
     * @param string $condition
     * @param string $fieldValue
     * @param string $fieldType
     *
     * @return Query
     */
    public function orWhere($fieldName, $condition, $fieldValue, $fieldType = self::TYPE_TEXT)
    {
        return $this->where(self::KEYWORD_OR, $fieldName, $condition, $fieldValue, $fieldType);
    }

    /**
     * Add "WHERE" parameter
     *
     * @param string $keyWord
     * @param string $fieldName
     * @param string $condition
     * @param string $fieldValue
     * @param string $fieldType
     *
     * @return Query
     */
    public function where($keyWord, $fieldName, $condition, $fieldValue, $fieldType = self::TYPE_TEXT)
    {
        $this->options[] = [
            'fieldName'  => $fieldName,
            'condition'  => $condition,
            'fieldValue' => $fieldValue,
            'fieldType'  => $fieldType,
            'type'       => $keyWord
        ];

        return $this;
    }

    /**
     * @return string
     */
    public function getQuery()
    {
        return $this->query;
    }

    /**
     * Get entities to select from
     *
     * @return array
     */
    public function getFrom()
    {
        return $this->from;
    }

    /**
     * Get query options
     *
     * @return array
     */
    public function getOptions()
    {
        return $this->options;
    }

    /**
     * Return mapping config array
     *
     * @return array
     */
    public function getMappingConfig()
    {
        return $this->mappingConfig;
    }

    /**
     * Get field array
     *
     * @return array
     */
    public function getFields()
    {
        return $this->fields;
    }

    /**
     * Set max results
     *
     * @param int $maxResults
     *
     * @return Query
     */
    public function setMaxResults($maxResults)
    {
        $this->maxResults = (int) $maxResults;

        return $this;
    }

    /**
     * Get limit parameter
     *
     * @return int
     */
    public function getMaxResults()
    {
        return $this->maxResults;
    }

    /**
     * Set first result offset
     *
     * @param int $firstResult
     *
     * @return Query
     */
    public function setFirstResult($firstResult)
    {
        $this->firstResult = (int) $firstResult;

        return $this;
    }

    /**
     * Get first result offset
     *
     * @return int
     */
    public function getFirstResult()
    {
        return $this->firstResult;
    }

    /**
     * Set order by
     *
     * @param string $fieldName
     * @param string $direction
     * @param string $type
     *
     * @return Query
     */
    public function setOrderBy($fieldName, $direction = self::ORDER_ASC, $type = self::TYPE_TEXT)
    {
        $this->orderBy        = $fieldName;
        $this->orderDirection = $direction;
        $this->orderType      = $type;

        return $this;
    }

    /**
     * Get order by field
     *
     * @return string
     */
    public function getOrderBy()
    {
        return $this->orderBy;
    }

    /**
     * Get "order by" field type
     *
     * @return string
     */
    public function getOrderType()
    {
        return $this->orderType;
    }

    /**
     * Get order by direction
     *
     * @return string
     */
    public function getOrderDirection()
    {
        return $this->orderDirection;
    }

    /**
     * Clear string
     *
     * @param  string $inputString
     * @return string
     */
    public static function clearString($inputString)
    {
        return trim(
            preg_replace(
                '/ +/',
                self::DELIMITER,
                preg_replace('/[^\w:*]/u', self::DELIMITER, $inputString)
            )
        );
    }

    /**
     * @param array  $fields
     * @param array  $field
     * @param string $entity
     * @return array
     */
    private function mapTargetFields($fields, $field, $entity)
    {
        foreach ($field['target_fields'] as $targetFields) {
            if (!isset($fields[$targetFields]) || !in_array($entity, $fields[$targetFields])) {
                $fields[$targetFields][] = $entity;
            }
        }

        return $fields;
    }

    /**
     * @param array  $fields
     * @param array  $field
     * @param string $entity
     * @return array
     */
    private function mapRelationFields($fields, $field, $entity)
    {
        foreach ($field['relation_fields'] as $relationField) {
            if (isset($relationField['target_fields']) && count($relationField['target_fields']) > 0) {
                foreach ($relationField['target_fields'] as $targetFields) {
                    if (!isset($fields[$targetFields]) || !in_array($entity, $fields[$targetFields])) {
                        $fields[$targetFields][] = $entity;
                    }
                }
            }
        }

        return $fields;
    }
}
