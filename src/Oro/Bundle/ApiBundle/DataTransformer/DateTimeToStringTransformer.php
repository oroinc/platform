<?php

namespace Oro\Bundle\ApiBundle\DataTransformer;

use Symfony\Component\Form\Extension\Core\DataTransformer\DateTimeToRfc3339Transformer as BaseTransformer;

class DateTimeToStringTransformer extends BaseTransformer
{
    public function __construct()
    {
        parent::__construct('UTC', 'UTC');
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
