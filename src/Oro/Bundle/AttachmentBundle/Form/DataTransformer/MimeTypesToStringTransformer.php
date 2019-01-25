<?php

namespace Oro\Bundle\AttachmentBundle\Form\DataTransformer;

use Oro\Bundle\AttachmentBundle\Tools\MimeTypesConverter;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;

/**
 * The data transformer that is used to transform the list of MIME types
 * from a string contains MIME types delimited by linefeed (\n) symbol to an array and vise versa.
 */
class MimeTypesToStringTransformer implements DataTransformerInterface
{
    /**
     * {@inheritdoc}
     */
    public function transform($value)
    {
        if (is_string($value)) {
            $value = MimeTypesConverter::convertToArray($value);
        } elseif (null !== $value && !is_array($value)) {
            throw new TransformationFailedException('Expected an array or a string.');
        }

        return $value;
    }

    /**
     * {@inheritdoc}
     */
    public function reverseTransform($value)
    {
        if ('' === $value) {
            return null;
        }

        if (is_array($value)) {
            $value = MimeTypesConverter::convertToString($value);
        } elseif (!is_string($value)) {
            throw new TransformationFailedException('Expected an array or a string.');
        }

        return $value;
    }
}
