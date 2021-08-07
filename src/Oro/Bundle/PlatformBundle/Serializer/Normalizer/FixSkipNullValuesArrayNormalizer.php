<?php

namespace Oro\Bundle\PlatformBundle\Serializer\Normalizer;

use Oro\Component\PhpUtils\ArrayUtil;
use Symfony\Component\Serializer\Normalizer\AbstractObjectNormalizer;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

/**
 * Fixes handling of the "skip_null_values" flag for associative array.
 * This is required to make sure that Symfony and JMS serializers handles `null` values in the same way.
 */
class FixSkipNullValuesArrayNormalizer implements NormalizerInterface
{
    /**
     * {@inheritDoc}
     */
    public function normalize($object, string $format = null, array $context = [])
    {
        if (!($context[AbstractObjectNormalizer::SKIP_NULL_VALUES] ?? false)) {
            return $object;
        }

        $result = $object;
        foreach ($object as $key => $value) {
            if (null === $value) {
                unset($result[$key]);
            }
        }

        return $result;
    }

    /**
     * {@inheritDoc}
     */
    public function supportsNormalization($data, string $format = null)
    {
        return $data && \is_array($data) && ArrayUtil::isAssoc($data);
    }
}
