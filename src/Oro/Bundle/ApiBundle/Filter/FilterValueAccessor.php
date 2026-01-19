<?php

namespace Oro\Bundle\ApiBundle\Filter;

use Oro\Component\PhpUtils\QueryStringUtil;

/**
 * The default implementation of a collection of the FilterValue objects.
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
class FilterValueAccessor implements FilterValueAccessorInterface
{
    protected const DEFAULT_OPERATOR = 'eq';

    /** @var array|null [filter key => [FilterValue, ...] */
    private ?array $parameters = null;
    /** @var array [group name => [filter key => [FilterValue, ...], ...], ...] */
    private array $groups;
    private ?string $defaultGroupName = null;

    /**
     * {@inheritDoc}
     */
    public function has(string $key): bool
    {
        $this->ensureInitialized();

        if (isset($this->parameters[$key])) {
            return true;
        }

        $result = false;
        if ($this->defaultGroupName && isset($this->groups[$this->defaultGroupName])) {
            foreach ($this->groups[$this->defaultGroupName] as $values) {
                /** @var FilterValue $value */
                foreach ($values as $value) {
                    if ($value->getPath() === $key) {
                        $result = true;
                        break;
                    }
                }
            }
        }

        return $result;
    }

    /**
     * {@inheritDoc}
     */
    public function get(string $key): ?FilterValue
    {
        $values = $this->getMultiple($key);
        if (!$values) {
            return null;
        }

        return end($values);
    }

    /**
     * Gets all filter values with the given key.
     * In additional finds filter values in the default filter's group if it is set.
     *
     * @return FilterValue[]
     */
    public function getMultiple(string $key): array
    {
        $this->ensureInitialized();

        if (isset($this->parameters[$key])) {
            return $this->parameters[$key];
        }

        $result = [];
        if ($this->defaultGroupName && isset($this->groups[$this->defaultGroupName])) {
            foreach ($this->groups[$this->defaultGroupName] as $values) {
                /** @var FilterValue $value */
                foreach ($values as $value) {
                    if ($value->getPath() === $key) {
                        $result[] = $value;
                        break;
                    }
                }
            }
        }

        return $result;
    }

    /**
     * {@inheritDoc}
     */
    public function getGroup(string $group): array
    {
        $resultGroups = [];
        $groups = $this->getGroupMultiple($group);
        foreach ($groups as $key => $values) {
            $resultGroups[$key] = end($values);
        }

        return $resultGroups;
    }

    /**
     * Gets all filter values from the given group.
     *
     * @param string $group
     *
     * @return array [filter key => [FilterValue, ...], ...]
     */
    public function getGroupMultiple(string $group): array
    {
        $this->ensureInitialized();

        return $this->groups[$group] ?? [];
    }

    /**
     * {@inheritDoc}
     */
    public function getDefaultGroupName(): ?string
    {
        return $this->defaultGroupName;
    }

    /**
     * {@inheritDoc}
     */
    public function setDefaultGroupName(?string $group): void
    {
        $this->defaultGroupName = $group;
    }

    /**
     * {@inheritDoc}
     */
    public function getAll(): array
    {
        $resultParameters = [];
        $parameters = $this->getAllMultiple();
        foreach ($parameters as $key => $values) {
            $resultParameters[$key] = end($values);
        }

        return $resultParameters;
    }

    /**
     * Gets all filter values.
     *
     * @return array [filter key => [FilterValue, ...], ...]
     */
    public function getAllMultiple(): array
    {
        $this->ensureInitialized();

        return $this->parameters;
    }

    /**
     * {@inheritDoc}
     */
    public function set(string $key, ?FilterValue $value): void
    {
        $this->ensureInitialized();

        $group = $this->extractGroup($key);
        if (null !== $value) {
            $value->setOperator($this->normalizeOperator($value->getOperator()));
            $this->setParameterWithRememberSource($group, $key, $value);
        } else {
            unset($this->parameters[$key], $this->groups[$group][$key]);
        }
    }

    /**
     * {@inheritDoc}
     */
    public function remove(string $key): void
    {
        $this->ensureInitialized();

        $group = $this->extractGroup($key);

        unset($this->parameters[$key], $this->groups[$group][$key]);
    }

    /**
     * {@inheritDoc}
     */
    public function getQueryString(): string
    {
        $this->ensureInitialized();

        $params = [];
        foreach ($this->groups as $group => $items) {
            foreach ($items as $values) {
                /** @var FilterValue $value */
                foreach ($values as $value) {
                    if (!$value->getSourceKey()) {
                        continue;
                    }

                    $path = $value->getPath();
                    if ($path !== $group) {
                        $path = $group . '[' . implode('][', explode('.', $path)) . ']';
                    }
                    $operator = $value->getOperator();
                    if (self::DEFAULT_OPERATOR !== $operator) {
                        $path .= '[' . $operator . ']';
                    }
                    $params[$path] = $value->getSourceValue();
                }
            }
        }

        return QueryStringUtil::buildQueryString($params);
    }

    /**
     * Initializes this object to be ready to work.
     */
    protected function initialize(): void
    {
        $this->parameters = [];
        $this->groups = [];
    }

    protected function setParameter(string $group, string $key, FilterValue $value): void
    {
        $valueIndex = null;
        if (isset($this->parameters[$key])) {
            /** @var FilterValue $existingValue */
            foreach ($this->parameters[$key] as $existingValueIndex => $existingValue) {
                if ($value->getOperator() === $existingValue->getOperator()) {
                    $valueIndex = $existingValueIndex;
                }
            }
        }
        if (null === $valueIndex) {
            $valueIndex = \count($this->parameters[$key] ?? []);
        }
        $this->parameters[$key][$valueIndex] = $value;
        $this->groups[$group][$key][$valueIndex] = $value;
    }

    protected function setParameterWithRememberSource(string $group, string $key, FilterValue $value): void
    {
        $valueIndex = null;
        if (isset($this->parameters[$key])) {
            /** @var FilterValue $existingValue */
            foreach ($this->parameters[$key] as $existingValueIndex => $existingValue) {
                if ($value->getOperator() === $existingValue->getOperator()) {
                    $valueIndex = $existingValueIndex;
                    $value->setSource($existingValue);
                }
            }
        }
        if (null === $valueIndex) {
            $valueIndex = \count($this->parameters[$key] ?? []);
        }
        $this->parameters[$key][$valueIndex] = $value;
        $this->groups[$group][$key][$valueIndex] = $value;
    }

    protected function normalizeOperator(?string $operator): string
    {
        if (!$operator) {
            $operator = self::DEFAULT_OPERATOR;
        }

        return $operator;
    }

    /**
     * Makes sure this object is initialized and ready to work.
     */
    private function ensureInitialized(): void
    {
        if (null === $this->parameters) {
            $this->initialize();
        }
    }

    private function extractGroup(string $key): string
    {
        $delimPos = strpos($key, '[');

        return false !== $delimPos && str_ends_with($key, ']')
            ? substr($key, 0, $delimPos)
            : $key;
    }
}
