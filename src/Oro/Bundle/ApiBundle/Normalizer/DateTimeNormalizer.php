<?php

namespace Oro\Bundle\ApiBundle\Normalizer;

/**
 * This normalizer tells the ObjectNormalizer to skip a normalization of \DateTime.
 */
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
