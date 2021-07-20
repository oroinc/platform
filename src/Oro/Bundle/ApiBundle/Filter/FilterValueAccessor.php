<?php

namespace Oro\Bundle\ApiBundle\Filter;

use Oro\Component\PhpUtils\QueryStringUtil;

/**
 * The default implementation of a collection of the FilterValue objects.
 */
class FilterValueAccessor implements FilterValueAccessorInterface
{
    /** @var FilterValue[] */
    private $parameters;

    /** @var array */
    private $groups;

    /** @var string|null */
    private $defaultGroupName;

    /**
     * {@inheritdoc}
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
     * {@inheritdoc}
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
     * {@inheritdoc}
     */
    public function getGroup(string $group): array
    {
        $this->ensureInitialized();

        if (!isset($this->groups[$group])) {
            return [];
        }

        return $this->groups[$group];
    }

    /**
     * {@inheritdoc}
     */
    public function getDefaultGroupName(): ?string
    {
        return $this->defaultGroupName;
    }

    /**
     * {@inheritdoc}
     */
    public function setDefaultGroupName(?string $group): void
    {
        $this->defaultGroupName = $group;
    }

    /**
     * {@inheritdoc}
     */
    public function getAll(): array
    {
        $this->ensureInitialized();

        return $this->parameters;
    }

    /**
     * {@inheritdoc}
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
     * {@inheritdoc}
     */
    public function remove(string $key): void
    {
        $this->ensureInitialized();

        $group = $this->extractGroup($key);

        unset($this->parameters[$key], $this->groups[$group][$key]);
    }

    /**
     * {@inheritdoc}
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
                    $path = $group . '[' . \implode('][', \explode('.', $path)) . ']';
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
        $delimPos = \strpos($key, '[');

        return false !== $delimPos && \substr($key, -1) === ']'
            ? \substr($key, 0, $delimPos)
            : $key;
    }
}
