<?php

namespace Oro\Bundle\TestFrameworkBundle\Behat\Element\Transformers;

/**
 * Transforms name parts by appending 'page' suffix if not already present.
 *
 * This transformer ensures that page names always end with 'page', allowing flexible
 * page name matching where users can refer to pages with or without the 'page' suffix.
 */
class PageSuffixTransformer implements NamePartsTransformerInterface
{
    #[\Override]
    public function isApplicable(array $nameParts)
    {
        return strtolower(end($nameParts)) !== 'page';
    }

    #[\Override]
    public function transform(array $nameParts)
    {
        return $nameParts + [count($nameParts) => 'page'];
    }
}
