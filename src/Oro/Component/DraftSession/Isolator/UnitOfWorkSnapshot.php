<?php

declare(strict_types=1);

namespace Oro\Component\DraftSession\Isolator;

/**
 * Immutable value object that holds a snapshot of UnitOfWork state captured
 * by {@see NonDraftEntitiesUnitOfWorkIsolator::isolateNonDraftEntities()} or
 * {@see DraftEntitiesUnitOfWorkIsolator::isolateDraftEntities()}.
 */
class UnitOfWorkSnapshot
{
    /**
     * @param array<string, mixed> $state Map of UnitOfWork property names to their values.
     */
    public function __construct(
        private readonly array $state,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function getState(): array
    {
        return $this->state;
    }
}
