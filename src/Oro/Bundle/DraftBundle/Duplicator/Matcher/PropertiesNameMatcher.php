<?php

namespace Oro\Bundle\DraftBundle\Duplicator\Matcher;

use DeepCopy\Matcher\Matcher;

/**
 * Determines whether a filter can be applied to the specified properties
 */
class PropertiesNameMatcher implements Matcher
{
    /**
     * @var string[]
     */
    private $properties;

    /**
     * @param string[] $properties
     */
    public function __construct(array $properties = [])
    {
        $this->properties = $properties;
    }

    /**
     * @inheritDoc
     */
    public function matches($object, $property): bool
    {
        return in_array($property, $this->properties);
    }
}
