<?php

namespace Oro\Bundle\FormBundle\Form\DataTransformer;

use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;

/**
 * Transforms a value between an array and a string that is JSON representation of this array and vise versa.
 */
class ArrayToJsonTransformer implements DataTransformerInterface
{
    private bool $allowNull;

    public function __construct(bool $allowNull = false)
    {
        $this->allowNull = $allowNull;
    }

    /**
     * {@inheritdoc}
     */
    public function transform($value)
    {
        if (null === $value || [] === $value) {
            return '';
        }

        if (!\is_array($value)) {
            throw new TransformationFailedException('Expected an array.');
        }

        try {
            return json_encode($value, JSON_THROW_ON_ERROR);
        } catch (\Throwable $e) {
            throw new TransformationFailedException('Failed to build the JSON representation.', $e->getCode(), $e);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function reverseTransform($value)
    {
        if ('' === $value || '[]' === $value || '{}' === $value || null === $value) {
            return $this->allowNull ? null : [];
        }

        if (!\is_string($value)) {
            throw new TransformationFailedException('Expected a string.');
        }

        try {
            return json_decode($value, true, 512, JSON_THROW_ON_ERROR);
        } catch (\Throwable $e) {
            throw new TransformationFailedException('The malformed JSON.', $e->getCode(), $e);
        }
    }
}
