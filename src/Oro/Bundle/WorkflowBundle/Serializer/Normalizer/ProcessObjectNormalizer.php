<?php

namespace Oro\Bundle\WorkflowBundle\Serializer\Normalizer;

/**
 * Normalizes any object by serializing it to base64-encoded string
 * and denormalizes back using PHP's native serialization
 */
class ProcessObjectNormalizer extends AbstractProcessNormalizer
{
    public const SERIALIZED = '__SERIALIZED__';

    #[\Override]
    public function normalize(
        mixed $object,
        ?string $format = null,
        array $context = []
    ): float|int|bool|\ArrayObject|array|string|null {
        return array(self::SERIALIZED => base64_encode(serialize($object)));
    }

    #[\Override]
    public function denormalize($data, string $type, ?string $format = null, array $context = []): mixed
    {
        $value = $data[self::SERIALIZED];

        if (!is_string($value)) {
            return null;
        }

        $value = base64_decode($value);

        if (!is_string($value) || !$value) {
            return null;
        }

        return unserialize($value);
    }

    #[\Override]
    public function supportsNormalization($data, $format = null, array $context = []): bool
    {
        return is_object($data);
    }

    #[\Override]
    public function supportsDenormalization($data, string $type, ?string $format = null, array $context = []): bool
    {
        return is_array($data) && !empty($data[self::SERIALIZED]);
    }

    public function getSupportedTypes(?string $format): array
    {
        return ['object' => true];
    }
}
