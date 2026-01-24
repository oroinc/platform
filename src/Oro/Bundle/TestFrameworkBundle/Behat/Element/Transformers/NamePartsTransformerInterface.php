<?php

namespace Oro\Bundle\TestFrameworkBundle\Behat\Element\Transformers;

/**
 * Defines the contract for transforming name parts during page/element name resolution.
 *
 * Implementations of this interface can check if they apply to a set of name parts and
 * transform them accordingly, enabling flexible name matching strategies for page and element lookups.
 */
interface NamePartsTransformerInterface
{
    /**
     * @param array $nameParts
     * @return boolean
     */
    public function isApplicable(array $nameParts);

    /**
     * @param array $nameParts
     * @return array $parts
     */
    public function transform(array $nameParts);
}
