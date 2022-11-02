<?php

namespace Oro\Bundle\QueryDesignerBundle\QueryDesigner;

/**
 * This class provides a set of functions intended to manipulation with the join identifiers.
 *
 * Format description:
 *      ::, +, |, . - literals
 *      [] - values enclosed in square brackets are optional
 *      () - group
 *      *  - expression can be repeated many times
 *      <class>  - class name of entity
 *      <field-name> - name of the field
 *      <join-type> - type of join (left, inner, ...)
 *      <join-condition-type> - (ON, WITH, ...)
 *      <join-condition> - part after ON keyword
 *      <field> - <field-name>[|<join-type>[|<join-condition-type>[|<join-condition>]]]
 *
 * Formats:
 *      [<class>::]<field>([+<class>[::<class>]::<field>])*
 *      <field-name>[.<field>]([+<class>[::<class>]::<field>])*
 *
 * The join identifier is a string which unique identifies
 * each JOIN used in a query.
 * Examples:
 *      AcmeBundle\Entity\Order::products
 *          - represents "order -> products" join
 *      AcmeBundle\Entity\Order::products+AcmeBundle\Entity\Product::statuses
 *          - represents "order -> products -> statuses" join
 *      AcmeBundle\Entity\Order::products+AcmeBundle\Entity\Product::AcmeBundle\Entity\User::product
 *          - represents "order -> products -> users" unidirectional join
 *            in this case the "product" association in "AcmeBundle\Entity\User" entity has no
 *            inverse side association in AcmeBundle\Entity\Product entity
 *      AcmeBundle\Entity\Order::products|left
 *          - represents "order -> products" join forces to use LEFT JOIN
 *      AcmeBundle\Entity\Order::products|||products.active = true
 *          - represents "order -> products" join with additional condition
 *      AcmeBundle\Entity\Order::products||WITH|products.orderId = order AND products.active = true
 *          - represents "order -> products" join with custom condition
 *      AcmeBundle\Entity\Order::products|left|WITH|products.orderId = order AND products.active = true
 *          - represents "order -> products" join with custom condition and forces to use LEFT JOIN
 *      order.products|inner
 *          - represents "order -> products" join forces to use INNER JOIN
 *      order.products|left|WITH|products.orderId = order AND products.active = true
 *          - represents "order -> products" join with custom condition and forces to use LEFT JOIN
 * The join identifier for the root table is empty string.
 *
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
class JoinIdentifierHelper
{
    /** @var string */
    private $rootEntity;

    /**
     * @param string $rootEntity
     */
    public function __construct($rootEntity)
    {
        $this->rootEntity = $rootEntity;
    }

    /**
     * Returns the join identifier built from the given attributes
     *
     * @param string      $join
     * @param string|null $parentJoinId
     * @param string|null $joinType
     * @param string|null $conditionType
     * @param string|null $condition
     *
     * @return string
     */
    public function buildJoinIdentifier(
        $join,
        $parentJoinId = null,
        $joinType = null,
        $conditionType = null,
        $condition = null
    ) {
        $result = $join;
        if (!empty($parentJoinId)) {
            $result = $parentJoinId . '+' . $result;
        }
        if (!empty($condition)) {
            $result .= sprintf('|%s|%s|%s', $joinType, $conditionType, $condition);
        } elseif (!empty($conditionType)) {
            $result .= sprintf('|%s|%s', $joinType, $conditionType);
        } elseif (!empty($joinType)) {
            $result .= '|' . $joinType;
        }

        return $result;
    }

    /**
     * Returns the join identifier for the given column
     *
     * @param string      $columnName
     * @param string|null $entityClass
     *
     * @return string
     */
    public function buildColumnJoinIdentifier($columnName, $entityClass = null)
    {
        return sprintf('%s::%s', $entityClass ?? $this->rootEntity, $columnName);
    }

    /**
     * Gets all join identifiers the given column name consists
     *
     * As example, for 'rootEntityField+Class\Name::joinedEntityRelation+Relation\Class::fieldToSelect' column
     * the result will be:
     * - 'Root\Class::rootEntityField'
     * - 'Root\Class::rootEntityField+Class\Name::joinedEntityRelation'
     *
     * @param string $columnName
     *
     * @return string[]
     */
    public function explodeColumnName($columnName)
    {
        $lastDelimiter = strrpos($columnName, '+');
        if (false === $lastDelimiter) {
            return [''];
        }

        return $this->explodeJoinIdentifier(
            $this->buildColumnJoinIdentifier(substr($columnName, 0, $lastDelimiter))
        );
    }

    /**
     * Gets all join identifiers the given join identifier consists
     *
     * @param string $joinId
     *
     * @return string[]
     */
    public function explodeJoinIdentifier($joinId)
    {
        $joinIds = [];
        $items = $this->splitJoinIdentifier($joinId);
        foreach ($items as $item) {
            $joinIds[] = empty($joinIds)
                ? $item
                : sprintf('%s+%s', $joinIds[count($joinIds) - 1], $item);
        }

        return $joinIds;
    }

    /**
     * Gets parts the the given join identifier consists
     *
     * @param string $joinId
     *
     * @return string[]
     */
    public function splitJoinIdentifier($joinId)
    {
        return explode('+', $joinId);
    }

    /**
     * Gets parts the the given join identifier consists
     *
     * @param string[] $joinIds
     *
     * @return string
     */
    public function mergeJoinIdentifier($joinIds)
    {
        return implode('+', $joinIds);
    }

    /**
     * Extracts a parent join identifier
     *
     * @param string $joinId
     *
     * @return string
     *
     * @throws \LogicException if incorrect join identifier specified
     */
    public function getParentJoinIdentifier($joinId)
    {
        if (empty($joinId)) {
            throw new \LogicException('Cannot get parent join identifier for root table.');
        }

        $lastDelimiter = strrpos($joinId, '+');

        return false !== $lastDelimiter
            ? substr($joinId, 0, $lastDelimiter)
            : '';
    }

    /**
     * Builds join identifier for a table is joined on the same level as a table identified by $joinId.
     *
     * @param string $joinId          The join identifier
     * @param string $joinByFieldName The name of a field should be used to join new table
     *
     * @return string The join identifier
     */
    public function buildSiblingJoinIdentifier($joinId, $joinByFieldName)
    {
        if (empty($joinId)) {
            return sprintf('%s::%s', $this->rootEntity, $joinByFieldName);
        }

        $parentJoinId = $this->getParentJoinIdentifier($joinId);
        if (empty($parentJoinId)) {
            return sprintf('%s::%s', substr($joinId, 0, strpos($joinId, '::')), $joinByFieldName);
        }

        $entityClass = substr(
            $joinId,
            \strlen($parentJoinId) + 1,
            strpos($joinId, '::', \strlen($parentJoinId) + 1) - \strlen($parentJoinId) - 1
        );

        return sprintf('%s+%s::%s', $parentJoinId, $entityClass, $joinByFieldName);
    }

    /**
     * Extracts an entity class name for the given column or from the given join identifier
     *
     * @param string $columnNameOrJoinId
     *
     * @return string|null
     */
    public function getEntityClassName($columnNameOrJoinId)
    {
        $startDelimiter = $this->getStartPosition($columnNameOrJoinId, '+');
        $lastJoinPart = $this->getLastJoinPart($columnNameOrJoinId, $startDelimiter);
        $lastDelimiter = strrpos($lastJoinPart, '::');
        if (false === $lastDelimiter) {
            return 0 === $startDelimiter && !str_contains($lastJoinPart, '.')
                ? $this->rootEntity
                : null;
        }

        $result = substr($lastJoinPart, 0, $lastDelimiter);

        // check if the class name has :: delimiter (it means that it is unidirectional relationship)
        // and if so the class name is after ::
        $lastDelimiter = strrpos($result, '::');
        if (false !== $lastDelimiter) {
            $result = substr($result, $lastDelimiter + 2);
        }

        return $result;
    }

    /**
     * Extracts a field name for the given column or from the given join identifier
     *
     * @param string $columnNameOrJoinId
     *
     * @return string
     */
    public function getFieldName($columnNameOrJoinId)
    {
        $lastJoinPart = $this->getLastJoinPart($columnNameOrJoinId, $this->getStartPosition($columnNameOrJoinId, '+'));
        $lastDelimiter = $this->getStartPosition($lastJoinPart, '::');
        if ($lastDelimiter > 0) {
            return substr($lastJoinPart, $lastDelimiter);
        }
        $lastDelimiter = $this->getStartPosition($lastJoinPart, '.');
        if ($lastDelimiter > 0) {
            return substr($lastJoinPart, $lastDelimiter);
        }

        return $lastJoinPart;
    }

    /**
     * Checks if the given join identifier represents unidirectional relationship
     *
     * @param string $joinId
     *
     * @return bool
     */
    public function isUnidirectionalJoin($joinId)
    {
        $startDelimiter = strpos($joinId, '::', $this->getStartPosition($joinId, '+'));
        if (false === $startDelimiter) {
            return false;
        }

        return false !== strpos($joinId, '::', $startDelimiter + 2);
    }

    /**
     * Checks if given join identifier represents unidirectional join with already prepared conditions
     * Possible use cases in virtual fields join with unidirectional join
     *
     * @param string $joinId
     *
     * @return bool
     */
    public function isUnidirectionalJoinWithCondition($joinId)
    {
        return
            false === strpos($joinId, '::', $this->getStartPosition($joinId, '+'))
            && !str_contains($this->getUnidirectionalJoinEntityName($joinId), '.');
    }

    /**
     * Fetches entity name from unidirectional join or join part form prepared conditioned join
     *
     * @param string $joinId
     *
     * @return string
     */
    public function getUnidirectionalJoinEntityName($joinId)
    {
        $lastJoinPart = $this->getLastJoinPart($joinId, $this->getStartPosition($joinId, '+'));
        $lastDelimiter = $this->getStartPosition($lastJoinPart, '::');

        return $lastDelimiter > 0 ? substr($lastJoinPart, $lastDelimiter) : $lastJoinPart;
    }

    /**
     * Extracts the join part from the given join identifier
     *
     * @param string $joinId
     *
     * @return string
     */
    public function getJoin($joinId)
    {
        return $this->getLastJoinPart($joinId, $this->getStartPosition($joinId, '+'));
    }

    /**
     * Extracts the join type from the given join identifier
     *
     * @param string $joinId
     *
     * @return string|null NULL for autodetect, or a string represents the join type, for example 'inner' or 'left'
     */
    public function getJoinType($joinId)
    {
        $startDelimiter = strpos($joinId, '|', $this->getStartPosition($joinId, '+'));
        if (false === $startDelimiter) {
            return null;
        }

        return $this->getLastJoinPart($joinId, $startDelimiter + 1) ?: null;
    }

    /**
     * Extracts the join condition type from the given join identifier
     *
     * @param string $joinId
     *
     * @return string|null NULL if not specified
     *                     or a string represents the join condition type, for example 'WITH' or 'ON'
     */
    public function getJoinConditionType($joinId)
    {
        $startDelimiter = strpos($joinId, '|', $this->getStartPosition($joinId, '+'));
        if (false === $startDelimiter) {
            return null;
        }
        $startDelimiter = strpos($joinId, '|', $startDelimiter + 1);
        if (false === $startDelimiter) {
            return null;
        }

        return $this->getLastJoinPart($joinId, $startDelimiter + 1) ?: null;
    }

    /**
     * Extracts the join condition from the given join identifier
     *
     * @param string $joinId
     *
     * @return string|null NULL if not specified, or a string represents the join condition
     */
    public function getJoinCondition($joinId)
    {
        $startDelimiter = strpos($joinId, '|', $this->getStartPosition($joinId, '+'));
        if (false === $startDelimiter) {
            return null;
        }
        $startDelimiter = strpos($joinId, '|', $startDelimiter + 1);
        if (false === $startDelimiter) {
            return null;
        }
        $startDelimiter = strpos($joinId, '|', $startDelimiter + 1);
        if (false === $startDelimiter) {
            return null;
        }

        return $this->getLastJoinPart($joinId, $startDelimiter + 1) ?: null;
    }

    /**
     * @param string $str
     * @param string $needle
     *
     * @return int
     */
    private function getStartPosition($str, $needle)
    {
        $startDelimiter = strrpos($str, $needle);

        return false === $startDelimiter
            ? 0
            : $startDelimiter + strlen($needle);
    }

    /**
     * @param string   $columnNameOrJoinId
     * @param int|bool $startDelimiter
     *
     * @return string
     */
    private function getLastJoinPart($columnNameOrJoinId, $startDelimiter)
    {
        $endDelimiter = strpos($columnNameOrJoinId, '|', $startDelimiter);

        return false === $endDelimiter
            ? substr($columnNameOrJoinId, $startDelimiter)
            : substr($columnNameOrJoinId, $startDelimiter, $endDelimiter - $startDelimiter);
    }
}
