<?php

namespace Oro\Bundle\EmailBundle\Form\DataTransformer;

use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\UnexpectedTypeException;

class EmailAddressApiTransformer implements DataTransformerInterface
{
    /**
     * @var bool
     */
    protected $multiple;

    /**
     * @param bool $multiple
     */
    public function __construct($multiple = false)
    {
        $this->multiple = $multiple;
    }

    /**
     * {@inheritdoc}
     */
    public function transform($value)
    {
        if ($this->multiple) {
            if (null === $value) {
                return [];
            }

            if (!is_array($value)) {
                throw new UnexpectedTypeException($value, 'array');
            }

            return $this->normalizeArray($value);
        } else {
            if (null === $value) {
                return '';
            }

            if (!is_string($value)) {
                throw new UnexpectedTypeException($value, 'string');
            }

            return trim($value);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function reverseTransform($value)
    {
        if ($this->multiple) {
            if (!$value) {
                return [];
            }

            return $this->normalizeArray(is_string($value) ? explode(';', $value) : $value);
        } else {
            if (!$value) {
                return null;
            }

            return trim($value) ?: null;
        }
    }

    /**
     * @param string[] $value
     *
     * @return string[]
     */
    protected function normalizeArray(array $value)
    {
        if (empty($value)) {
            return $value;
        }

        return array_values(array_unique(array_filter(array_map('trim', $value))));
    }
}
