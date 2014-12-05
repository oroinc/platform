<?php

namespace Oro\Bundle\SecurityBundle\ORM\Walker;

use Doctrine\ORM\Query;
use Doctrine\ORM\Query\AST;
use Doctrine\ORM\Query\Exec;
use Doctrine\ORM\Query\SqlWalker as DoctrineSqlWalker;
use Doctrine\ORM\Mapping\ClassMetadata;

/**
 * Fixes a Doctrine bug with joining on tables with Joined type inheritance
 *
 * @TODO: This should be removed after upgrading Doctrine to 2.4.6+ (BAP-6712).
 * @SuppressWarnings(PHPMD)
 * @codingStandardsIgnoreStart
 */
class SqlWalker extends DoctrineSqlWalker
{
    /**
     * {@inheritDoc}
     */
    private $rsm;

    /**
     * {@inheritDoc}
     */
    private $em;

    /**
     * {@inheritDoc}
     */
    private $conn;

    /**
     * {@inheritDoc}
     */
    private $query;


    /**
     * {@inheritDoc}
     */
    private $queryComponents;

    /**
     * {@inheritDoc}
     */
    private $selectedClasses = array();

    /**
     * {@inheritDoc}
     */
    private $useSqlTableAliases = true;

    /**
     * {@inheritDoc}
     */
    private $platform;

    /**
     * {@inheritDoc}
     */
    private $quoteStrategy;

    /**
     * {@inheritDoc}
     */
    public function __construct($query, $parserResult, array $queryComponents)
    {
        $this->query            = $query;
        $this->queryComponents  = $queryComponents;
        $this->rsm              = $parserResult->getResultSetMapping();
        $this->em               = $query->getEntityManager();
        $this->conn             = $this->em->getConnection();
        $this->platform         = $this->conn->getDatabasePlatform();
        $this->quoteStrategy    = $this->em->getConfiguration()->getQuoteStrategy();

        parent::__construct($query, $parserResult, $queryComponents);

    }

    /**
     * {@inheritDoc}
     */
    public function walkJoinAssociationDeclaration($joinAssociationDeclaration, $joinType = AST\Join::JOIN_TYPE_INNER, $condExpr = null)
    {
        $sql = '';

        $associationPathExpression = $joinAssociationDeclaration->joinAssociationPathExpression;
        $joinedDqlAlias            = $joinAssociationDeclaration->aliasIdentificationVariable;
        $indexBy                   = $joinAssociationDeclaration->indexBy;

        $relation        = $this->queryComponents[$joinedDqlAlias]['relation'];
        $targetClass     = $this->em->getClassMetadata($relation['targetEntity']);
        $sourceClass     = $this->em->getClassMetadata($relation['sourceEntity']);
        $targetTableName = $this->quoteStrategy->getTableName($targetClass,$this->platform);

        $targetTableAlias = $this->getSQLTableAlias($targetClass->getTableName(), $joinedDqlAlias);
        $sourceTableAlias = $this->getSQLTableAlias($sourceClass->getTableName(), $associationPathExpression->identificationVariable);

        // Ensure we got the owning side, since it has all mapping info
        $assoc = ( ! $relation['isOwningSide']) ? $targetClass->associationMappings[$relation['mappedBy']] : $relation;

        if ($this->query->getHint(Query::HINT_INTERNAL_ITERATION) == true && (!$this->query->getHint(self::HINT_DISTINCT) || isset($this->selectedClasses[$joinedDqlAlias]))) {
            if ($relation['type'] == ClassMetadata::ONE_TO_MANY || $relation['type'] == ClassMetadata::MANY_TO_MANY) {
                throw QueryException::iterateWithFetchJoinNotAllowed($assoc);
            }
        }
        $targetTableJoin = null;
        // This condition is not checking ClassMetadata::MANY_TO_ONE, because by definition it cannot
        // be the owning side and previously we ensured that $assoc is always the owning side of the associations.
        // The owning side is necessary at this point because only it contains the JoinColumn information.
        switch (true) {
            case ($assoc['type'] & ClassMetadata::TO_ONE):
                $conditions = array();

                foreach ($assoc['joinColumns'] as $joinColumn) {
                    $quotedSourceColumn = $this->quoteStrategy->getJoinColumnName($joinColumn, $targetClass, $this->platform);
                    $quotedTargetColumn = $this->quoteStrategy->getReferencedJoinColumnName($joinColumn, $targetClass, $this->platform);

                    if ($relation['isOwningSide']) {
                        $conditions[] = $sourceTableAlias . '.' . $quotedSourceColumn . ' = ' . $targetTableAlias . '.' . $quotedTargetColumn;

                        continue;
                    }

                    $conditions[] = $sourceTableAlias . '.' . $quotedTargetColumn . ' = ' . $targetTableAlias . '.' . $quotedSourceColumn;
                }

                // Apply remaining inheritance restrictions
                $discrSql = $this->_generateDiscriminatorColumnConditionSQL(array($joinedDqlAlias));

                if ($discrSql) {
                    $conditions[] = $discrSql;
                }

                // Apply the filters
                $filterExpr = $this->generateFilterConditionSQL($targetClass, $targetTableAlias);

                if ($filterExpr) {
                    $conditions[] = $filterExpr;
                }

                $targetTableJoin = array(
                    'table' => $targetTableName . ' ' . $targetTableAlias,
                    'condition' => implode(' AND ', $conditions),
                );
                break;

            case ($assoc['type'] == ClassMetadata::MANY_TO_MANY):
                // Join relation table
                $joinTable      = $assoc['joinTable'];
                $joinTableAlias = $this->getSQLTableAlias($joinTable['name'], $joinedDqlAlias);
                $joinTableName  = $this->quoteStrategy->getJoinTableName($assoc, $sourceClass, $this->platform);

                $conditions      = array();
                $relationColumns = ($relation['isOwningSide'])
                    ? $assoc['joinTable']['joinColumns']
                    : $assoc['joinTable']['inverseJoinColumns'];

                foreach ($relationColumns as $joinColumn) {
                    $quotedSourceColumn = $this->quoteStrategy->getJoinColumnName($joinColumn, $targetClass, $this->platform);
                    $quotedTargetColumn = $this->quoteStrategy->getReferencedJoinColumnName($joinColumn, $targetClass, $this->platform);

                    $conditions[] = $sourceTableAlias . '.' . $quotedTargetColumn . ' = ' . $joinTableAlias . '.' . $quotedSourceColumn;
                }

                $sql .= $joinTableName . ' ' . $joinTableAlias . ' ON ' . implode(' AND ', $conditions);

                // Join target table
                $sql .= ($joinType == AST\Join::JOIN_TYPE_LEFT || $joinType == AST\Join::JOIN_TYPE_LEFTOUTER) ? ' LEFT JOIN ' : ' INNER JOIN ';

                $conditions      = array();
                $relationColumns = ($relation['isOwningSide'])
                    ? $assoc['joinTable']['inverseJoinColumns']
                    : $assoc['joinTable']['joinColumns'];

                foreach ($relationColumns as $joinColumn) {
                    $quotedSourceColumn = $this->quoteStrategy->getJoinColumnName($joinColumn, $targetClass, $this->platform);
                    $quotedTargetColumn = $this->quoteStrategy->getReferencedJoinColumnName($joinColumn, $targetClass, $this->platform);

                    $conditions[] = $targetTableAlias . '.' . $quotedTargetColumn . ' = ' . $joinTableAlias . '.' . $quotedSourceColumn;
                }

                // Apply remaining inheritance restrictions
                $discrSql = $this->_generateDiscriminatorColumnConditionSQL(array($joinedDqlAlias));

                if ($discrSql) {
                    $conditions[] = $discrSql;
                }

                // Apply the filters
                $filterExpr = $this->generateFilterConditionSQL($targetClass, $targetTableAlias);

                if ($filterExpr) {
                    $conditions[] = $filterExpr;
                }

                $targetTableJoin = array(
                    'table' => $targetTableName . ' ' . $targetTableAlias,
                    'condition' => implode(' AND ', $conditions),
                );
                break;

            default:
                throw new \BadMethodCallException('Type of association must be one of *_TO_ONE or MANY_TO_MANY');
        }

        // Handle WITH clause
        $withCondition = (null === $condExpr) ? '' : ('(' . $this->walkConditionalExpression($condExpr) . ')');

        if ($targetClass->isInheritanceTypeJoined()) {
            $ctiJoins = $this->_generateClassTableInheritanceJoins($targetClass, $joinedDqlAlias);
            // If we have WITH condition, we need to build nested joins for target class table and cti joins
            if ($withCondition) {
                $sql .= '(' . $targetTableJoin['table'] . $ctiJoins . ') ON ' . $targetTableJoin['condition'];
            } else {
                $sql .= $targetTableJoin['table'] . ' ON ' . $targetTableJoin['condition'] . $ctiJoins;
            }
        } else {
            $sql .= $targetTableJoin['table'] . ' ON ' . $targetTableJoin['condition'];
        }

        if ($withCondition) {
            $sql .= ' AND ' . $withCondition;
        }

        // Apply the indexes
        if ($indexBy) {
            // For Many-To-One or One-To-One associations this obviously makes no sense, but is ignored silently.
            $this->rsm->addIndexBy(
                $indexBy->simpleStateFieldPathExpression->identificationVariable,
                $indexBy->simpleStateFieldPathExpression->field
            );
        } else if (isset($relation['indexBy'])) {
            $this->rsm->addIndexBy($joinedDqlAlias, $relation['indexBy']);
        }

        return $sql;
    }

    /**
     * {@inheritDoc}
     */
    private function generateFilterConditionSQL(ClassMetadata $targetEntity, $targetTableAlias)
    {
        if (!$this->em->hasFilters()) {
            return '';
        }

        switch($targetEntity->inheritanceType) {
            case ClassMetadata::INHERITANCE_TYPE_NONE:
                break;
            case ClassMetadata::INHERITANCE_TYPE_JOINED:
                // The classes in the inheritance will be added to the query one by one,
                // but only the root node is getting filtered
                if ($targetEntity->name !== $targetEntity->rootEntityName) {
                    return '';
                }
                break;
            case ClassMetadata::INHERITANCE_TYPE_SINGLE_TABLE:
                // With STI the table will only be queried once, make sure that the filters
                // are added to the root entity
                $targetEntity = $this->em->getClassMetadata($targetEntity->rootEntityName);
                break;
            default:
                //@todo: throw exception?
                return '';
                break;
        }

        $filterClauses = array();
        foreach ($this->em->getFilters()->getEnabledFilters() as $filter) {
            if ('' !== $filterExpr = $filter->addFilterConstraint($targetEntity, $targetTableAlias)) {
                $filterClauses[] = '(' . $filterExpr . ')';
            }
        }

        return implode(' AND ', $filterClauses);
    }

    /**
     * {@inheritDoc}
     */
    private function _generateDiscriminatorColumnConditionSQL(array $dqlAliases)
    {
        $sqlParts = array();

        foreach ($dqlAliases as $dqlAlias) {
            $class = $this->queryComponents[$dqlAlias]['metadata'];

            if ( ! $class->isInheritanceTypeSingleTable()) continue;

            $conn   = $this->em->getConnection();
            $values = array();

            if ($class->discriminatorValue !== null) { // discriminators can be 0
                $values[] = $conn->quote($class->discriminatorValue);
            }

            foreach ($class->subClasses as $subclassName) {
                $values[] = $conn->quote($this->em->getClassMetadata($subclassName)->discriminatorValue);
            }

            $sqlParts[] = (($this->useSqlTableAliases) ? $this->getSQLTableAlias($class->getTableName(), $dqlAlias) . '.' : '')
                . $class->discriminatorColumn['name'] . ' IN (' . implode(', ', $values) . ')';
        }

        $sql = implode(' AND ', $sqlParts);

        return (count($sqlParts) > 1) ? '(' . $sql . ')' : $sql;
    }

    /**
     * {@inheritDoc}
     */
    private function _generateClassTableInheritanceJoins($class, $dqlAlias)
    {
        $sql = '';

        $baseTableAlias = $this->getSQLTableAlias($class->getTableName(), $dqlAlias);

        // INNER JOIN parent class tables
        foreach ($class->parentClasses as $parentClassName) {
            $parentClass = $this->em->getClassMetadata($parentClassName);
            $tableAlias  = $this->getSQLTableAlias($parentClass->getTableName(), $dqlAlias);

            // If this is a joined association we must use left joins to preserve the correct result.
            $sql .= isset($this->queryComponents[$dqlAlias]['relation']) ? ' LEFT ' : ' INNER ';
            $sql .= 'JOIN ' . $this->quoteStrategy->getTableName($parentClass, $this->platform) . ' ' . $tableAlias . ' ON ';

            $sqlParts = array();

            foreach ($this->quoteStrategy->getIdentifierColumnNames($class, $this->platform) as $columnName) {
                $sqlParts[] = $baseTableAlias . '.' . $columnName . ' = ' . $tableAlias . '.' . $columnName;
            }

            // Add filters on the root class
            if ($filterSql = $this->generateFilterConditionSQL($parentClass, $tableAlias)) {
                $sqlParts[] = $filterSql;
            }

            $sql .= implode(' AND ', $sqlParts);
        }

        // Ignore subclassing inclusion if partial objects is disallowed
        if ($this->query->getHint(Query::HINT_FORCE_PARTIAL_LOAD)) {
            return $sql;
        }

        // LEFT JOIN child class tables
        foreach ($class->subClasses as $subClassName) {
            $subClass   = $this->em->getClassMetadata($subClassName);
            $tableAlias = $this->getSQLTableAlias($subClass->getTableName(), $dqlAlias);

            $sql .= ' LEFT JOIN ' . $this->quoteStrategy->getTableName($subClass, $this->platform) . ' ' . $tableAlias . ' ON ';

            $sqlParts = array();

            foreach ($this->quoteStrategy->getIdentifierColumnNames($subClass, $this->platform) as $columnName) {
                $sqlParts[] = $baseTableAlias . '.' . $columnName . ' = ' . $tableAlias . '.' . $columnName;
            }

            $sql .= implode(' AND ', $sqlParts);
        }

        return $sql;
    }
}
// @codingStandardsIgnoreEnd
