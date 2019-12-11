<?php

namespace Oro\Bundle\ImportExportBundle\Serializer;

use Symfony\Component\Serializer\Encoder\ContextAwareEncoderInterface;
use Symfony\Component\Serializer\Normalizer\ContextAwareNormalizerInterface;
use Symfony\Component\Serializer\SerializerInterface as BaseSerializerInterface;

/**
 * Interface which must be implemented by ImportExport serializer.
 */
interface SerializerInterface extends
    BaseSerializerInterface,
    ContextAwareNormalizerInterface,
    ContextAwareEncoderInterface
{
}
