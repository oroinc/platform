<?php

namespace Oro\Bundle\TestFrameworkBundle\Behat\Element\Transformers;

class PageSuffixTransformer implements NamePartsTransformerInterface
{
    /** {@inheritdoc} */
    public function isApplicable(array $nameParts)
    {
        return strtolower(end($nameParts)) !== 'page';
    }

    /** {@inheritdoc} */
    public function transform(array $nameParts)
    {
        return $nameParts + [count($nameParts) => 'page'];
    }
}
