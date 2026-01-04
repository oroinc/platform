<?php

namespace Oro\Bundle\SearchBundle\Query;

use Oro\Bundle\SearchBundle\Query\Criteria\Criteria;

/**
 * Encapsulates query to search engine using SQL based format
 *
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
class Query
{
    public const ORDER_ASC = 'asc';
    public const ORDER_DESC = 'desc';

    public const KEYWORD_SELECT = 'select';
    public const KEYWORD_FROM = 'from';
    public const KEYWORD_WHERE = 'where';
    public const KEYWORD_AND = 'and';
    public const KEYWORD_OR = 'or';
    public const KEYWORD_OFFSET = 'offset';
    public const KEYWORD_MAX_RESULTS = 'max_results';
    public const KEYWORD_AGGREGATE = 'aggregate';
    public const KEYWORD_ORDER_BY = 'order_by';
    public const KEYWORD_AS = 'as';

    public const OPERATOR_EQUALS = '=';
    public const OPERATOR_NOT_EQUALS = '!=';
    public const OPERATOR_GREATER_THAN = '>';
    public const OPERATOR_GREATER_THAN_EQUALS = '>=';
    public const OPERATOR_LESS_THAN = '<';
    public const OPERATOR_LESS_THAN_EQUALS = '<=';
    public const OPERATOR_CONTAINS = '~';
    public const OPERATOR_NOT_CONTAINS = '!~';
    public const OPERATOR_IN = 'in';
    public const OPERATOR_NOT_IN = '!in';
    public const OPERATOR_STARTS_WITH = 'starts_with';
    public const OPERATOR_EXISTS = 'exists';
    public const OPERATOR_NOT_EXISTS = 'notexists';
    public const OPERATOR_LIKE = 'like';
    public const OPERATOR_NOT_LIKE = 'notlike';

    public const TYPE_TEXT = 'text';
    public const TYPE_INTEGER = 'integer';
    public const TYPE_DATETIME = 'datetime';
    public const TYPE_DECIMAL = 'decimal';

    public const INFINITY = 10000000;
    public const FINITY = 0.000001;

    public const AGGREGATE_FUNCTION_COUNT = 'count';
    public const AGGREGATE_FUNCTION_SUM = 'sum';
    public const AGGREGATE_FUNCTION_MAX = 'max';
    public const AGGREGATE_FUNCTION_MIN = 'min';
    public const AGGREGATE_FUNCTION_AVG = 'avg';

    public const AGGREGATE_PARAMETER_MAX = 'max';

    public const DELIMITER = ' ';

    /**
     * Indicates type of search query
     */
    public const HINT_SEARCH_TYPE = 'search_type';

    /**
     * Stores search term
     */
    public const HINT_SEARCH_TERM = 'search_term';

    /**
     * Stores search term
     */
    public const HINT_SEARCH_SESSION = 'search_session';

    /** @var array */
    protected $select = [];

    /** @var array */
    protected $from;

    /** @var array */
    protected $mappingConfig;

    /** @var array */
    protected $fields;

    /** @var array */
    protected $selectAliases = [];

    /** @var Criteria */
    protected $criteria;

    /** @var array */
    protected $aggregations = [];

    /**
     * The map of query hints.
     *
     * @var array
     */
    protected $hints = [];

    public function __construct()
    {
        $this->from = false;

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

        $this->fields = $fields;
        $this->mappingConfig = $mappingConfig;
    }

    /**
     * Insert list of required fields to query select
     *
     * @param mixed $field
     *
     * @param string $enforcedFieldType
     *
     * @return Query
     */
    public function select($field, $enforcedFieldType = null)
    {
        $this->select = $this->selectAliases = [];

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
     * @param string|string[] $fieldNames
     * @param string|null $enforcedFieldType
     * @return $this
     */
    public function addSelect($fieldNames, $enforcedFieldType = null)
    {
        if ($fieldNames) {
            foreach ((array)$fieldNames as $fieldName) {
                $fieldName = $this->parseFieldAliasing($fieldName, $enforcedFieldType);

                $this->addToSelect($fieldName, $enforcedFieldType);
            }
        }

        return $this;
    }

    /**
     * @param string|string[] $entities
     * @return $this
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
     * Get fields to select
     *
     * @return array
     */
    public function getSelect()
    {
        return array_values($this->select);
    }

    /**
     * Get entity aliases to select from
     *
     * @return array
     */
    public function getFrom()
    {
        return $this->from;
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
     * Clear string
     *
     * @param string $inputString
     *
     * @return string
     */
    public static function clearString($inputString)
    {
        /**
         * Replace all not not supported characters with whitespaces to support both MyISAM and InnoDB
         *
         * /[^\p{L}\d\s]/u <- returns all characters that are not:
         * p{L} <- letter in any unicode language
         * \d <- digit
         * \s <- whitespace
         *
         * /[\s]{2,}/ <-- returns all multi-spaces
         */
        return trim(
            preg_replace(
                '/[\s]{2,}/',
                self::DELIMITER,
                preg_replace('/[^\p{L}\d\s]/u', self::DELIMITER, (string)$inputString)
            )
        );
    }

    /**
     * Returns string representation of the query
     *
     * @return string
     */
    public function getStringQuery()
    {
        $fromString = '';
        $from = $this->getFrom();
        if ($from) {
            $fromString .= 'from '.implode(', ', $from);
        }

        $whereString = $this->getWhereString();

        $orderByString = '';
        $orderings = $this->criteria->getOrderings();
        if ($orderings) {
            $orderByString = ' order by';
            foreach ($orderings as $field => $direction) {
                $orderByString .= ' '.Criteria::explodeFieldTypeName($field)[1].' '.$direction;
            }
        }

        $limitString = '';
        $maxResults = $this->criteria->getMaxResults();
        if ($maxResults && $maxResults != Query::INFINITY) {
            $limitString = ' limit '.$maxResults;
        }

        $offsetString = '';
        $firstResult = $this->criteria->getFirstResult();
        if ($firstResult) {
            $offsetString .= ' offset '.$firstResult;
        }

        $selectString = '';
        $selectColumnsString = $this->getStringColumns();
        if (!empty($selectColumnsString)) {
            $selectString = trim('select '.$selectColumnsString).' ';
        }

        return $selectString
            .$fromString
            .$whereString
            .$orderByString
            .$limitString
            .$offsetString;
    }

    /**
     * @return array
     */
    public function getSelectAliases()
    {
        return $this->selectAliases;
    }

    /**
     * Returns a combination of getSelect() and getSelectAlias().
     * Returns an array of fields that will be returned in the
     * dataset.
     *
     * @return array
     */
    public function getSelectDataFields()
    {
        if (empty($this->select)) {
            return [];
        }

        $aliases = $this->selectAliases;
        $result = [];

        foreach ($this->select as $select) {
            list($fieldType, $fieldName) = Criteria::explodeFieldTypeName($select);
            if (isset($aliases[$fieldName])) {
                $resultName = $aliases[$fieldName];
            } elseif (isset($aliases[$select])) {
                $resultName = $aliases[$select];
            } else {
                $resultName = $fieldName;
            }

            $result[$select] = $resultName;
        }

        return $result;
    }

    /**
     * @param string $name
     * @param string $field
     * @param string $function
     *
     * @return $this
     */
    public function addAggregate($name, $field, $function, array $parameters = [])
    {
        $this->aggregations[$name] = ['field' => $field, 'function' => $function, 'parameters' => $parameters];

        return $this;
    }

    /**
     * @return array [name => ['field' => field name, 'function' => function, 'parameters' => params], ...]
     */
    public function getAggregations()
    {
        return $this->aggregations;
    }

    /**
     * @param array $aggregations [name => ['field' => field name, 'function' => function, 'parameters' => params], ...]
     *
     * @return $this
     */
    public function setAggregations(array $aggregations)
    {
        $this->aggregations = $aggregations;

        return $this;
    }

    /**
     * @param      $fieldName
     * @param null $enforcedFieldType
     * @return $this
     */
    private function addToSelect($fieldName, $enforcedFieldType = null)
    {
        if (!$fieldName) {
            return $this;
        }

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
     * Returns the WHERE string part for getStringQuery.
     *
     * @return string
     */
    private function getWhereString()
    {
        $whereString = '';
        if (null !== $whereExpr = $this->criteria->getWhereExpression()) {
            $visitor = new QueryStringExpressionVisitor();
            $whereString = ' where '.$whereExpr->visit($visitor);
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
            $result = '('.$result.')';
        }

        return $result;
    }

    /**
     * @param array $fields
     * @param array $field
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
     * @param array $fields
     * @param array $field
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

    /**
     * Parse field name and check if there is an alias declared in it.
     *
     * @param string $field
     * @param string|null $enforcedFieldType
     * @return string
     */
    private function parseFieldAliasing($field, $enforcedFieldType = null)
    {
        $part = strrev(trim($field));
        $part = preg_split('/ sa /im', $part, 2);

        if (count($part) > 1) {
            // splitting with ' ' and taking first word as a field name - does not allow spaces in field name
            $rev = strrev($part[1]);
            $rev = explode(' ', $rev);
            $field = array_shift($rev);

            list($explodedType, $explodedName) = Criteria::explodeFieldTypeName($field);
            if (!$explodedType) {
                if ($enforcedFieldType) {
                    $explodedType = $enforcedFieldType;
                } else {
                    $explodedType = self::TYPE_TEXT;
                }
            }
            $field = Criteria::implodeFieldTypeName($explodedType, $explodedName);

            $alias = strrev($part[0]);

            $this->selectAliases[$field] = $alias;
        }

        return $field;
    }

    /**
     * Sets a query hint. If the hint name is not recognized, it is silently ignored.
     *
     * @param string $name The name of the hint.
     * @param mixed $value The value of the hint.
     *
     * @return $this
     */
    public function setHint(string $name, $value): self
    {
        $this->hints[$name] = $value;

        return $this;
    }

    /**
     * Gets the value of a query hint. If the hint name is not recognized, FALSE is returned.
     *
     * @param string $name The name of the hint.
     *
     * @return mixed The value of the hint or FALSE, if the hint name is not recognized.
     */
    public function getHint(string $name)
    {
        return $this->hints[$name] ?? false;
    }

    /**
     * Check if the query has a hint
     *
     * @param string $name The name of the hint
     *
     * @return bool False if the query does not have any hint
     */
    public function hasHint(string $name): bool
    {
        return isset($this->hints[$name]);
    }

    /**
     * Return the key value map of query hints that are currently set.
     *
     * @return array<string,mixed>
     */
    public function getHints(): array
    {
        return $this->hints;
    }

    public function __clone()
    {
        $this->hints = [];
        $this->criteria = clone $this->criteria;
    }
}
