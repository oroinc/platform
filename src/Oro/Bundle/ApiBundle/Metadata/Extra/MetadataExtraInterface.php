<?php

namespace Oro\Bundle\ApiBundle\Metadata\Extra;

use Oro\Bundle\ApiBundle\Processor\GetMetadata\MetadataContext;

/**
 * Provides an interface for different kind requests for additional metadata.
 */
interface MetadataExtraInterface
{
    /**
     * Gets a string that uniquely identifies a type of additional metadata.
     */
    public function getName(): string;

    /**
     * Makes modifications of the MetadataContext necessary to get required additional metadata.
     */
    public function configureContext(MetadataContext $context): void;

    /**
     * Returns a string that should be added to a cache key used by the metadata provider.
     */
    public function getCacheKeyPart(): ?string;
}
