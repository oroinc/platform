<?php

namespace Oro\Bundle\ApiBundle\DataTransformer;

use Symfony\Component\Form\Extension\Core\DataTransformer\DateTimeToLocalizedStringTransformer as BaseTransformer;

class DateToStringTransformer extends BaseTransformer
{
    public function __construct()
    {
        parent::__construct(
            'UTC',
            'UTC',
            'yyyy-MM-dd',
            \IntlDateFormatter::NONE,
            \IntlDateFormatter::GREGORIAN,
            'yyyy-MM-dd'
        );
    }

    /**
     * {@inheritdoc}
     */
    public function transform($value)
    {
        if (null === $value) {
            return null;
        }

        return parent::transform($value);
    }
}
