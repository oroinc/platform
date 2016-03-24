<?php

namespace Oro\Component\EntitySerializer;

use Doctrine\Common\Util\ClassUtils;

class EntityDataAccessor implements DataAccessorInterface
{
    /** @var \ReflectionClass[] */
    private $reflCache = [];

    /**
     * {@inheritdoc}
     */
    public function hasGetter($className, $property)
    {
        $suffix = $this->camelize($property);

        $refl = $this->getReflectionClass($className);
        if ($refl->hasMethod('get' . $suffix)) {
            return true;
        }
        if ($refl->hasMethod('is' . $suffix)) {
            return true;
        }
        if ($refl->hasMethod('has' . $suffix)) {
            return true;
        }
        if ($refl->hasMethod($suffix)) {
            return true;
        }
        if ($refl->hasProperty($property)) {
            return true;
        }

        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function tryGetValue($object, $property, &$value)
    {
        if (is_array($object)) {
            if (array_key_exists($property, $object)) {
                $value = $object[$property];

                return true;
            }
        } else {
            $refl = $this->getReflectionClass(get_class($object));

            $suffix = $this->camelize($property);

            $accessor = 'get' . $suffix;
            if ($refl->hasMethod($accessor)) {
                $value = $object->{$accessor}();

                return true;
            }
            $accessor = 'is' . $suffix;
            if ($refl->hasMethod($accessor)) {
                $value = $object->{$accessor}();

                return true;
            }
            $accessor = 'has' . $suffix;
            if ($refl->hasMethod($accessor)) {
                $value = $object->{$accessor}();

                return true;
            }
            $accessor = $suffix;
            if ($refl->hasMethod($accessor)) {
                $value = $object->{$accessor}();

                return true;
            }
            if ($refl->hasProperty($property)) {
                $prop = $refl->getProperty($property);
                $prop->setAccessible(true);
                $value = $prop->getValue($object);

                return true;
            }
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
            if (is_array($object)) {
                throw new \RuntimeException(
                    sprintf(
                        'Cannot get a value of "%s" field.',
                        $property
                    )
                );
            } else {
                throw new \RuntimeException(
                    sprintf(
                        'Cannot get a value of "%s" field from "%s" entity.',
                        $property,
                        ClassUtils::getClass($object)
                    )
                );
            }
        };

        return $value;
    }

    /**
     * Camelizes a given string.
     *
     * @param string $string Some string
     *
     * @return string The camelized version of the string
     */
    protected function camelize($string)
    {
        return strtr(ucwords(strtr($string, ['_' => ' '])), [' ' => '']);
    }

    /**
     * Gets an instance of \ReflectionClass for the given class name
     *
     * @param string $className
     *
     * @return \ReflectionClass
     */
    protected function getReflectionClass($className)
    {
        if (isset($this->reflCache[$className])) {
            return $this->reflCache[$className];
        }

        $reflClass                   = new \ReflectionClass($className);
        $this->reflCache[$className] = $reflClass;

        return $reflClass;
    }
}
