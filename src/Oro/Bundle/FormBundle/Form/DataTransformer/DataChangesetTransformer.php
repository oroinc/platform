<?php

namespace Oro\Bundle\FormBundle\Form\DataTransformer;

use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\UnexpectedTypeException;

use Doctrine\Common\Collections\ArrayCollection;

class DataChangesetTransformer implements DataTransformerInterface
{
    const DATA_KEY = 'data';

    /**
     * {@inheritdoc}
     */
    public function transform($value)
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

    /**
     * {@inheritdoc}
     */
    public function reverseTransform($value)
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
