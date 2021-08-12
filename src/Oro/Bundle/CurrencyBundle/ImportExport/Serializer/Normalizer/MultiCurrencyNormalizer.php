<?php

namespace Oro\Bundle\CurrencyBundle\ImportExport\Serializer\Normalizer;

use Oro\Bundle\CurrencyBundle\Entity\MultiCurrency;
use Oro\Bundle\LocaleBundle\Formatter\NumberFormatter;
use Symfony\Component\Serializer\Normalizer\ContextAwareNormalizerInterface;

/**
 * Provides normalization means for currency.
 */
class MultiCurrencyNormalizer implements ContextAwareNormalizerInterface
{
    private NumberFormatter $formatter;

    public function __construct(NumberFormatter $formatter)
    {
        $this->formatter = $formatter;
    }

    /**
     * {@inheritdoc}
     */
    public function supportsNormalization($data, string $format = null, array $context = []): bool
    {
        return $data instanceof MultiCurrency;
    }

    /**
     * {@inheritdoc}
     */
    public function normalize($object, string $format = null, array $context = [])
    {
        return $this->formatter->formatCurrency($object->getValue(), $object->getCurrency());
    }
}
