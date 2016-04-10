<?php

namespace Oro\Bundle\ApiBundle\Request\DocumentBuilder;

use Oro\Bundle\ApiBundle\Util\ConfigUtil;

class ArrayAccessor implements ObjectAccessorInterface
{
    /**
     * {@inheritdoc}
     */
    public function getClassName($object)
    {
        return array_key_exists(ConfigUtil::CLASS_NAME, $object)
            ? $object[ConfigUtil::CLASS_NAME]
            : null;
    }

    /**
     * {@inheritdoc}
     */
    public function getValue($object, $propertyName)
    {
        if (!$this->hasProperty($object, $propertyName)) {
            throw new \OutOfBoundsException(sprintf('The "%s" property does not exist.', $propertyName));
        }

        return $object[$propertyName];
    }

    /**
     * {@inheritdoc}
     */
    public function hasProperty($object, $propertyName)
    {
        // ignore "metadata" items
        if (ConfigUtil::CLASS_NAME === $propertyName) {
            return false;
        }

        return array_key_exists($propertyName, $object);
    }

    /**
     * {@inheritdoc}
     */
    public function toArray($object)
    {
        // remove "metadata" items
        unset($object[ConfigUtil::CLASS_NAME]);

        return $object;
    }
}
