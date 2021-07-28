<?php

namespace Oro\Bundle\WorkflowBundle\Serializer\Normalizer;

class ProcessTraversableNormalizer extends AbstractProcessNormalizer
{
    /**
     * {@inheritdoc}
     */
    public function normalize($object, string $format = null, array $context = [])
    {
        $normalizedData = [];

        foreach ($object as $key => $value) {
            $normalizedData[$key] = $this->serializer->normalize($value, $format, $context);
        }

        return $normalizedData;
    }

    /**
     * {@inheritdoc}
     */
    public function denormalize($data, string $type, string $format = null, array $context = [])
    {
        $denormalizedData = [];

        foreach ($data as $key => $value) {
            $denormalizedData[$key] = $this->serializer->denormalize($value, $type, $format, $context);
        }

        return $denormalizedData;
    }

    /**
     * {@inheritdoc}
     */
    public function supportsNormalization($data, string $format = null): bool
    {
        return is_array($data) || $data instanceof \Traversable;
    }

    /**
     * {@inheritdoc}
     */
    public function supportsDenormalization($data, string $type, string $format = null): bool
    {
        return is_array($data);
    }
}
