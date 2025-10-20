<?php

namespace Oro\Bundle\ImportExportBundle\Serializer;

use Symfony\Component\Serializer\Encoder\ContextAwareDecoderInterface;
use Symfony\Component\Serializer\Encoder\ContextAwareEncoderInterface;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\SerializerInterface as SymfonySerializerInterface;

/**
 * Interface which must be implemented by ImportExport serializer.
 */
interface SerializerInterface extends
    SymfonySerializerInterface,
    NormalizerInterface,
    DenormalizerInterface,
    ContextAwareEncoderInterface,
    ContextAwareDecoderInterface
{
}
