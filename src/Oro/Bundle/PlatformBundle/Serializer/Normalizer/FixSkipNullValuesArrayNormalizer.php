<?php

namespace Oro\Bundle\PlatformBundle\Serializer\Normalizer;

use Oro\Component\PhpUtils\ArrayUtil;
use Symfony\Component\Serializer\Normalizer\AbstractObjectNormalizer;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\SerializerAwareInterface;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * Fixes handling of the "skip_null_values" flag for associative array.
 * This is required to make sure that Symfony and JMS serializers handles `null` values in the same way.
 */
class FixSkipNullValuesArrayNormalizer implements NormalizerInterface, SerializerAwareInterface
{
    private ?SerializerInterface $serializer = null;

    #[\Override]
    public function setSerializer(SerializerInterface $serializer): void
    {
        $this->serializer = $serializer;
    }

    #[\Override]
    public function normalize(
        mixed $data,
        ?string $format = null,
        array $context = []
    ): array|string|int|float|bool|\ArrayObject|null {
        if (!($context[AbstractObjectNormalizer::SKIP_NULL_VALUES] ?? false)) {
            return $data;
        }

        $normalizer = $this->serializer instanceof NormalizerInterface
            ? $this->serializer
            : null;

        return self::removeNullValues($data, $format, $context, $normalizer);
    }

    #[\Override]
    public function supportsNormalization($data, ?string $format = null, array $context = []): bool
    {
        return \is_array($data) && !empty($data);
    }

    private static function removeNullValues(
        array $data,
        ?string $format,
        array $context,
        ?NormalizerInterface $normalizer
    ): array {
        if (!ArrayUtil::isAssoc($data)) {
            return self::removeNullValuesFromCollection($data, $format, $context, $normalizer);
        }

        $result = $data;
        foreach ($data as $name => $value) {
            if (null === $value) {
                unset($result[$name]);
            } elseif (\is_array($value) && !empty($value)) {
                $result[$name] = self::removeNullValues($value, $format, $context, $normalizer);
            } elseif (\is_object($value) && null !== $normalizer) {
                $result[$name] = $normalizer->normalize($value, $format, $context);
            }
        }

        return $result;
    }

    private static function removeNullValuesFromCollection(
        array $data,
        ?string $format,
        array $context,
        ?NormalizerInterface $normalizer
    ): array {
        $result = [];
        foreach ($data as $index => $value) {
            if (\is_array($value) && !empty($value)) {
                $value = self::removeNullValues($value, $format, $context, $normalizer);
            } elseif (\is_object($value) && null !== $normalizer) {
                $value = $normalizer->normalize($value, $format, $context);
            }
            $result[$index] = $value;
        }

        return $result;
    }

    public function getSupportedTypes(?string $format): array
    {
        return [
            '*' => false
        ];
    }
}
