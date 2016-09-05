<?php

namespace Oro\Bundle\ApiBundle\Metadata;

use Oro\Bundle\ApiBundle\Processor\GetMetadata\MetadataContext;

/**
 * Provides an interface for different kind requests for additional metadata.
 */
interface MetadataExtraInterface
{
    /**
     * Gets a string that uniquely identifies a type of additional metadata.
     *
     * @return string
     */
    public function getName();

    /**
     * Makes modifications of the MetadataContext necessary to get required additional metadata.
     *
     * @param MetadataContext $context
     */
    public function configureContext(MetadataContext $context);

    /**
     * Returns a string that should be added to a cache key used by the metadata provider.
     *
     * @return string|null
     */
    public function getCacheKeyPart();
}
