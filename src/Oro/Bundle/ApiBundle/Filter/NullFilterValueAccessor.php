<?php

namespace Oro\Bundle\ApiBundle\Filter;

/**
 * Implements empty not changeable collection of the FilterValue objects.
 */
class NullFilterValueAccessor implements FilterValueAccessorInterface
{
    /**
     * {@inheritdoc}
     */
    public function has(string $key): bool
    {
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function get(string $key): ?FilterValue
    {
        return null;
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
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function set(string $key, ?FilterValue $value): void
    {
    }

    /**
     * {@inheritdoc}
     */
    public function remove(string $key): void
    {
    }

    /**
     * {@inheritdoc}
     */
    public function getQueryString(): string
    {
        return '';
    }
}
