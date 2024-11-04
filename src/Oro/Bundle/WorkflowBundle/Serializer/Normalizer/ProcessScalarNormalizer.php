<?php

namespace Oro\Bundle\WorkflowBundle\Serializer\Normalizer;

class ProcessScalarNormalizer extends AbstractProcessNormalizer
{
    #[\Override]
    public function normalize($object, string $format = null, array $context = [])
    {
        return $object;
    }

    #[\Override]
    public function denormalize($data, string $type, string $format = null, array $context = [])
    {
        return $data;
    }

    #[\Override]
    public function supportsNormalization($data, $format = null): bool
    {
        return is_scalar($data) || $data === null;
    }

    #[\Override]
    public function supportsDenormalization($data, string $type, string $format = null): bool
    {
        return is_scalar($data) || $data === null;
    }
}
