<?php

namespace Oro\Bundle\ImportExportBundle\Serializer\Normalizer;

use Symfony\Component\Serializer\Normalizer\DenormalizerInterface as BaseDenormalizerInterface;

interface DenormalizerInterface extends BaseDenormalizerInterface
{
    /**
     * Checks whether the given class is supported for denormalization by this normalizer
     *
     * @param mixed  $data   Data to denormalize from.
     * @param string $type   The class to which the data should be denormalized.
     * @param string $format The format being deserialized from.
     * @param array  $context options available to the denormalizer
     *
     * @return Boolean
     */
    public function supportsDenormalization($data, $type, $format = null, array $context = array());
}
