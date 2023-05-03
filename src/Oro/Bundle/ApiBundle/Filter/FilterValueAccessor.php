<?php

namespace Oro\Bundle\ApiBundle\Filter;

use Oro\Component\PhpUtils\QueryStringUtil;

/**
 * The default implementation of a collection of the FilterValue objects.
 */
class FilterValueAccessor implements FilterValueAccessorInterface
{
    /** @var FilterValue[]|null */
    private ?array $parameters = null;
    /** @var array [group name => [filter key => FilterValue, ...], ...] */
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
            /** @var FilterValue $value */
            foreach ($this->groups[$this->defaultGroupName] as $value) {
                if ($value->getPath() === $key) {
                    $result = true;
                    break;
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
        $this->ensureInitialized();

        if (isset($this->parameters[$key])) {
            return $this->parameters[$key];
        }

        $result = null;
        if ($this->defaultGroupName && isset($this->groups[$this->defaultGroupName])) {
            /** @var FilterValue $value */
            foreach ($this->groups[$this->defaultGroupName] as $value) {
                if ($value->getPath() === $key) {
                    $result = $value;
                    break;
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
            if (isset($this->parameters[$key])) {
                $value->setSource($this->parameters[$key]);
            }
            $this->parameters[$key] = $value;
            $this->groups[$group][$key] = $value;
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
            /** @var FilterValue $item */
            foreach ($items as $item) {
                if (!$item->getSourceKey()) {
                    continue;
                }

                $path = $item->getPath();
                if ($path !== $group) {
                    $path = $group . '[' . implode('][', explode('.', $path)) . ']';
                }
                $operator = $item->getOperator();
                if ('eq' !== $operator) {
                    $path .= '[' . $operator . ']';
                }
                $params[$path] = $item->getSourceValue();
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
        $this->parameters[$key] = $value;
        $this->groups[$group][$key] = $value;
    }

    protected function normalizeOperator(?string $operator): string
    {
        if (!$operator) {
            $operator = 'eq';
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
