<?php

namespace Oro\Bundle\CurrencyBundle\Form\DataTransformer;

use Oro\Bundle\CurrencyBundle\DoctrineExtension\Dbal\Types\MoneyValueType;
use Symfony\Component\Form\DataTransformerInterface;

/**
 * The aim of this transformer is converting value that we get from number form type to database format
 * and prevent fake update of money_value fields.
 */
class MoneyValueTransformer implements DataTransformerInterface
{
    /**
     * @inheritDoc
     */
    public function transform($value)
    {
        return $value;
    }

    /**
     * @param mixed $value
     *
     * @return mixed|string
     */
    public function reverseTransform($value)
    {
        if (!is_numeric($value)) {
            return $value;
        }

        if (false !== strpos($value, '.')) {
            list(, $decimalPart) = explode('.', $value);
            if (strlen($decimalPart) >= MoneyValueType::TYPE_SCALE) {
                return $value;
            }
        }

        return number_format($value, MoneyValueType::TYPE_SCALE, '.', '');
    }
}
