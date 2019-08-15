<?php

namespace Oro\Bundle\ApiBundle\Request\DocumentBuilder;

use Oro\Bundle\ApiBundle\Util\ConfigUtil;

/**
 * Provides an access to properties of arrays.
 */
class ArrayAccessor implements ObjectAccessorInterface
{
    /**
     * {@inheritdoc}
     */
    public function getClassName($object): ?string
    {
        return $object[ConfigUtil::CLASS_NAME] ?? null;
    }

    /**
     * {@inheritdoc}
     */
    public function getValue($object, string $propertyName)
    {
        if (!$this->hasProperty($object, $propertyName)) {
            throw new \OutOfBoundsException(sprintf('The "%s" property does not exist.', $propertyName));
        }

        return $object[$propertyName];
    }

    /**
     * {@inheritdoc}
     */
    public function hasProperty($object, string $propertyName): bool
    {
        // ignore "metadata" items
        if (ConfigUtil::CLASS_NAME === $propertyName) {
            return false;
        }

        return \array_key_exists($propertyName, $object);
    }

    /**
     * {@inheritdoc}
     */
    public function toArray($object): array
    {
        // remove "metadata" items
        unset($object[ConfigUtil::CLASS_NAME]);

        return $object;
    }
}
