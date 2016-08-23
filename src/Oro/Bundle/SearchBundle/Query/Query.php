<?php

namespace Oro\Bundle\SearchBundle\Query;

use Doctrine\Common\Persistence\ObjectManager;

use Oro\Bundle\SearchBundle\Query\Criteria\Criteria;
use Oro\Bundle\SearchBundle\Exception\ExpressionSyntaxError;

/**
 * @TODO: In platform 2.0 this class should be extended from the Doctrine\Common\Collections\Criteria.
 *        We should refactor this class only from platform v2.0 because it will break backward compatibility.
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
class Query
{
    const ORDER_ASC  = 'asc';
    const ORDER_DESC = 'desc';

    const KEYWORD_SELECT      = 'select';
    const KEYWORD_FROM        = 'from';
    const KEYWORD_WHERE       = 'where';
    const KEYWORD_AND         = 'and';
    const KEYWORD_OR          = 'or';
    const KEYWORD_OFFSET      = 'offset';
    const KEYWORD_MAX_RESULTS = 'max_results';
    const KEYWORD_ORDER_BY    = 'order_by';

    const OPERATOR_EQUALS              = '=';
    const OPERATOR_NOT_EQUALS          = '!=';
    const OPERATOR_GREATER_THAN        = '>';
    const OPERATOR_GREATER_THAN_EQUALS = '>=';
    const OPERATOR_LESS_THAN           = '<';
    const OPERATOR_LESS_THAN_EQUALS    = '<=';
    const OPERATOR_CONTAINS            = '~';
    const OPERATOR_NOT_CONTAINS        = '!~';
    const OPERATOR_IN                  = 'in';
    const OPERATOR_NOT_IN              = '!in';

    const TYPE_TEXT     = 'text';
    const TYPE_INTEGER  = 'integer';
    const TYPE_DATETIME = 'datetime';
    const TYPE_DECIMAL  = 'decimal';

    const INFINITY = 10000000;
    const FINITY   = 0.000001;

    const DELIMITER = ' ';

    /** @var array */
    protected $select = [];

    /** @var array */
    protected $from;

    /** @var array */
    protected $mappingConfig;

    /** @var array */
    protected $fields;

    /** @var Criteria */
    protected $criteria;

    /**
     */
    public function __construct()
    {
        $this->maxResults = 0;
        $this->from       = false;

        $this->criteria = Criteria::create();

        $this->criteria->setMaxResults(0);
    }

    /**
     * @return Criteria
     */
    public function getCriteria()
    {
        return $this->criteria;
    }

    /**
     * @param Criteria $criteria
     */
    public function setCriteria(Criteria $criteria)
    {
        $this->criteria = $criteria;
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
        $fields = [];

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
     * Insert list of required fields to query select
     *
     * @param mixed  $field
     *
     * @param string $enforcedFieldType
     *
     * @return Query
     */
    public function select($field, $enforcedFieldType = null)
    {
        $this->select = [];

        if (is_array($field)) {
            foreach ($field as $_field) {
                $this->addSelect($_field, $enforcedFieldType);
            }

            return $this;
        }

        $this->addSelect($field, $enforcedFieldType);

        return $this;
    }

    /**
     * @param string $fieldName
     * @param string $enforcedFieldType
     * @return $this
     */
    public function addSelect($fieldName, $enforcedFieldType = null)
    {
        $fieldType = self::TYPE_TEXT;

        list($explodedType, $explodedName) = Criteria::explodeFieldTypeName($fieldName);

        if (!empty($explodedType) && !empty($explodedName)) {
            $fieldType = $explodedType;
            $fieldName = $explodedName;
        }

        if ($enforcedFieldType !== null) {
            $fieldType = $enforcedFieldType;
        }

        $field = Criteria::implodeFieldTypeName($fieldType, $fieldName);

        if (!is_string($field)) {
            return $this;
        }

        $this->select[$field] = $field; // do not allow repeating fields

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
     * @deprecated Since 1.8 use criteria to add conditions
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
     * @deprecated Since 1.8 use criteria to add conditions
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
     * @deprecated Since 1.8 use criteria to add conditions
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
        $expr      = Criteria::expr();
        $fieldName = Criteria::implodeFieldTypeName($fieldType, $fieldName);

        switch ($condition) {
            case self::OPERATOR_CONTAINS:
                $expr = $expr->contains($fieldName, $fieldValue);
                break;
            case self::OPERATOR_NOT_CONTAINS:
                $expr = $expr->notContains($fieldName, $fieldValue);
                break;
            case self::OPERATOR_EQUALS:
                $expr = $expr->eq($fieldName, $fieldValue);
                break;
            case self::OPERATOR_NOT_EQUALS:
                $expr = $expr->neq($fieldName, $fieldValue);
                break;
            case self::OPERATOR_GREATER_THAN:
                $expr = $expr->gt($fieldName, $fieldValue);
                break;
            case self::OPERATOR_GREATER_THAN_EQUALS:
                $expr = $expr->gte($fieldName, $fieldValue);
                break;
            case self::OPERATOR_LESS_THAN:
                $expr = $expr->lt($fieldName, $fieldValue);
                break;
            case self::OPERATOR_LESS_THAN_EQUALS:
                $expr = $expr->lte($fieldName, $fieldValue);
                break;

            case self::OPERATOR_IN:
                $expr = $expr->in($fieldName, $fieldValue);
                break;
            case self::OPERATOR_NOT_IN:
                $expr = $expr->notIn($fieldName, $fieldValue);
                break;
            default:
                throw new ExpressionSyntaxError(
                    sprintf('Unsupported operator "%s"', $condition)
                );
        }

        if ($keyWord === self::KEYWORD_AND) {
            $this->criteria->andWhere($expr);
        } else {
            $this->criteria->orWhere($expr);
        }

        return $this;
    }

    /**
     * Get fields to select
     *
     * @return array
     */
    public function getSelect()
    {
        $result = array_values($this->select);

        return $result;
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
     * @deprecated Since 1.8 use getCriteria method
     * @throws \Exception
     */
    public function getOptions()
    {
        throw new \Exception('Method getOptions is depricated for Query class. Please use getCriteria method');
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
     * @deprecated Since 1.8 use criteria's setMaxResults method
     *
     * @param int $maxResults
     *
     * @return Query
     */
    public function setMaxResults($maxResults)
    {
        $this->criteria->setMaxResults((int)$maxResults);

        return $this;
    }

    /**
     * Get limit parameter
     *
     * @deprecated Since 1.8 use criteria's getMaxResults method
     *
     * @return int
     */
    public function getMaxResults()
    {
        return $this->criteria->getMaxResults();
    }

    /**
     * Set first result offset
     *
     * @deprecated Since 1.8 use criteria's setFirstResult method
     *
     * @param int $firstResult
     *
     * @return Query
     */
    public function setFirstResult($firstResult)
    {
        $this->criteria->setFirstResult((int)$firstResult);

        return $this;
    }

    /**
     * Get first result offset
     *
     * @deprecated Since 1.8 use criteria's getFirstResult method
     *
     * @return int
     */
    public function getFirstResult()
    {
        return $this->criteria->getFirstResult();
    }

    /**
     * Set order by
     *
     * @deprecated Since 1.8 use criteria's orderBy method
     *
     * @param string $fieldName
     * @param string $direction
     * @param string $type
     *
     * @return Query
     */
    public function setOrderBy($fieldName, $direction = self::ORDER_ASC, $type = self::TYPE_TEXT)
    {
        $this->criteria->orderBy([$type . '.' . $fieldName => $direction]);

        return $this;
    }

    /**
     * Get order by field
     *
     * @deprecated Since 1.8 use criteria's getOrderings method
     *
     * @return string
     */
    public function getOrderBy()
    {
        $orders    = array_keys($this->criteria->getOrderings());
        $fieldName = array_pop($orders);

        return Criteria::explodeFieldTypeName($fieldName)[1];
    }

    /**
     * Get "order by" field type
     *
     * @deprecated Since 1.8 use criteria's getOrderings method
     *
     * @return string
     */
    public function getOrderType()
    {
        $orders    = array_keys($this->criteria->getOrderings());
        $fieldName = array_pop($orders);

        return Criteria::explodeFieldTypeName($fieldName)[0];
    }

    /**
     * Get order by direction
     *
     * @deprecated Since 1.8 use criteria's getOrderings method
     *
     * @return string
     */
    public function getOrderDirection()
    {
        $orders = $this->criteria->getOrderings();

        return array_pop($orders);
    }

    /**
     * Clear string
     *
     * @param  string $inputString
     *
     * @return string
     */
    public static function clearString($inputString)
    {
        $string = trim(
            preg_replace(
                '/ +/',
                self::DELIMITER,
                preg_replace('/[^\w:*@.]/u', self::DELIMITER, $inputString)
            )
        );

        $fullString = str_replace(self::DELIMITER, '', $string);
        if (filter_var($fullString, FILTER_VALIDATE_INT)) {
            return $fullString;
        }

        return $string;
    }

    /**
     * Returns string representation of the query
     *
     * @return string
     */
    public function getStringQuery()
    {
        $fromString = '';

        if ($this->getFrom()) {
            $fromString .= 'from ' . implode(', ', $this->getFrom());
        }

        $whereString = $this->getWhereString();

        $orderByString = '';
        if ($this->getOrderBy()) {
            $orderByString .= ' ' . $this->getOrderBy();
        }
        if ($this->getOrderDirection()) {
            $orderByString .= ' ' . $this->getOrderDirection();
        }
        if ($orderByString) {
            $orderByString = ' order by' . $orderByString;
        }

        $limitString = '';
        if ($this->getMaxResults() && $this->getMaxResults() != Query::INFINITY) {
            $limitString = ' limit ' . $this->getMaxResults();
        }

        $offsetString = '';
        if ($this->getFirstResult()) {
            $offsetString .= ' offset ' . $this->getFirstResult();
        }

        $selectColumnsString = $this->getStringColumns();

        $selectString = '';
        if (!empty($selectColumnsString)) {
            $selectString = trim('select ' . $selectColumnsString) . ' ';
        }

        return $selectString
               . $fromString
               . $whereString
               . $orderByString
               . $limitString
               . $offsetString;
    }

    /**
     * Returns the WHERE string part for getStringQuery.
     *
     * @return string
     */
    private function getWhereString()
    {
        $whereString = '';
        if (null !== $whereExpr = $this->criteria->getWhereExpression()) {
            $visitor     = new QueryStringExpressionVisitor();
            $whereString = ' where ' . $whereExpr->visit($visitor);
        }

        return $whereString;
    }

    /**
     * @return string
     */
    private function getStringColumns()
    {
        $selects = $this->select;

        if (empty($selects)) {
            return '';
        }

        $result = implode(', ', $selects);

        if (count($selects) > 1) {
            $result = '(' . $result . ')';
        }

        return $result;
    }

    /**
     * @param array  $fields
     * @param array  $field
     * @param string $entity
     *
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
     *
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
