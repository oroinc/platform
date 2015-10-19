<?php

namespace Oro\Bundle\ApiBundle\Normalizer;

class DateTimeNormalizer implements ObjectNormalizerInterface
{
    /**
     * {@inheritdoc}
     */
    public function supports($object)
    {
        return $object instanceof \DateTime;
    }

    /**
     * {@inheritdoc}
     */
    public function normalize($object)
    {
        return $object;
    }
}
