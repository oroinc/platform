<?php

namespace Oro\Bundle\TestFrameworkBundle\PHPUnit\Comparator;

use SebastianBergmann\Comparator\ObjectComparator;

class PartialObjectComparator extends ObjectComparator
{
    /** @var string */
    protected $objectType;

    /** @var string[] */
    protected $properties = [];

    /**
     * @param string $objectType
     * @param string[] $properties
     */
    public function __construct($objectType, $properties = [])
    {
        $this->validateObjectType($objectType, $properties);
        parent::__construct();
        $this->objectType = $objectType;
        $this->properties = $properties;
    }

    /**
     * {@inheritdoc}
     */
    public function accepts($expected, $actual)
    {
        return $expected instanceof $this->objectType && $actual instanceof $this->objectType;
    }

    /**
     * {@inheritdoc}
     */
    protected function toArray($object)
    {
        return array_intersect_key(
            parent::toArray($object),
            array_flip($this->properties)
        );
    }

    /**
     * @param string $type
     * @param string $properties
     *
     * @throws \InvalidArgumentException
     */
    protected function validateObjectType($type, $properties)
    {
        $ref = new \ReflectionClass($type);
        $typeProperties = array_map(
            function (\ReflectionProperty $property) {
                return $property->getName();
            },
            $ref->getProperties()
        );

        $missingProperties = array_diff($properties, $typeProperties);
        if ($missingProperties) {
            throw new \InvalidArgumentException(sprintf(
                'Following properties are missing for type "%s": "%s"',
                $type,
                implode(', ', $missingProperties)
            ));
        }
    }
}
