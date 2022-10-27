<?php

namespace Oro\Bundle\EntityBundle\ImportExport\Serializer;

use Oro\Bundle\EntityBundle\Entity\EntityFieldFallbackValue;
use Oro\Bundle\EntityBundle\Fallback\EntityFallbackResolver;
use Oro\Bundle\LocaleBundle\Model\LocaleSettings;
use Symfony\Component\Serializer\Normalizer\ContextAwareDenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\ContextAwareNormalizerInterface;

/**
 * Serializer implementation for EntityFieldFallbackValue class
 */
class EntityFieldFallbackValueNormalizer implements ContextAwareNormalizerInterface, ContextAwareDenormalizerInterface
{
    public const VIRTUAL_FIELD_NAME = 'value';

    private EntityFallbackResolver $fallbackResolver;

    private LocaleSettings $localeSettings;

    public function __construct(EntityFallbackResolver $fallbackResolver, LocaleSettings $localeSettings)
    {
        $this->fallbackResolver = $fallbackResolver;
        $this->localeSettings = $localeSettings;
    }

    /**
     * @param EntityFieldFallbackValue $object
     *
     * {@inheritdoc}
     */
    public function normalize($object, string $format = null, array $context = [])
    {
        if (!$object instanceof EntityFieldFallbackValue) {
            return null;
        }

        return [self::VIRTUAL_FIELD_NAME => $object->getFallback() ?: $object->getOwnValue()];
    }

    /**
     * {@inheritdoc}
     */
    public function denormalize($data, string $type, string $format = null, array $context = [])
    {
        $object = new EntityFieldFallbackValue();
        if (is_array($data) && array_key_exists(self::VIRTUAL_FIELD_NAME, $data)) {
            $value = $data[self::VIRTUAL_FIELD_NAME];
        } else {
            throw new \InvalidArgumentException('To denormalize EntityFieldFallbackValue you must specify its value');
        }

        if ($this->fallbackResolver->isFallbackConfigured(
            $value,
            $context['entityName'],
            $context['fieldName']
        )) {
            $object->setFallback($value);
        } else {
            $object->setScalarValue($this->parseScalarValue($value, $context['entityName'], $context['fieldName']));
        }

        return $object;
    }

    /**
     * {@inheritdoc}
     */
    public function supportsDenormalization($data, string $type, string $format = null, array $context = []): bool
    {
        return is_a($type, EntityFieldFallbackValue::class, true);
    }

    /**
     * {@inheritdoc}
     */
    public function supportsNormalization($data, string $format = null, array $context = []): bool
    {
        return $data instanceof EntityFieldFallbackValue;
    }

    /**
     * @param mixed $value
     * @param string $parentEntityName
     * @param string $fieldName
     *
     * @return mixed
     */
    private function parseScalarValue($value, string $parentEntityName, string $fieldName)
    {
        if (!\in_array(
            $this->fallbackResolver->getType($parentEntityName, $fieldName),
            [EntityFallbackResolver::TYPE_DECIMAL, EntityFallbackResolver::TYPE_INTEGER],
            false
        )) {
            return $value;
        }

        $position = 0;
        $formatter = new \NumberFormatter($this->localeSettings->getLocale(), \NumberFormatter::DECIMAL);
        $parsedValue = $formatter->parse($value, \NumberFormatter::TYPE_DOUBLE, $position);

        if (intl_is_failure($formatter->getErrorCode()) || $position < strlen($value) || is_infinite($parsedValue)) {
            // Returns value as-is after the failed parsing, because it still has to be passed to the validators which
            // are executed afterwards.
            return $value;
        }

        return fmod($parsedValue, 1) === 0.0 ? (int)$parsedValue : $parsedValue;
    }
}
