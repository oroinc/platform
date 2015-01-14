<?php

namespace Oro\Bundle\FormBundle\Tests\Unit\Stub;

use Symfony\Component\Form\DataTransformerInterface;

class StubTransformer implements DataTransformerInterface
{
    /**
     * {@inheritdoc}
     */
    public function transform($value)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function reverseTransform($value)
    {
    }
}
