<?php

namespace Oro\Bundle\WorkflowBundle\Serializer\Normalizer;

class ProcessTraversableNormalizer extends AbstractProcessNormalizer
{
    /**
     * {@inheritDoc}
     */
    public function normalize($object, $format = null, array $context = array())
    {
        $normalizedData = array();

        foreach ($object as $key => $value) {
            $normalizedData[$key] = $this->serializer->normalize($value, $context);
        }

        return $normalizedData;
    }

    /**
     * {@inheritDoc}
     */
    public function denormalize($data, $class, $format = null, array $context = array())
    {
        $denormalizedData = array();

        foreach ($data as $key => $value) {
            $denormalizedData[$key] = $this->serializer->denormalize($value, null, $format, $context);
        }

        return $denormalizedData;
    }

    /**
     * {@inheritDoc}
     */
    public function supportsNormalization($data, $format = null)
    {
        return is_array($data) || $data instanceof \Traversable;
    }

    /**
     * {@inheritDoc}
     */
    public function supportsDenormalization($data, $type, $format = null)
    {
        return is_array($data);
    }
}
