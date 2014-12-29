<?php

namespace Oro\Bundle\WorkflowBundle\Serializer\Normalizer;

class ProcessObjectNormalizer extends AbstractProcessNormalizer
{
    const SERIALIZED = '__SERIALIZED__';

    /**
     * {@inheritDoc}
     */
    public function normalize($object, $format = null, array $context = array())
    {
        return array(self::SERIALIZED => base64_encode(serialize($object)));
    }

    /**
     * {@inheritDoc}
     */
    public function denormalize($data, $class, $format = null, array $context = array())
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

    /**
     * {@inheritDoc}
     */
    public function supportsNormalization($data, $format = null)
    {
        return is_object($data);
    }

    /**
     * {@inheritDoc}
     */
    public function supportsDenormalization($data, $type, $format = null)
    {
        return is_array($data) && !empty($data[self::SERIALIZED]);
    }
}
