<?php

namespace Oro\Bundle\EntityConfigBundle\Form\DataTransformer;

use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;

class ConfigLabelTransformer implements DataTransformerInterface
{
    /**
     * @param mixed $value
     * @return mixed|void
     */
    public function transform($value)
    {
        $value = $value;

        return [];
    }


    /**

     */
    public function reverseTransform($value)
    {
        $value = $value;

        return [];
    }
}
