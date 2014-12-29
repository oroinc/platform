<?php

namespace Oro\Bundle\ImportExportBundle\Serializer\Normalizer;

use Symfony\Component\Serializer\Normalizer\NormalizerInterface as BaseNormalizerInterface;

interface NormalizerInterface extends BaseNormalizerInterface
{
    /**
     * @param mixed  $data    Data to normalize.
     * @param string $format  The format being (de-)serialized from or into.
     * @param array  $context Context options for the normalizer
     *
     * @return Boolean
     */
    public function supportsNormalization($data, $format = null, array $context = array());
}
