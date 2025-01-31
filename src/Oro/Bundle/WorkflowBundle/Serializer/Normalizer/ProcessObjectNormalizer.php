<?php

namespace Oro\Bundle\WorkflowBundle\Serializer\Normalizer;

class ProcessObjectNormalizer extends AbstractProcessNormalizer
{
    const SERIALIZED = '__SERIALIZED__';

    #[\Override]
    public function normalize($object, ?string $format = null, array $context = [])
    {
        return array(self::SERIALIZED => base64_encode(serialize($object)));
    }

    #[\Override]
    public function denormalize($data, string $type, ?string $format = null, array $context = [])
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
    public function supportsNormalization($data, $format = null): bool
    {
        return is_object($data);
    }

    #[\Override]
    public function supportsDenormalization($data, string $type, ?string $format = null): bool
    {
        return is_array($data) && !empty($data[self::SERIALIZED]);
    }
}
