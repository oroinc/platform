<?php

namespace Oro\Bundle\ApiBundle\Request\DocumentBuilder;

/**
 * Provides an access to properties of a different object types.
 */
class ObjectAccessor implements ObjectAccessorInterface
{
    /** @var ObjectAccessorInterface[] */
    private $accessors = [];

    /**
     * {@inheritdoc}
     */
    public function getClassName($object): ?string
    {
        return $this->getObjectAccessor($object)->getClassName($object);
    }

    /**
     * {@inheritdoc}
     */
    public function getValue($object, string $propertyName)
    {
        return $this->getObjectAccessor($object)->getValue($object, $propertyName);
    }

    /**
     * {@inheritdoc}
     */
    public function hasProperty($object, string $propertyName): bool
    {
        return $this->getObjectAccessor($object)->hasProperty($object, $propertyName);
    }

    /**
     * {@inheritdoc}
     */
    public function toArray($object): array
    {
        return $this->getObjectAccessor($object)->toArray($object);
    }

    /**
     * @param mixed $object
     *
     * @return ObjectAccessorInterface
     */
    private function getObjectAccessor($object): ObjectAccessorInterface
    {
        $objectType = \is_object($object)
            ? \get_class($object)
            : \gettype($object);

        if (isset($this->accessors[$objectType])) {
            return $this->accessors[$objectType];
        }

        // currently only ArrayAccessor was implemented. It is enough for now.
        // in the future may be ArrayAccessAccessor will be implemented.
        if (\is_array($object)) {
            $this->accessors[$objectType] = new ArrayAccessor();
        } else {
            throw new \InvalidArgumentException(\sprintf(
                'The object accessor for "%s" type does not exist.',
                $objectType
            ));
        }

        return $this->accessors[$objectType];
    }
}
