<?php

namespace Oro\Bundle\WorkflowBundle\Serializer\Normalizer;

class ProcessTraversableNormalizer extends AbstractProcessNormalizer
{
    #[\Override]
    public function normalize($object, string $format = null, array $context = [])
    {
        $normalizedData = [];

        foreach ($object as $key => $value) {
            $normalizedData[$key] = $this->serializer->normalize($value, $format, $context);
        }

        return $normalizedData;
    }

    #[\Override]
    public function denormalize($data, string $type, string $format = null, array $context = [])
    {
        $denormalizedData = [];

        foreach ($data as $key => $value) {
            $denormalizedData[$key] = $this->serializer->denormalize($value, $type, $format, $context);
        }

        return $denormalizedData;
    }

    #[\Override]
    public function supportsNormalization($data, string $format = null): bool
    {
        return is_array($data) || $data instanceof \Traversable;
    }

    #[\Override]
    public function supportsDenormalization($data, string $type, string $format = null): bool
    {
        return is_array($data);
    }
}
