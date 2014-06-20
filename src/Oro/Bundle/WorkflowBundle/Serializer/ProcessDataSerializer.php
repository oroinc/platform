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
        $result = parent::normalize($data, $format, $context);

        $this->normalizerCache = array(); // disable internal cache

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function denormalize($data, $type, $format = null, array $context = array())
    {
        $result = parent::denormalize($data, $type, $format, $context);

        $this->denormalizerCache = array(); // disable internal cache

        return $result;
    }
}
