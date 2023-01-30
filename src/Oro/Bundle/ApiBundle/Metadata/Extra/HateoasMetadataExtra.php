<?php

namespace Oro\Bundle\ApiBundle\Metadata\Extra;

use Oro\Bundle\ApiBundle\Filter\QueryStringAccessorInterface;
use Oro\Bundle\ApiBundle\Processor\GetMetadata\MetadataContext;

/**
 * An instance of this class can be added to the metadata extras of the context
 * to request metadata for HATEOAS links.
 */
class HateoasMetadataExtra implements MetadataExtraInterface
{
    public const NAME = 'hateoas';

    private QueryStringAccessorInterface $queryStringAccessor;

    /**
     * @param QueryStringAccessorInterface $queryStringAccessor An accessor to a query string contains
     *                                                          all requested filters.
     */
    public function __construct(QueryStringAccessorInterface $queryStringAccessor)
    {
        $this->queryStringAccessor = $queryStringAccessor;
    }

    /**
     * {@inheritdoc}
     */
    public function getName(): string
    {
        return self::NAME;
    }

    /**
     * {@inheritdoc}
     */
    public function configureContext(MetadataContext $context): void
    {
        // no any modifications of the MetadataContext is required
    }

    /**
     * {@inheritdoc}
     */
    public function getCacheKeyPart(): ?string
    {
        return self::NAME;
    }

    /**
     * Returns an instance of a class that can be used to get URL-encoded query string
     * representation of all requested filters.
     */
    public function getQueryStringAccessor(): QueryStringAccessorInterface
    {
        return $this->queryStringAccessor;
    }
}
