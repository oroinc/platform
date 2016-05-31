<?php

namespace Oro\Bundle\LocaleBundle\Form\DataTransformer;

use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;
use Symfony\Component\Form\Exception\UnexpectedTypeException;

class MultipleValueTransformer implements DataTransformerInterface
{
    /**
     * @var string
     */
    protected $defaultField;

    /**
     * @var string
     */
    protected $collectionField;

    /**
     * @param string $defaultField
     * @param string $collectionField
     */
    public function __construct($defaultField, $collectionField)
    {
        $this->defaultField = $defaultField;
        $this->collectionField = $collectionField;
    }

    /**
     * {@inheritdoc}
     */
    public function transform($value)
    {
        if (null === $value) {
            return null;
        }

        if (!is_array($value)) {
            throw new UnexpectedTypeException($value, 'array');
        }

        $result = [$this->defaultField => null, $this->collectionField => []];

        if (isset($value[null])) {
            $result[$this->defaultField] = $value[null];
            unset($value[null]);
        }

        $result[$this->collectionField] = $value;

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function reverseTransform($value)
    {
        if (null === $value) {
            return null;
        }

        if (!is_array($value)) {
            throw new UnexpectedTypeException($value, 'array');
        }

        if (!array_key_exists($this->defaultField, $value)) {
            throw new TransformationFailedException('Value does not contain default value');
        }

        if (!array_key_exists($this->collectionField, $value)) {
            throw new TransformationFailedException('Value does not contain collection value');
        }

        $result = $value[$this->collectionField];
        $result[null] = $value[$this->defaultField];

        return $result;
    }
}
