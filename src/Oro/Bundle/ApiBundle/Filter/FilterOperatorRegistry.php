<?php

namespace Oro\Bundle\ApiBundle\Filter;

/**
 * The container for all registered operators for API filters.
 */
class FilterOperatorRegistry
{
    /** @var array [operator name => operator short name or NULL, ...] */
    private array $operators;
    /** @var array [operator short name => operator name, ...] */
    private array $shortOperators;

    /**
     * @param array $operators [operator name => operator short name or NULL, ...]
     */
    public function __construct(array $operators)
    {
        $this->operators = $operators;
        $this->shortOperators = [];
        foreach ($operators as $name => $shortName) {
            if ($shortName && !\array_key_exists($shortName, $this->shortOperators)) {
                $this->shortOperators[$shortName] = $name;
            }
        }
    }

    /**
     * Returns the name of the requested operator.
     *
     * @param string $operator The name or short name of an operator
     *
     * @return string The name of an operator
     *
     * @throws \InvalidArgumentException if the given operator is not known
     */
    public function resolveOperator(string $operator): string
    {
        if (isset($this->shortOperators[$operator])) {
            return $this->shortOperators[$operator];
        }
        if (\array_key_exists($operator, $this->operators)) {
            return $operator;
        }
        throw new \InvalidArgumentException(sprintf('The operator "%s" is not known.', $operator));
    }
}
