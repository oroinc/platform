<?php

namespace Oro\Bundle\FormBundle\Form\DataTransformer;

use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\UnexpectedTypeException;

/**
 * Transforms between data changeset formats for form data handling.
 */
class DataChangesetTransformer implements DataTransformerInterface
{
    public const DATA_KEY = 'data';

    #[\Override]
    public function transform($value): mixed
    {
        $result = [];
        if (null === $value || [] === $value) {
            return $result;
        }

        foreach ($value as $id => $changeSetRow) {
            $result[$id] = $changeSetRow[self::DATA_KEY];
        }

        return $result;
    }

    #[\Override]
    public function reverseTransform($value): mixed
    {
        $result = new ArrayCollection();
        if (!$value) {
            return $result;
        }

        if (!is_array($value) && !$value instanceof \Traversable) {
            throw new UnexpectedTypeException($value, 'array');
        }

        foreach ($value as $id => $changeSetRow) {
            $result->set(
                $id,
                [self::DATA_KEY => $changeSetRow]
            );
        }

        return $result;
    }
}
