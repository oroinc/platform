<?php

namespace Oro\Bundle\WorkflowBundle\Serializer\Normalizer;

/**
 * Normalizes traversable objects and arrays by processing each contained element through the serializer
 */
class ProcessTraversableNormalizer extends AbstractProcessNormalizer
{
    #[\Override]
    public function normalize(
        mixed $object,
        ?string $format = null,
        array $context = []
    ): float|int|bool|\ArrayObject|array|string|null {
        $normalizedData = [];

        foreach ($object as $key => $value) {
            $normalizedData[$key] = $this->serializer->normalize($value, $format, $context);
        }

        return $normalizedData;
    }

    #[\Override]
    public function denormalize($data, string $type, ?string $format = null, array $context = []): mixed
    {
        $denormalizedData = [];

        foreach ($data as $key => $value) {
            $denormalizedData[$key] = $this->serializer->denormalize($value, $type, $format, $context);
        }

        return $denormalizedData;
    }

    #[\Override]
    public function supportsNormalization($data, ?string $format = null, array $context = []): bool
    {
        return is_array($data) || $data instanceof \Traversable;
    }

    #[\Override]
    public function supportsDenormalization($data, string $type, ?string $format = null, array $context = []): bool
    {
        return is_array($data);
    }

    public function getSupportedTypes(?string $format): array
    {
        return ['object' => true];
    }
}
