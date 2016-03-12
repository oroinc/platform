<?php

namespace Oro\Bundle\ActionBundle\Tests\Unit;

/**
 * The trait provide private object property write access faster than through Reflection
 * Recommended only for test purposes
 */
trait AccessorHelperTrait
{
    protected function writePrivate($object, $propertyName, $value)
    {
        $accessor = function & ($object, $property) {
            $link = &\Closure::bind(
                function &() use ($property) {
                    return $this->$property;
                },
                $object,
                $object
            );

            return $link();
        };

        $ref = &$accessor($object, $propertyName);
        $ref = $value;
    }
}
