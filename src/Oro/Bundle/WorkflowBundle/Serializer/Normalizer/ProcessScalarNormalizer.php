<?php

namespace Oro\Bundle\WorkflowBundle\Serializer\Normalizer;

class ProcessScalarNormalizer extends AbstractProcessNormalizer
{
    /**
     * {@inheritDoc}
     */
    public function normalize($object, $format = null, array $context = array())
    {
        return $object;
    }

    /**
     * {@inheritDoc}
     */
    public function denormalize($data, $class, $format = null, array $context = array())
    {
        return $data;
    }

    /**
     * {@inheritDoc}
     */
    public function supportsNormalization($data, $format = null)
    {
        return is_scalar($data) || $data === null;
    }

    /**
     * {@inheritDoc}
     */
    public function supportsDenormalization($data, $type, $format = null)
    {
        return is_scalar($data) || $data === null;
    }
}
