<?php

namespace Oro\Bundle\ApiBundle\Form\DataTransformer;

use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Extension\Core\DataTransformer\DateTimeToLocalizedStringTransformer as BaseTransformer;

/**
 * This data transformer is used to wrap
 * "Symfony\Component\Form\Extension\Core\DataTransformer\DateTimeToLocalizedStringTransformer"
 * to prevent timezone conversion in "reverseTransform" method for case if the input string contains
 * a date without the time.
 */
class DateTimeToLocalizedStringTransformer implements DataTransformerInterface
{
    /** @var BaseTransformer */
    private $innerTransformer;

    /**
     * @param BaseTransformer $innerTransformer
     */
    public function __construct(BaseTransformer $innerTransformer)
    {
        $this->innerTransformer = $innerTransformer;
    }

    /**
     * {@inheritdoc}
     */
    public function transform($value)
    {
        return $this->innerTransformer->transform($value);
    }

    /**
     * {@inheritdoc}
     */
    public function reverseTransform($value)
    {
        // add the time part to prevent using the local timezone for a date without the time
        if (\is_string($value) && $value && false === \strpos($value, 'T')) {
            $value .= 'T00:00:00Z';
        }

        return $this->innerTransformer->reverseTransform($value);
    }
}
