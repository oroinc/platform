<?php

namespace Oro\Bundle\QueryDesignerBundle\QueryDesigner;

use Oro\Bundle\QueryDesignerBundle\Exception\InvalidConfigurationException;
use Oro\Bundle\QueryDesignerBundle\Model\AbstractQueryDesigner;

/**
 * The base context for classes that convert a query definition created by the query designer to another format.
 *
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 * @SuppressWarnings(PHPMD.ExcessivePublicCount)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 * @SuppressWarnings(PHPMD.TooManyMethods)
 */
class QueryConverterContext
{
    private const ROOT_JOIN_ID = '';

    private const COLUMN_ALIAS_TEMPLATE = 'c%s';
    private const TABLE_ALIAS_TEMPLATE  = 't%s';

    /** @var string */
    private $rootEntity;

    /** @var array */
    private $definition;

    /** @var string[] [alias => join id, ...] */
    private $joins = [];

    /** @var int */
    private $generatedTableAliasCounter = 0;

    /** @var string[] [join id => alias, ...] */
    private $tableAliases = [];

    /** @var string[] [column id => column alias, ...] */
    private $columnAliases = [];

    /** @var string[] [column id => column name, ...] */
    private $columnNames = [];

    /** @var string[] [column alias => column id, ...] */
    private $columnIds = [];

    /** @var string[] [column name => column expression, ...] */
    private $virtualColumnExpressions = [];

    /** @var array[] [column join id => [key => value, ...], ...] */
    private $virtualColumnOptions = [];

    /** @var array [join id => virtual join id, ...] */
    private $virtualRelationJoins = [];

    /** @var array [virtual join id => join id, ...] */
    private $virtualRelationJoinsInverse = [];

    /** @var string[] [alias => table alias, ...] */
    private $aliases = [];

    /** @var array */
    private $queryAliases = [];

    /**
     * @param AbstractQueryDesigner $source
     */
    public function init(AbstractQueryDesigner $source): void
    {
        $entity = $source->getEntity();
        $this->validateEntity($entity);
        $definition = QueryDefinitionUtil::decodeDefinition($source->getDefinition());
        $this->validateDefinition($definition);

        $this->rootEntity = $entity;
        $this->definition = $definition;
    }

    public function reset(): void
    {
        $this->rootEntity = null;
        $this->definition = null;
        $this->joins = [];
        $this->generatedTableAliasCounter = 0;
        $this->tableAliases = [];
        $this->columnAliases = [];
        $this->columnNames = [];
        $this->columnIds = [];
        $this->aliases = [];
        $this->queryAliases = [];
        $this->virtualColumnExpressions = [];
        $this->virtualColumnOptions = [];
        $this->virtualRelationJoins = [];
        $this->virtualRelationJoinsInverse = [];
    }

    /**
     * @return array
     */
    public function getDefinition(): array
    {
        return $this->definition;
    }

    /**
     * @return string
     */
    public function getRootEntity(): string
    {
        return $this->rootEntity;
    }

    /**
     * @return string
     */
    final public function getRootJoinId(): string
    {
        return self::ROOT_JOIN_ID;
    }

    /**
     * @return string
     */
    public function getRootTableAlias(): string
    {
        $rootJoinId = $this->getRootJoinId();
        if (!isset($this->tableAliases[$rootJoinId])) {
            throw new \LogicException('The root table alias is not defined.');
        }

        return $this->tableAliases[$rootJoinId];
    }

    /**
     * @param string $alias
     */
    public function setRootTableAlias(string $alias): void
    {
        $this->setTableAlias($this->getRootJoinId(), $alias);
    }

    /**
     * @return string[] [alias => join id, ...]
     */
    public function getJoins(): array
    {
        return $this->joins;
    }

    /**
     * @param string $alias
     *
     * @return bool
     */
    public function hasJoin(string $alias): bool
    {
        return !empty($this->joins[$alias]);
    }

    /**
     * @param string $alias
     *
     * @return string|null
     */
    public function findJoin(string $alias): ?string
    {
        return $this->joins[$alias] ?? null;
    }

    /**
     * @param string $alias
     *
     * @return string
     */
    public function getJoin(string $alias): string
    {
        if (!isset($this->joins[$alias])) {
            throw new \LogicException(sprintf('The join for the alias "%s" is not defined.', $alias));
        }

        return $this->joins[$alias];
    }

    /**
     * @return string
     */
    public function generateTableAlias(): string
    {
        return sprintf(self::TABLE_ALIAS_TEMPLATE, ++$this->generatedTableAliasCounter);
    }

    /**
     * @return string[] [join id => alias, ...]
     */
    public function getTableAliases(): array
    {
        return $this->tableAliases;
    }

    /**
     * @param string $joinId
     *
     * @return bool
     */
    public function hasTableAlias(string $joinId): bool
    {
        return isset($this->tableAliases[$joinId]);
    }

    /**
     * @param string $joinId
     *
     * @return string|null
     */
    public function findTableAlias(string $joinId): ?string
    {
        return $this->tableAliases[$joinId] ?? null;
    }

    /**
     * @param string $joinId
     *
     * @return string
     */
    public function getTableAlias(string $joinId): string
    {
        if (!isset($this->tableAliases[$joinId])) {
            throw new \LogicException(sprintf('The table alias for the join "%s" is not defined.', $joinId));
        }

        return $this->tableAliases[$joinId];
    }

    /**
     * @param string $joinId
     * @param string $alias
     */
    public function setTableAlias(string $joinId, string $alias): void
    {
        $this->tableAliases[$joinId] = $alias;
        $this->joins[$alias] = $joinId;
    }

    /**
     * @return string
     */
    public function generateColumnAlias(): string
    {
        return sprintf(self::COLUMN_ALIAS_TEMPLATE, count($this->columnAliases) + 1);
    }

    /**
     * @return string[] [column id => column alias, ...]
     */
    public function getColumnAliases(): array
    {
        return $this->columnAliases;
    }

    /**
     * @param string $columnId
     *
     * @return bool
     */
    public function hasColumnAlias(string $columnId): bool
    {
        return isset($this->columnAliases[$columnId]);
    }

    /**
     * @param string $columnId
     *
     * @return string|null
     */
    public function findColumnAlias(string $columnId): ?string
    {
        return $this->columnAliases[$columnId] ?? null;
    }

    /**
     * @param string $columnId
     *
     * @return string
     */
    public function getColumnAlias(string $columnId): string
    {
        if (!isset($this->columnAliases[$columnId])) {
            throw new \LogicException(sprintf(
                'The column alias for the column "%s" is not defined.',
                $columnId
            ));
        }

        return $this->columnAliases[$columnId];
    }

    /**
     * @param string $columnId
     * @param string $columnAlias
     * @param string $columnName
     */
    public function setColumnAlias(string $columnId, string $columnAlias, string $columnName): void
    {
        $this->columnAliases[$columnId] = $columnAlias;
        $this->columnNames[$columnId] = $columnName;
        $this->columnIds[$columnAlias] = $columnId;
    }

    /**
     * @param string $columnAlias
     *
     * @return string|null
     */
    public function findColumnId(string $columnAlias): ?string
    {
        return $this->columnIds[$columnAlias] ?? null;
    }

    /**
     * @param string $columnAlias
     *
     * @return string
     */
    public function getColumnId(string $columnAlias): string
    {
        if (!isset($this->columnIds[$columnAlias])) {
            throw new \LogicException(sprintf(
                'The column identifier for the column alias "%s" is not defined.',
                $columnAlias
            ));
        }

        return $this->columnIds[$columnAlias];
    }

    /**
     * @param string $columnId
     *
     * @return bool
     */
    public function hasColumnName(string $columnId): bool
    {
        return isset($this->columnNames[$columnId]);
    }

    /**
     * @param string $columnId
     *
     * @return string|null
     */
    public function findColumnName(string $columnId): ?string
    {
        return $this->columnNames[$columnId] ?? null;
    }

    /**
     * @param string $columnId
     *
     * @return string
     */
    public function getColumnName(string $columnId): string
    {
        if (!isset($this->columnNames[$columnId])) {
            throw new \LogicException(sprintf(
                'The column name for the column "%s" is not defined.',
                $columnId
            ));
        }

        return $this->columnNames[$columnId];
    }

    /**
     * @param string $columnName
     *
     * @return bool
     */
    public function hasVirtualColumnExpression(string $columnName): bool
    {
        return isset($this->virtualColumnExpressions[$columnName]);
    }

    /**
     * @param string $columnName
     *
     * @return string
     */
    public function getVirtualColumnExpression(string $columnName): string
    {
        if (!isset($this->virtualColumnExpressions[$columnName])) {
            throw new \LogicException(sprintf(
                'The virtual column expression for the column "%s" is not defined.',
                $columnName
            ));
        }

        return $this->virtualColumnExpressions[$columnName];
    }

    /**
     * @param string $columnName
     * @param string $expression
     */
    public function setVirtualColumnExpression(string $columnName, string $expression): void
    {
        $this->virtualColumnExpressions[$columnName] = $expression;
    }

    /**
     * @param string $columnJoinId
     *
     * @return bool
     */
    public function hasVirtualColumnOptions(string $columnJoinId): bool
    {
        return isset($this->virtualColumnOptions[$columnJoinId]);
    }

    /**
     * @param string $columnJoinId
     *
     * @return array
     */
    public function getVirtualColumnOptions(string $columnJoinId): array
    {
        if (!isset($this->virtualColumnOptions[$columnJoinId])) {
            throw new \LogicException(sprintf(
                'The virtual column options for the column "%s" are not defined.',
                $columnJoinId
            ));
        }

        return $this->virtualColumnOptions[$columnJoinId];
    }

    /**
     * @param string $columnJoinId
     * @param string $optionName
     *
     * @return bool
     */
    public function hasVirtualColumnOption(string $columnJoinId, string $optionName): bool
    {
        return
            isset($this->virtualColumnOptions[$columnJoinId])
            && \array_key_exists($optionName, $this->virtualColumnOptions[$columnJoinId]);
    }

    /**
     * @param string $columnJoinId
     * @param string $optionName
     *
     * @return mixed
     */
    public function getVirtualColumnOption(string $columnJoinId, string $optionName)
    {
        $options = $this->getVirtualColumnOptions($columnJoinId);
        if (!\array_key_exists($optionName, $options)) {
            throw new \LogicException(sprintf(
                'The virtual column option "%s" for the column "%s" is not defined.',
                $optionName,
                $columnJoinId
            ));
        }


        return $options[$optionName];
    }

    /**
     * @param string $columnJoinId
     * @param array  $options
     */
    public function setVirtualColumnOptions(string $columnJoinId, array $options): void
    {
        $this->virtualColumnOptions[$columnJoinId] = $options;
    }

    /**
     * @return bool
     */
    public function hasVirtualRelationJoins(): bool
    {
        return !empty($this->virtualRelationJoins);
    }

    /**
     * @param string $joinId
     *
     * @return bool
     */
    public function hasVirtualRelationJoin(string $joinId): bool
    {
        return isset($this->virtualRelationJoins[$joinId]);
    }

    /**
     * @param string $joinId
     *
     * @return string
     */
    public function getVirtualRelationJoin(string $joinId): string
    {
        if (!isset($this->virtualRelationJoins[$joinId])) {
            throw new \LogicException(sprintf(
                'The virtual relation join for the join "%s" is not defined.',
                $joinId
            ));
        }

        return $this->virtualRelationJoins[$joinId];
    }

    /**
     * @param string $joinId
     * @param string $virtualJoinId
     */
    public function setVirtualRelationJoin(string $joinId, string $virtualJoinId): void
    {
        $this->virtualRelationJoins[$joinId] = $virtualJoinId;
        $this->virtualRelationJoinsInverse[$virtualJoinId] = $joinId;
    }

    /**
     * @param string $virtualJoinId
     *
     * @return string|null
     */
    public function findJoinByVirtualRelationJoin(string $virtualJoinId): ?string
    {
        return $this->virtualRelationJoinsInverse[$virtualJoinId] ?? null;
    }

    /**
     * @return string[] [alias => table alias, ...]
     */
    public function getAliases(): array
    {
        return $this->aliases;
    }

    /**
     * @param string $alias
     *
     * @return bool
     */
    public function hasAlias(string $alias): bool
    {
        return isset($this->aliases[$alias]);
    }

    /**
     * @param string $alias
     *
     * @return string
     */
    public function getAlias(string $alias): string
    {
        if (!isset($this->aliases[$alias])) {
            throw new \LogicException(sprintf('The table alias for the alias "%s" is not defined.', $alias));
        }

        return $this->aliases[$alias];
    }

    /**
     * @param string $alias
     * @param string $tableAlias
     */
    public function setAlias(string $alias, string $tableAlias): void
    {
        $this->aliases[$alias] = $tableAlias;
    }

    /**
     * @return string[]
     */
    public function getQueryAliases(): array
    {
        return $this->queryAliases;
    }

    /**
     * @param string[] $queryAliases
     */
    public function setQueryAliases(array $queryAliases): void
    {
        $this->queryAliases = $queryAliases;
    }

    /**
     * @param string|null $entity
     */
    protected function validateEntity(?string $entity): void
    {
        if (!$entity) {
            throw new InvalidConfigurationException('The entity must be specified.');
        }
    }

    /**
     * @param array $definition
     */
    protected function validateDefinition(array $definition): void
    {
        if (!\array_key_exists('columns', $definition)) {
            throw new InvalidConfigurationException('The "columns" definition does not exist.');
        }
        if (empty($definition['columns'])) {
            throw new InvalidConfigurationException('The "columns" definition must not be empty.');
        }
    }
}
