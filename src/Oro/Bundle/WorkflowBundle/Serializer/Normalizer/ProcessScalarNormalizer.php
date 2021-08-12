<?php

namespace Oro\Bundle\WorkflowBundle\Serializer\Normalizer;

class ProcessScalarNormalizer extends AbstractProcessNormalizer
{
    /**
     * {@inheritDoc}
     */
    public function normalize($object, string $format = null, array $context = [])
    {
        return $object;
    }

    /**
     * {@inheritDoc}
     */
    public function denormalize($data, string $type, string $format = null, array $context = [])
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
    public function supportsDenormalization($data, string $type, string $format = null): bool
    {
        return is_scalar($data) || $data === null;
    }
}
