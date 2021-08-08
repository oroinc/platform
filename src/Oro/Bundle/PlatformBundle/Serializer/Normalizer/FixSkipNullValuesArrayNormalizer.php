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

        return self::removeNullValues($object);
    }

    /**
     * {@inheritDoc}
     */
    public function supportsNormalization($data, string $format = null)
    {
        return \is_array($data) && !empty($data);
    }

    private static function removeNullValues(array $data): array
    {
        if (!ArrayUtil::isAssoc($data)) {
            return self::removeNullValuesFromCollection($data);
        }

        $result = $data;
        foreach ($data as $name => $value) {
            if (null === $value) {
                unset($result[$name]);
            } elseif (\is_array($value) && !empty($value)) {
                $result[$name] = self::removeNullValues($value);
            }
        }

        return $result;
    }

    private static function removeNullValuesFromCollection(array $data): array
    {
        $result = [];
        foreach ($data as $index => $value) {
            if (\is_array($value) && !empty($value)) {
                $value = self::removeNullValues($value);
            }
            $result[$index] = $value;
        }

        return $result;
    }
}
