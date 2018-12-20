<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Filter;

use Oro\Bundle\ApiBundle\Filter\FilterValue;
use Oro\Bundle\ApiBundle\Filter\FilterValueAccessorInterface;

class TestFilterValueAccessor implements FilterValueAccessorInterface
{
    /** @var FilterValue[] */
    private $values = [];

    /**
     * {@inheritdoc}
     */
    public function has(string $key): bool
    {
        return isset($this->values[$key]);
    }

    /**
     * {@inheritdoc}
     */
    public function get(string $key): ?FilterValue
    {
        return $this->values[$key] ?? null;
    }

    /**
     * {@inheritdoc}
     */
    public function getGroup(string $group): array
    {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function getDefaultGroupName(): ?string
    {
        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function setDefaultGroupName(?string $group): void
    {
    }

    /**
     * {@inheritdoc}
     */
    public function getAll(): array
    {
        return $this->values;
    }

    /**
     * {@inheritdoc}
     */
    public function set(string $key, ?FilterValue $value): void
    {
        if (null === $value) {
            unset($this->values[$key]);
        } else {
            $this->values[$key] = $value;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function remove(string $key): void
    {
        unset($this->values[$key]);
    }

    /**
     * {@inheritdoc}
     */
    public function getQueryString(): string
    {
        return '';
    }
}
