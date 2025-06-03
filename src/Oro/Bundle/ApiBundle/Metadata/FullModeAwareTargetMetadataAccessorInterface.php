<?php

namespace Oro\Bundle\ApiBundle\Metadata;

/**
 * This interface should be implemented by target metadata accessors
 * in case when these accessors support switching between full and not full modes.
 */
interface FullModeAwareTargetMetadataAccessorInterface
{
    public function isFullMode(): bool;

    public function setFullMode(bool $full = true): void;
}
