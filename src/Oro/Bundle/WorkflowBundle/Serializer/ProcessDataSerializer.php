<?php

namespace Oro\Bundle\WorkflowBundle\Serializer;

use Symfony\Component\Serializer\Serializer;

class ProcessDataSerializer extends Serializer
{
    /**
     * {@inheritdoc}
     */
    public function normalize($data, $format = null, array $context = array())
    {
        $this->normalizerCache = array(); // disable internal cache

        return parent::normalize($data, $format, $context);
    }

    /**
     * {@inheritdoc}
     */
    public function denormalize($data, $type, $format = null, array $context = array())
    {
        $this->denormalizerCache = array(); // disable internal cache;

        return parent::denormalize($data, $type, $format, $context);
    }
}
