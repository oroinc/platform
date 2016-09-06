<?php

/*
 * This file is a copy of {@see Symfony\Component\PropertyAccess\PropertyAccessor}
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 */

namespace Oro\Component\PropertyAccess;

use Symfony\Component\PropertyAccess\Exception;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Symfony\Component\PropertyAccess\PropertyPath;
use Symfony\Component\PropertyAccess\StringUtil;
use Symfony\Component\PropertyAccess\PropertyPathInterface;

/**
 * Writes and reads values to/from an object/array graph.
 *
 * This class is mostly a copy of {@see Symfony\Component\PropertyAccess\PropertyAccessor} v2.7.3
 * but it has the following advantages:
 * * allows to use the same syntax of the property path for objects and arrays
 * * a bit faster getValue method
 * * fixes some issues of Symfony's PropertyAccessor, for example working with magic __get method
 * New features:
 * * 'remove' method is added to allow to remove items from arrays or objects
 *
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
class PropertyAccessor implements PropertyAccessorInterface
{
    const VALUE = 0;
    const IS_REF = 1;

    /** @var bool */
    protected $magicCall;

    /** @var bool */
    protected $ignoreInvalidIndices;

    /** @var PropertyPathInterface[] */
    protected $propertyPathCache = [];

    /**
     * @param bool $magicCall            Determines whether the use of "__call" is enabled
     * @param bool $ignoreInvalidIndices Determines whether a reading a value by non-existing index
     *                                   is allowed or an exception should be thrown
     *                                   This has no influence on writing non-existing indices
     *                                   with setValue() and remove()
     *                                   setValue() always create an array item on-the-fly
     *                                   remove() always ignore non-existing indices
     */
    public function __construct($magicCall = false, $ignoreInvalidIndices = false)
    {
        $this->magicCall            = $magicCall;
        $this->ignoreInvalidIndices = $ignoreInvalidIndices;
    }

    /**
     * Sets the value at the end of the property path of the object.
     *
     * Example:
     *
     * <code>
     *     use Oro\Component\PropertyAccess;
     *
     *     $propertyAccessor = new PropertyAccessor();
     *
     *     echo $propertyAccessor->setValue($object, 'child.name', 'John');
     *     // equals echo $object->getChild()->setName('John');
     * </code>
     *
     * This method first tries to find a public setter for each property in the
     * path. The name of the setter must be the camel-cased property name
     * prefixed with "set".
     *
     * If the setter does not exist, this method tries to find a public
     * property. The value of the property is then changed.
     *
     * If neither is found, an exception is thrown.
     *
     * @param object|array                 $object       The object or array to modify
     * @param string|PropertyPathInterface $propertyPath The property path to modify
     * @param mixed                        $value        The value to set at the end of the property path
     *
     * @throws Exception\InvalidPropertyPathException If an object or a property path has invalid type.
     * @throws Exception\NoSuchPropertyException If a property does not exist or is not public.
     */
    public function setValue(&$object, $propertyPath, $value)
    {
        $propertyPath = $this->getPropertyPath($propertyPath);

        $path      = $propertyPath->getElements();
        $values    = &$this->readPropertiesUntil($object, $propertyPath, true);
        $overwrite = true;

        // Add the root object to the list
        array_unshift(
            $values,
            [self::VALUE => &$object, self::IS_REF => true]
        );

        for ($i = count($values) - 1; $i >= 0; --$i) {
            $object = &$values[$i][self::VALUE];

            if ($overwrite) {
                if (!is_object($object) && !is_array($object)) {
                    throw new Exception\NoSuchPropertyException(
                        sprintf(
                            'PropertyAccessor requires a graph of objects or arrays to operate on, ' .
                            'but it found type "%s" while trying to traverse path "%s" at property "%s".',
                            gettype($object),
                            (string)$propertyPath,
                            $path[$i]
                        )
                    );
                }

                $property = $path[$i];

                $this->writeValue($object, $property, $value);
            }

            $value     = &$object;
            $overwrite = !$values[$i][self::IS_REF];
        }
    }

    /**
     * Removes the property at the end of the property path of the object.
     *
     * Example:
     *
     * <code>
     *     use Oro\Component\PropertyAccess;
     *
     *     $propertyAccessor = new PropertyAccessor();
     *
     *     echo $propertyAccessor->remove($object, 'child.name');
     *     // equals echo $object->getChild()->removeName();
     * </code>
     *
     * This method first tries to find a public setter for each property in the
     * path. The name of the unsetter must be the camel-cased property name
     * prefixed with "remove".
     *
     * If it is found, an exception is thrown.
     *
     * @param object|array                 $object       The object or array to modify
     * @param string|PropertyPathInterface $propertyPath The property path to modify
     *
     * @throws Exception\InvalidPropertyPathException If an object or a property path has invalid type.
     * @throws Exception\NoSuchPropertyException If a property does not exist or is not public.
     */
    public function remove(&$object, $propertyPath)
    {
        $propertyPath = $this->getPropertyPath($propertyPath);

        $path   = $propertyPath->getElements();
        $values = &$this->readPropertiesUntil($object, $propertyPath);

        if (count($values) < count($path) - 1) {
            return;
        }

        // Add the root object to the list
        array_unshift(
            $values,
            [self::VALUE => &$object, self::IS_REF => true]
        );

        $value     = null;
        $overwrite = true;
        $lastIndex = count($values) - 1;
        for ($i = $lastIndex; $i >= 0; --$i) {
            $object = &$values[$i][self::VALUE];

            if ($overwrite) {
                if (!is_object($object) && !is_array($object)) {
                    throw new Exception\NoSuchPropertyException(
                        sprintf(
                            'PropertyAccessor requires a graph of objects or arrays to operate on, ' .
                            'but it found type "%s" while trying to traverse path "%s" at property "%s".',
                            gettype($object),
                            (string)$propertyPath,
                            $path[$i]
                        )
                    );
                }

                $propertyPath = $path[$i];

                if ($i === $lastIndex) {
                    $this->unsetProperty($object, $propertyPath);
                } else {
                    $this->writeValue($object, $propertyPath, $value);
                }
            }

            $value     = &$object;
            $overwrite = !$values[$i][self::IS_REF];
        }
    }

    /**
     * Returns the value at the end of the property path of the object.
     *
     * Example:
     *
     * <code>
     *     use Oro\Component\PropertyAccess;
     *
     *     $propertyAccessor = new PropertyAccessor();
     *
     *     echo $propertyAccessor->getValue($object, 'child.name);
     *     // equals echo $object->getChild()->getName();
     * </code>
     *
     * This method first tries to find a public getter for each property in the
     * path. The name of the getter must be the camel-cased property name
     * prefixed with "get", "is", or "has".
     *
     * If the getter does not exist, this method tries to find a public
     * property. The value of the property is then returned.
     *
     * If none of them are found, an exception is thrown.
     *
     * @param object|array                 $object       The object or array to traverse
     * @param string|PropertyPathInterface $propertyPath The property path to read
     *
     * @return mixed The value at the end of the property path
     *
     * @throws Exception\InvalidPropertyPathException If an object or a property path has invalid type.
     * @throws Exception\NoSuchPropertyException If a property does not exist or is not public.
     */
    public function getValue($object, $propertyPath)
    {
        $propertyPath = $this->getPropertyPath($propertyPath);

        $path   = $propertyPath->getElements();
        $length = count($path);
        $value  = $this->readValue($object, $path[0], !$this->ignoreInvalidIndices, $propertyPath, 0);
        for ($i = 1; $i < $length; ++$i) {
            $value = $this->readValue($value[self::VALUE], $path[$i], !$this->ignoreInvalidIndices, $propertyPath, $i);
        }

        return $value[self::VALUE];
    }

    /**
     * {@inheritdoc}
     */
    public function isReadable($objectOrArray, $propertyPath)
    {
        $propertyPath = $this->getPropertyPath($propertyPath);

        try {
            $this->readPropertiesUntil(
                $objectOrArray,
                $propertyPath,
                false,
                $this->ignoreInvalidIndices,
                $propertyPath->getLength()
            );

            return true;
        } catch (Exception\AccessException $e) {
            return false;
        } catch (Exception\UnexpectedTypeException $e) {
            return false;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function isWritable($objectOrArray, $propertyPath)
    {
        $propertyPath = $this->getPropertyPath($propertyPath);

        try {
            $propertyValues = $this->readPropertiesUntil(
                $objectOrArray,
                $propertyPath,
                false,
                true,
                $propertyPath->getLength()
            );

            // Add the root object to the list
            array_unshift($propertyValues, [
                self::VALUE => $objectOrArray,
                self::IS_REF => true,
            ]);

            for ($i = count($propertyValues) - 2; $i >= 0; --$i) {
                $objectOrArray = $propertyValues[$i][self::VALUE];

                $property = $propertyPath->getElement($i);

                if ($objectOrArray instanceof \ArrayAccess || is_array($objectOrArray)) {
                    return true;
                }
                if (!$this->isPropertyWritable($objectOrArray, $property)) {
                    return false;
                }

                if ($propertyValues[$i][self::IS_REF]) {
                    return true;
                }
            }

            return true;
        } catch (Exception\AccessException $e) {
            return false;
        } catch (Exception\UnexpectedTypeException $e) {
            return false;
        }
    }

    /**
     * Reads the path from an object up to a given path index.
     *
     * @param object|array          $object               The object or array to read from
     * @param PropertyPathInterface $propertyPath         The property path to read
     * @param bool                  $addMissing           Set true to allow create missing nested arrays on demand
     * @param bool                  $ignoreInvalidIndices Whether to ignore invalid indices or throw an exception
     * @param int                   $lastIndex            The index up to which should be read
     *
     * @return array The values read in the path.
     *
     * @throws Exception\NoSuchPropertyException If a value within the path is neither object nor array.
     *                                           If a non-existing index is accessed.
     */
    private function &readPropertiesUntil(
        &$object,
        PropertyPathInterface $propertyPath,
        $addMissing = false,
        $ignoreInvalidIndices = true,
        $lastIndex = -1
    ) {
        $values = [];
        $path   = $propertyPath->getElements();
        $length = count($path);
        if (-1 === $lastIndex) {
            $lastIndex = $length - 1;
        }

        for ($i = 0; $i < $lastIndex; ++$i) {
            if (!is_object($object) && !is_array($object)) {
                throw new Exception\NoSuchPropertyException(
                    sprintf(
                        'PropertyAccessor requires a graph of objects or arrays to operate on, ' .
                        'but it found type "%s" while trying to traverse path "%s" at property "%s".',
                        gettype($object),
                        (string)$propertyPath,
                        $path[$i]
                    )
                );
            }

            $property = $path[$i];

            // Create missing nested arrays on demand
            if (($object instanceof \ArrayAccess && !isset($object[$property]))
                || (is_array($object) && !array_key_exists($property, $object))
            ) {
                if ($addMissing) {
                    $object[$property] = $i + 1 < $length ? [] : null;
                } elseif ($ignoreInvalidIndices) {
                    break;
                }
            }

            $value = &$this->readValue($object, $property, !$ignoreInvalidIndices, $propertyPath, $i);

            $object = &$value[self::VALUE];

            $values[] = &$value;
        }

        return $values;
    }

    /**
     * Reads a value of the given property from an object or array.
     *
     * @param array|object          $object   The object or array to read from
     * @param mixed                 $property The property or index to read
     * @param boolean               $strict
     * @param PropertyPathInterface $propertyPath
     * @param int                   $propertyPathIndex
     *
     * @return mixed
     *
     * @throws Exception\NoSuchPropertyException if the property does not exist or is not public
     *
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    protected function &readValue(
        &$object,
        $property,
        $strict,
        PropertyPathInterface $propertyPath = null,
        $propertyPathIndex = null
    ) {
        // Use an array instead of an object since performance is very crucial here
        $result = [self::VALUE => null, self::IS_REF => false];

        if (!$strict && null === $object) {
            // Ignore NULL object if non strict read is requested
        } elseif (is_array($object)) {
            if (isset($object[$property])) {
                $result[self::VALUE]  = &$object[$property];
                $result[self::IS_REF] = true;
            } elseif ($strict && !array_key_exists($property, $object)) {
                throw new Exception\NoSuchPropertyException(
                    sprintf('The key "%s" does exist in an array.', $property)
                );
            }
        } elseif ($object instanceof \ArrayAccess) {
            if (isset($object[$property])) {
                $result[self::VALUE] = $object[$property];
            } elseif ($strict) {
                $reflClass = new \ReflectionClass($object);
                throw new Exception\NoSuchPropertyException(
                    sprintf('The key "%s" does exist in class "%s".', $property, $reflClass->name)
                );
            }
        } elseif (is_object($object)) {
            $reflClass = new \ReflectionClass($object);
            $camelized = $this->camelize($property);
            if ($reflClass->hasMethod($getter = 'get' . $camelized)
                && $reflClass->getMethod($getter)->isPublic()
            ) {
                $result[self::VALUE] = $object->$getter();
            } elseif ($reflClass->hasMethod($isser = 'is' . $camelized)
                && $reflClass->getMethod($isser)->isPublic()
            ) {
                $result[self::VALUE] = $object->$isser();
            } elseif ($reflClass->hasMethod($hasser = 'has' . $camelized)
                && $reflClass->getMethod($hasser)->isPublic()
            ) {
                $result[self::VALUE] = $object->$hasser();
            } elseif ($reflClass->hasMethod($getsetter = lcfirst($camelized))
                && $reflClass->getMethod($getsetter)->isPublic()
            ) {
                // jQuery style, e.g. read: last(), write: last($item)
                $result[self::VALUE] = $object->$getsetter();
            } elseif ($reflClass->hasMethod('__get') && $reflClass->getMethod('__get')->isPublic()) {
                if ($reflClass->hasMethod('__isset') && $reflClass->getMethod('__isset')->isPublic()) {
                    if (!isset($object->$property)) {
                        throw new Exception\NoSuchPropertyException(
                            sprintf(
                                'The property "%s" cannot be got by "__get" method ' .
                                'because "__isset" method returns false. Class "%s".',
                                $property,
                                $reflClass->name
                            )
                        );
                    }
                }
                $result[self::VALUE] = $object->$property;
            } else {
                $hasProp = $reflClass->hasProperty($property);
                if ($hasProp && $reflClass->getProperty($property)->isPublic()) {
                    $result[self::VALUE]  = &$object->$property;
                    $result[self::IS_REF] = true;
                } elseif (!$hasProp && property_exists($object, $property)) {
                    // Needed to support \stdClass instances. We need to explicitly
                    // exclude $hasProp, otherwise if in the previous clause
                    // a *protected* property was found on the class, property_exists()
                    // returns true, consequently the following line will result in a
                    // fatal error.
                    $result[self::VALUE]  = &$object->$property;
                    $result[self::IS_REF] = true;
                } elseif ($this->magicCall
                    && $reflClass->hasMethod('__call')
                    && $reflClass->getMethod('__call')->isPublic()
                ) {
                    // we call the getter and hope the __call do the job
                    $result[self::VALUE] = $object->$getter();
                } else {
                    $methods = [$getter, $getsetter, $isser, $hasser, '__get'];
                    if ($this->magicCall) {
                        $methods[] = '__call';
                    }

                    throw new Exception\NoSuchPropertyException(
                        sprintf(
                            'Neither the property "%s" nor one of the methods "%s()" ' .
                            'exist and have public access in class "%s".',
                            $property,
                            implode('()", "', $methods),
                            $reflClass->name
                        )
                    );
                }
            }
        } else {
            if ($propertyPath !== null && $propertyPathIndex !== null) {
                throw new Exception\NoSuchPropertyException(
                    sprintf(
                        'PropertyAccessor requires a graph of objects or arrays to operate on, ' .
                        'but it found type "%s" while trying to traverse path "%s" at property "%s".',
                        gettype($object),
                        (string)$propertyPath,
                        $propertyPath->getElements()[$propertyPathIndex]
                    )
                );
            } else {
                throw new Exception\NoSuchPropertyException(
                    sprintf(
                        'Unexpected object type. Expected "array or object", "%s" given.',
                        is_object($object) ? get_class($object) : gettype($object)
                    )
                );
            }
        }

        // Objects are always passed around by reference
        if (!$result[self::IS_REF] && is_object($result[self::VALUE])) {
            $result[self::IS_REF] = true;
        }

        return $result;
    }

    /**
     * Sets the value of a property in the given object.
     *
     * @param array|object $object   The object or array to write to
     * @param mixed        $property The property or index to write
     * @param mixed        $value    The value to write
     *
     * @throws Exception\NoSuchPropertyException If the property does not exist or is not public.
     *
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    protected function writeValue(&$object, $property, $value)
    {
        if ($object instanceof \ArrayAccess || is_array($object)) {
            $object[$property] = $value;
        } elseif (is_object($object)) {
            $reflClass = new \ReflectionClass($object);
            $camelized = $this->camelize($property);
            $singulars = (array)StringUtil::singularify($camelized);

            if (is_array($value) || $value instanceof \Traversable) {
                $methods = $this->findAdderAndRemover($reflClass, $singulars);

                // Use addXxx() and removeXxx() to write the collection
                if (null !== $methods) {
                    $this->writeCollection($object, $property, $value, $methods[0], $methods[1]);

                    return;
                }
            }

            $setter           = 'set' . $camelized;
            $getsetter        = lcfirst($camelized); // jQuery style, e.g. read: last(), write: last($item)
            $classHasProperty = $reflClass->hasProperty($property);

            if ($this->isMethodAccessible($reflClass, $setter, 1)) {
                $object->$setter($value);
            } elseif ($this->isMethodAccessible($reflClass, $getsetter, 1)) {
                $object->$getsetter($value);
            } elseif ($this->isMethodAccessible($reflClass, '__set', 2)) {
                $object->$property = $value;
            } elseif ($classHasProperty && $reflClass->getProperty($property)->isPublic()) {
                $object->$property = $value;
            } elseif (!$classHasProperty && property_exists($object, $property)) {
                // Needed to support \stdClass instances. We need to explicitly
                // exclude $classHasProperty, otherwise if in the previous clause
                // a *protected* property was found on the class, property_exists()
                // returns true, consequently the following line will result in a
                // fatal error.
                $object->$property = $value;
            } elseif ($this->magicCall && $this->isMethodAccessible($reflClass, '__call', 2)) {
                // we call the getter and hope the __call do the job
                $object->$setter($value);
            } else {
                throw new Exception\NoSuchPropertyException(
                    sprintf(
                        'Neither the property "%s" nor one of the methods %s"%s()", "%s()", ' .
                        '"__set()" or "__call()" exist and have public access in class "%s".',
                        $property,
                        implode(
                            '',
                            array_map(
                                function ($singular) {
                                    return '"add' . $singular . '()"/"remove' . $singular . '()", ';
                                },
                                $singulars
                            )
                        ),
                        $setter,
                        $getsetter,
                        $reflClass->name
                    )
                );
            }
        } else {
            throw new Exception\NoSuchPropertyException(
                sprintf(
                    'Unexpected object type. Expected "array or object", "%s" given.',
                    is_object($object) ? get_class($object) : gettype($object)
                )
            );
        }
    }

    /**
     * Adjusts a collection-valued property by calling add*() and remove*() methods.
     *
     * @param object             $object       The object to write to
     * @param string             $property     The property to write
     * @param array|\Traversable $collection   The collection to write
     * @param string             $addMethod    The add*() method
     * @param string             $removeMethod The remove*() method
     */
    protected function writeCollection($object, $property, $collection, $addMethod, $removeMethod)
    {
        // At this point the add and remove methods have been found
        // Use iterator_to_array() instead of clone in order to prevent side effects
        // see https://github.com/symfony/symfony/issues/4670
        $itemsToAdd    = is_object($collection) ? iterator_to_array($collection) : $collection;
        $itemToRemove  = [];
        $propertyValue = $this->readValue($object, $property, false);
        $previousValue = $propertyValue[self::VALUE];

        if (is_array($previousValue) || $previousValue instanceof \Traversable) {
            foreach ($previousValue as $previousItem) {
                foreach ($collection as $key => $item) {
                    if ($item === $previousItem) {
                        // Item found, don't add
                        unset($itemsToAdd[$key]);

                        // Next $previousItem
                        continue 2;
                    }
                }

                // Item not found, add to remove list
                $itemToRemove[] = $previousItem;
            }
        }

        foreach ($itemToRemove as $item) {
            $object->{$removeMethod}($item);
        }

        foreach ($itemsToAdd as $item) {
            $object->{$addMethod}($item);
        }
    }

    /**
     * Returns whether a property is writable in the given object.
     *
     * @param object $object   The object to write to
     * @param string $property The property to write
     *
     * @return bool Whether the property is writable
     */
    private function isPropertyWritable($object, $property)
    {
        if (!is_object($object)) {
            return false;
        }

        $reflClass = new \ReflectionClass($object);

        $camelized = $this->camelize($property);
        $setter = 'set' . $camelized;
        $getsetter = lcfirst($camelized); // jQuery style, e.g. read: last(), write: last($item)
        $classHasProperty = $reflClass->hasProperty($property);

        if ($this->isMethodAccessible($reflClass, $setter, 1)
            || $this->isMethodAccessible($reflClass, $getsetter, 1)
            || $this->isMethodAccessible($reflClass, '__set', 2)
            || ($classHasProperty && $reflClass->getProperty($property)->isPublic())
            || (!$classHasProperty && property_exists($object, $property))
            || ($this->magicCall && $this->isMethodAccessible($reflClass, '__call', 2))) {
            return true;
        }

        $singulars = (array) StringUtil::singularify($camelized);

        // Any of the two methods is required, but not yet known
        if (null !== $this->findAdderAndRemover($reflClass, $singulars)) {
            return true;
        }

        return false;
    }

    /**
     * Unsets a property in the given object.
     *
     * @param array|object $object   The object or array to unset from
     * @param mixed        $property The property or index to unset
     *
     * @throws Exception\NoSuchPropertyException If the property does not exist or is not public.
     *
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    protected function unsetProperty(&$object, $property)
    {
        if ($object instanceof \ArrayAccess) {
            if (isset($object[$property])) {
                unset($object[$property]);
            }
        } elseif (is_array($object)) {
            unset($object[$property]);
        } elseif (is_object($object)) {
            $reflClass = new \ReflectionClass($object);
            $unsetter  = 'remove' . $this->camelize($property);

            if ($this->isMethodAccessible($reflClass, $unsetter, 0)) {
                $object->$unsetter();
            } elseif ($this->isMethodAccessible($reflClass, '__unset', 1)) {
                unset($object->$property);
            } elseif ($this->magicCall && $this->isMethodAccessible($reflClass, '__call', 2)) {
                // we call the unsetter and hope the __call do the job
                $object->$unsetter();
            } else {
                throw new Exception\NoSuchPropertyException(
                    sprintf(
                        'Neither one of the methods "%s()", ' .
                        '"__unset()" or "__call()" exist and have public access in class "%s".',
                        $unsetter,
                        $reflClass->name
                    )
                );
            }
        } else {
            throw new Exception\NoSuchPropertyException(
                sprintf(
                    'Unexpected object type. Expected "array or object", "%s" given.',
                    is_object($object) ? get_class($object) : gettype($object)
                )
            );
        }
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
     * Searches for add and remove methods.
     *
     * @param \ReflectionClass $reflClass The reflection class for the given object
     * @param array            $singulars The singular form of the property name or null
     *
     * @return array|null An array containing the adder and remover when found, null otherwise
     */
    protected function findAdderAndRemover(\ReflectionClass $reflClass, array $singulars)
    {
        foreach ($singulars as $singular) {
            $addMethod    = 'add' . $singular;
            $removeMethod = 'remove' . $singular;

            $addMethodFound    = $this->isMethodAccessible($reflClass, $addMethod, 1);
            $removeMethodFound = $this->isMethodAccessible($reflClass, $removeMethod, 1);

            if ($addMethodFound && $removeMethodFound) {
                return [$addMethod, $removeMethod];
            }
        }

        return null;
    }

    /**
     * Returns whether a method is public and has the number of required parameters.
     *
     * @param \ReflectionClass $class      The class of the method
     * @param string           $methodName The method name
     * @param int              $parameters The number of parameters
     *
     * @return bool Whether the method is public and has $parameters required parameters
     */
    protected function isMethodAccessible(\ReflectionClass $class, $methodName, $parameters)
    {
        if ($class->hasMethod($methodName)) {
            $method = $class->getMethod($methodName);

            if ($method->isPublic()
                && $method->getNumberOfRequiredParameters() <= $parameters
                && $method->getNumberOfParameters() >= $parameters
            ) {
                return true;
            }
        }

        return false;
    }

    /**
     * Gets a PropertyPath instance and caches it.
     *
     * @param string|PropertyPath $propertyPath
     *
     * @return PropertyPath
     */
    protected function getPropertyPath($propertyPath)
    {
        if ($propertyPath instanceof PropertyPathInterface) {
            return $propertyPath;
        }
        if (isset($this->propertyPathCache[$propertyPath])) {
            return $this->propertyPathCache[$propertyPath];
        }

        $propertyPathInstance = new PropertyPath($propertyPath);

        return $this->propertyPathCache[$propertyPath] = $propertyPathInstance;
    }
}
