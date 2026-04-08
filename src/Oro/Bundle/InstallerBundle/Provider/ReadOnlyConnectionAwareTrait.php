<?php

declare(strict_types=1);

namespace Oro\Bundle\InstallerBundle\Provider;

/**
 * Provides default read-only connection storage and lookup logic.
 */
trait ReadOnlyConnectionAwareTrait
{
    protected array $readonlyConnections = [];

    public function setReadOnlyConnections(array $names): void
    {
        $this->readonlyConnections = $names;
    }

    protected function isReadOnlyConnection(string $name): bool
    {
        return in_array($name, $this->readonlyConnections, true);
    }
}
