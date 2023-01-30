<?php

namespace Oro\Bundle\ApiBundle\Request\DocumentBuilder;

/**
 * Provides an access to properties of a different object types.
 */
class ObjectAccessor implements ObjectAccessorInterface
{
    /** @var ObjectAccessorInterface[] */
    private array $accessors = [];

    /**
     * {@inheritdoc}
     */
    public function getClassName(mixed $object): ?string
    {
        return $this->getObjectAccessor($object)->getClassName($object);
    }

    /**
     * {@inheritdoc}
     */
    public function getValue(mixed $object, string $propertyName): mixed
    {
        return $this->getObjectAccessor($object)->getValue($object, $propertyName);
    }

    /**
     * {@inheritdoc}
     */
    public function hasProperty(mixed $object, string $propertyName): bool
    {
        return $this->getObjectAccessor($object)->hasProperty($object, $propertyName);
    }

    /**
     * {@inheritdoc}
     */
    public function toArray(mixed $object): array
    {
        return $this->getObjectAccessor($object)->toArray($object);
    }

    private function getObjectAccessor(mixed $object): ObjectAccessorInterface
    {
        $objectType = get_debug_type($object);

        if (isset($this->accessors[$objectType])) {
            return $this->accessors[$objectType];
        }

        // currently only ArrayAccessor was implemented. It is enough for now.
        // in the future may be ArrayAccessAccessor will be implemented.
        if (\is_array($object)) {
            $this->accessors[$objectType] = new ArrayAccessor();
        } else {
            throw new \InvalidArgumentException(sprintf(
                'The object accessor for "%s" type does not exist.',
                $objectType
            ));
        }

        return $this->accessors[$objectType];
    }
}
