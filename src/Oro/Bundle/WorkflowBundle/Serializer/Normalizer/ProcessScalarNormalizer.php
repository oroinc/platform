<?php

namespace Oro\Bundle\WorkflowBundle\Serializer\Normalizer;

/**
 * Normalizer for scalar values (string, int, float, bool) and null in workflow process serialization
 * Provides pass-through normalization without data transformation for primitive data types
 */
class ProcessScalarNormalizer extends AbstractProcessNormalizer
{
    #[\Override]
    public function normalize(
        mixed $object,
        ?string $format = null,
        array $context = []
    ): float|int|bool|\ArrayObject|array|string|null {
        return $object;
    }

    #[\Override]
    public function denormalize($data, string $type, ?string $format = null, array $context = []): mixed
    {
        return $data;
    }

    #[\Override]
    public function supportsNormalization($data, $format = null, array $context = []): bool
    {
        return is_scalar($data) || $data === null;
    }

    #[\Override]
    public function supportsDenormalization($data, string $type, ?string $format = null, array $context = []): bool
    {
        return is_scalar($data) || $data === null;
    }

    public function getSupportedTypes(?string $format): array
    {
        return ['object' => true];
    }
}
