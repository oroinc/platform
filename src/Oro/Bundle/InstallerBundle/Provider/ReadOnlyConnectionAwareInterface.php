<?php

declare(strict_types=1);

namespace Oro\Bundle\InstallerBundle\Provider;

/**
 * Interface for services that need read-only DBAL connection names.
 */
interface ReadOnlyConnectionAwareInterface
{
    public function setReadOnlyConnections(array $names): void;
}
