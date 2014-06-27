<?php

namespace Oro\Bundle\UIBundle\PropertyAccess;

use Symfony\Component\PropertyAccess\Exception\InvalidPropertyPathException;
use Symfony\Component\PropertyAccess\Exception\NoSuchPropertyException;
use Symfony\Component\PropertyAccess\Exception\UnexpectedTypeException;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;

/**
 * Reads values from an object/array.
 * This class is fast than {@link Symfony\Component\PropertyAccess\PropertyAccessor},
 * but it cannot work with object/array graph, magic call.
 */
class SimpleReadPropertyAccessor implements PropertyAccessorInterface
{
    /** @var bool */
    private $magicCall;

    /**
     * @param bool $magicCall Set TRUE to enable the use of "__call"
     */
    public function __construct($magicCall = false)
    {
        $this->magicCall = $magicCall;
    }

    /**
     * {@inheritdoc}
     */
    public function getValue($objectOrArray, $propertyPath)
    {
        if (!is_string($propertyPath)) {
            throw new InvalidPropertyPathException(
                'The property path should be a string.',
                0,
                new UnexpectedTypeException($propertyPath, 'string')
            );
        } elseif (empty($propertyPath)) {
            throw new InvalidPropertyPathException('The property path should not be empty.');
        }

        if (is_array($objectOrArray)) {
            return isset($objectOrArray[$propertyPath]) || array_key_exists($propertyPath, $objectOrArray)
                ? $objectOrArray[$propertyPath]
                : null;
        } elseif (is_object($objectOrArray)) {
            return $this->getPropertyValue($objectOrArray, $propertyPath);
        }

        throw new UnexpectedTypeException($objectOrArray, 'array or object');
    }

    /**
     * {@inheritdoc}
     */
    public function setValue(&$objectOrArray, $propertyPath, $value)
    {
        throw new \RuntimeException('The "setValue" method is not implemented by this class.');
    }

    /**
     * Camelizes a given string.
     *
     * This method is a copy of appropriate method of {@link Symfony\Component\PropertyAccess\PropertyAccessor}
     *
     * @param  string $string Some string
     *
     * @return string The camelized version of the string
     */
    protected function camelize($string)
    {
        return preg_replace_callback(
            '/(^|_|\.)+(.)/',
            function ($match) {
                return ('.' === $match[1] ? '_' : '') . strtoupper($match[2]);
            },
            $string
        );
    }

    /**
     * Gets the property value from an object.
     *
     * The implementation of this method is similar as in readProperty method
     * of {@link Symfony\Component\PropertyAccess\PropertyAccessor}
     *
     * @param object $object   The object to read from.
     * @param string $property The property to read.
     *
     * @return mixed The value of the read property
     *
     * @throws NoSuchPropertyException If the property does not exist or is not public.
     *
     * @SuppressWarnings(PHPMD.NPathComplexity)
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    protected function getPropertyValue($object, $property)
    {
        $camelProp = $this->camelize($property);
        $reflClass = new \ReflectionClass($object);

        $getter = 'get' . $camelProp;
        if ($reflClass->hasMethod($getter) && $reflClass->getMethod($getter)->isPublic()) {
            return $object->$getter();
        }
        $isser = 'is' . $camelProp;
        if ($reflClass->hasMethod($isser) && $reflClass->getMethod($isser)->isPublic()) {
            return $object->$isser();
        }
        $hasser = 'has' . $camelProp;
        if ($reflClass->hasMethod($hasser) && $reflClass->getMethod($hasser)->isPublic()) {
            return $object->$hasser();
        }
        if ($reflClass->hasMethod('__get') && $reflClass->getMethod('__get')->isPublic()) {
            return $object->$property;
        }
        $classHasProperty = $reflClass->hasProperty($property);
        if ($classHasProperty && $reflClass->getProperty($property)->isPublic()) {
            return $object->$property;
        }
        if (!$classHasProperty && property_exists($object, $property)) {
            // Needed to support \stdClass instances. We need to explicitly
            // exclude $classHasProperty, otherwise if in the previous clause
            // a *protected* property was found on the class, property_exists()
            // returns true, consequently the following line will result in a
            // fatal error.
            return $object->$property;
        }
        if ($this->magicCall && $reflClass->hasMethod('__call') && $reflClass->getMethod('__call')->isPublic()) {
            // we call the getter and hope the __call do the job
            return $object->$getter();
        }

        throw new NoSuchPropertyException(
            sprintf(
                'Neither the property "%s" nor one of the methods "%s()", ' .
                '"%s()", "%s()", "__get()" or "__call()" exist and have public access in ' .
                'class "%s".',
                $property,
                $getter,
                $isser,
                $hasser,
                $reflClass->name
            )
        );
    }
}
