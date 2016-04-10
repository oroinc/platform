<?php

namespace Oro\Bundle\ApiBundle\Request\DocumentBuilder;

class ObjectAccessor implements ObjectAccessorInterface
{
    /** @var ObjectAccessorInterface[] */
    protected $accessors = [];

    /**
     * {@inheritdoc}
     */
    public function getClassName($object)
    {
        return $this->getObjectAccessor($object)->getClassName($object);
    }

    /**
     * {@inheritdoc}
     */
    public function getValue($object, $propertyName)
    {
        return $this->getObjectAccessor($object)->getValue($object, $propertyName);
    }

    /**
     * {@inheritdoc}
     */
    public function hasProperty($object, $propertyName)
    {
        return $this->getObjectAccessor($object)->hasProperty($object, $propertyName);
    }

    /**
     * {@inheritdoc}
     */
    public function toArray($object)
    {
        return $this->getObjectAccessor($object)->toArray($object);
    }

    /**
     * @param mixed $object
     *
     * @return ObjectAccessorInterface
     */
    protected function getObjectAccessor($object)
    {
        $objectType = is_object($object)
            ? get_class($object)
            : gettype($object);

        if (isset($this->accessors[$objectType])) {
            return $this->accessors[$objectType];
        }

        // currently only ArrayAccessor was implemented. It is enough for now.
        // in the future may be ArrayAccessAccessor will be implemented.
        if (is_array($object)) {
            $this->accessors[$objectType] = new ArrayAccessor();
        } else {
            throw new \RuntimeException(
                sprintf(
                    'The object accessor for "%s" type does not exist.',
                    is_object($object) ? get_class($object) : gettype($object)
                )
            );
        }

        return $this->accessors[$objectType];
    }
}
