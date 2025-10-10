<?php

namespace Oro\Bundle\CurrencyBundle\ImportExport\Serializer\Normalizer;

use Oro\Bundle\CurrencyBundle\Entity\MultiCurrency;
use Oro\Bundle\LocaleBundle\Formatter\NumberFormatter;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

/**
 * Provides normalization means for currency.
 */
class MultiCurrencyNormalizer implements NormalizerInterface
{
    private NumberFormatter $formatter;

    public function __construct(NumberFormatter $formatter)
    {
        $this->formatter = $formatter;
    }

    #[\Override]
    public function supportsNormalization($data, ?string $format = null, array $context = []): bool
    {
        return $data instanceof MultiCurrency;
    }

    #[\Override]
    public function normalize(
        mixed $object,
        ?string $format = null,
        array $context = []
    ): float|int|bool|\ArrayObject|array|string|null {
        return $this->formatter->formatCurrency($object->getValue(), $object->getCurrency());
    }

    public function getSupportedTypes(?string $format): array
    {
        return [MultiCurrency::class => true];
    }
}
