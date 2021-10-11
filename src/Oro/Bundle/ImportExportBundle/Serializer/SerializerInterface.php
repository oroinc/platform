<?php

namespace Oro\Bundle\ImportExportBundle\Serializer;

use Symfony\Component\Serializer\Encoder\ContextAwareDecoderInterface;
use Symfony\Component\Serializer\Encoder\ContextAwareEncoderInterface;
use Symfony\Component\Serializer\Normalizer\ContextAwareDenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\ContextAwareNormalizerInterface;
use Symfony\Component\Serializer\SerializerInterface as SymfonySerializerInterface;

/**
 * Interface which must be implemented by ImportExport serializer.
 */
interface SerializerInterface extends
    SymfonySerializerInterface,
    ContextAwareNormalizerInterface,
    ContextAwareDenormalizerInterface,
    ContextAwareEncoderInterface,
    ContextAwareDecoderInterface
{
}
