<?php

namespace Oro\Bundle\SecurityBundle\AccessRule;

use Oro\Bundle\SecurityBundle\AccessRule\Expr\CompositeExpression;
use Oro\Bundle\SecurityBundle\AccessRule\Expr\ExpressionInterface;

/**
 * Represents a container for expressions that are used to limit access to an entity.
 */
class Criteria
{
    /** @var string */
    private $type;

    /** @var string */
    private $entityClass;

    /** @var string */
    private $alias;

    /** @var bool */
    private $isRoot;

    /** @var string */
    private $permission;

    /** @var ExpressionInterface */
    private $expression;

    /** @var array [option name => option value, ...] */
    private $options = [];

    /**
     * @param string $type
     * @param string $entityClass
     * @param string $alias
     * @param string $permission
     * @param bool   $isRoot
     */
    public function __construct(
        string $type,
        string $entityClass,
        string $alias,
        string $permission = 'VIEW',
        bool $isRoot = true
    ) {
        $this->type = $type;
        $this->entityClass = $entityClass;
        $this->alias = $alias;
        $this->isRoot = $isRoot;
        $this->permission = $permission;
    }

    /**
     * Gets the type of a processed query.
     *
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * Gets name of a permission for which this criteria is created.
     *
     * @return string
     */
    public function getPermission(): string
    {
        return $this->permission;
    }

    /**
     * Gets the class name of an entity for which this criteria is created.
     *
     * @return string
     */
    public function getEntityClass(): string
    {
        return $this->entityClass;
    }

    /**
     * Gets an alias of an entity in a query for which this criteria is created.
     *
     * @return string
     */
    public function getAlias(): string
    {
        return $this->alias;
    }

    /**
     * Indicates whether this criteria is related to the root entity in a query.
     *
     * @return bool
     */
    public function isRoot(): bool
    {
        return $this->isRoot;
    }

    /**
     * Gets an expression that is built by this criteria object.
     *
     * @return ExpressionInterface|null
     */
    public function getExpression(): ?ExpressionInterface
    {
        return $this->expression;
    }

    /**
     * Sets an expression.
     *
     * @param ExpressionInterface $expression
     */
    public function setExpression(ExpressionInterface $expression): void
    {
        $this->expression = $expression;
    }

    /**
     * Appends an expression that should be concatenated with existing expressions by logical AND operator.
     *
     * @param ExpressionInterface $expression
     *
     * @return Criteria
     */
    public function andExpression(ExpressionInterface $expression): Criteria
    {
        if ($this->expression === null) {
            $this->expression = $expression;

            return $this;
        }

        $this->expression = new CompositeExpression(
            CompositeExpression::TYPE_AND,
            [$this->expression, $expression]
        );

        return $this;
    }

    /**
     * Appends an expression that should be concatenated with existing expressions by logical OR operator.
     *
     * @param ExpressionInterface $expression
     *
     * @return Criteria
     */
    public function orExpression(ExpressionInterface $expression): Criteria
    {
        if ($this->expression === null) {
            $this->expression = $expression;

            return $this;
        }

        $this->expression = new CompositeExpression(
            CompositeExpression::TYPE_OR,
            [$this->expression, $expression]
        );

        return $this;
    }

    /**
     * Indicates whether this criteria has the given additional option.
     *
     * @param string $key
     *
     * @return bool
     */
    public function hasOption(string $key): bool
    {
        return \array_key_exists($key, $this->options);
    }

    /**
     * Gets the given additional option. In case if the option does not exist, the default value is returned.
     *
     * @param string $key
     * @param mixed  $defaultValue
     *
     * @return mixed
     */
    public function getOption(string $key, $defaultValue = null)
    {
        if (!$this->hasOption($key)) {
            return $defaultValue;
        }

        return $this->options[$key];
    }

    /**
     * Sets an additional option.
     *
     * @param string $key
     * @param mixed  $value
     */
    public function setOption(string $key, $value): void
    {
        $this->options[$key] = $value;
    }

    /**
     * Removes an additional option.
     *
     * @param string $key
     */
    public function removeOption(string $key): void
    {
        unset($this->options[$key]);
    }
}
