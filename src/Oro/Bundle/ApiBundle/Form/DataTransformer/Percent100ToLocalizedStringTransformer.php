<?php

namespace Oro\Bundle\ApiBundle\Form\DataTransformer;

use Symfony\Component\Form\Extension\Core\DataTransformer\NumberToLocalizedStringTransformer;

/**
 * Transforms a value between a percentage value multiplied by 100 and a string.
 */
class Percent100ToLocalizedStringTransformer extends NumberToLocalizedStringTransformer
{
    public const PERCENT_SCALE = 12;

    public function __construct(
        int $scale = null,
        ?bool $grouping = false,
        ?int $roundingMode = \NumberFormatter::ROUND_HALFUP,
        string $locale = null
    ) {
        parent::__construct(($scale ?? self::PERCENT_SCALE) + 2, $grouping, $roundingMode, $locale);
    }

    /**
     * {@inheritDoc}
     */
    public function transform($value)
    {
        $result = parent::transform(
            is_numeric($value)
                ? round((float)$value / 100.0, self::PERCENT_SCALE + 2)
                : $value
        );
        if (is_numeric($result)) {
            $result = rtrim($result, '0');
        }

        return $result;
    }

    /**
     * {@inheritDoc}
     */
    public function reverseTransform($value)
    {
        $result = parent::reverseTransform($value);
        if (null !== $result) {
            $result = round($result * 100.0, self::PERCENT_SCALE);
        }

        return $result;
    }
}
