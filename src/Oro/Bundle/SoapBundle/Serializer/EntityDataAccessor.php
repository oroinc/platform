<?php

namespace Oro\Bundle\SoapBundle\Serializer;

use Doctrine\Common\Util\ClassUtils;

class EntityDataAccessor implements DataAccessorInterface
{
    /**
     * {@inheritdoc}
     */
    public function hasGetter($className, $property)
    {
        $suffix = ucfirst($property);

        if (method_exists($className, 'get' . $suffix)) {
            return true;
        }
        if (method_exists($className, 'is' . $suffix)) {
            return true;
        }
        if (method_exists($className, 'has' . $suffix)) {
            return true;
        }

        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function tryGetValue($object, $property, &$value)
    {
        $suffix = ucfirst($property);

        $accessor = 'get' . $suffix;
        if (method_exists($object, $accessor)) {
            $value = $object->$accessor();

            return true;
        }
        $accessor = 'is' . $suffix;
        if (method_exists($object, $accessor)) {
            $value = $object->$accessor();

            return true;
        }
        $accessor = 'has' . $suffix;
        if (method_exists($object, $accessor)) {
            $value = $object->$accessor();

            return true;
        }

        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function getValue($object, $property)
    {
        $value = null;
        if (!$this->tryGetValue($object, $property, $value)) {
            throw new \RuntimeException(
                sprintf(
                    'Cannot get a value of "%s" field from "%s" entity.',
                    $property,
                    ClassUtils::getClass($object)
                )
            );
        };

        return $value;
    }
}
