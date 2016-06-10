<?php

namespace Oro\Bundle\ApiBundle\DataTransformer;

use Symfony\Component\Form\Extension\Core\DataTransformer\DateTimeToStringTransformer as BaseTransformer;

class TimeToStringTransformer extends BaseTransformer
{
    public function __construct()
    {
        parent::__construct('UTC', 'UTC', 'H:i:s');
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
