<?php

namespace Oro\Bundle\ApiBundle\Request;

use Oro\Bundle\ApiBundle\Metadata\EntityMetadata;

/**
 * This transformer delegates the transformation of an entity identifier to resolvers of
 * predefined entity identifiers and if no one resolver is not resolve it,
 * delegates the transformation to wrapped transformer.
 */
class CombinedEntityIdTransformer implements EntityIdTransformerInterface
{
    /** @var EntityIdTransformerInterface */
    private $mainTransformer;

    /** @var EntityIdResolverRegistry */
    private $resolverRegistry;

    /** @var RequestType */
    private $requestType;

    /**
     * @param EntityIdTransformerInterface $mainTransformer
     * @param EntityIdResolverRegistry     $resolverRegistry
     * @param RequestType                  $requestType
     */
    public function __construct(
        EntityIdTransformerInterface $mainTransformer,
        EntityIdResolverRegistry $resolverRegistry,
        RequestType $requestType
    ) {
        $this->mainTransformer = $mainTransformer;
        $this->resolverRegistry = $resolverRegistry;
        $this->requestType = $requestType;
    }

    /**
     * {@inheritdoc}
     */
    public function transform($id, EntityMetadata $metadata)
    {
        return $this->mainTransformer->transform($id, $metadata);
    }

    /**
     * {@inheritdoc}
     */
    public function reverseTransform($value, EntityMetadata $metadata)
    {
        $resolver = null;
        if (\is_string($value)) {
            $resolver = $this->resolverRegistry->getResolver($value, $metadata->getClassName(), $this->requestType);
        }
        if (null !== $resolver) {
            return $resolver->resolve();
        }

        return $this->mainTransformer->reverseTransform($value, $metadata);
    }
}
