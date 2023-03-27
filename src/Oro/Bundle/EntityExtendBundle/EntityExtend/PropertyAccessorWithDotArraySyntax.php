<?php

namespace Oro\Bundle\EntityExtendBundle\EntityExtend;

use Oro\Bundle\EntityExtendBundle\EntityReflectionClass;
use Oro\Bundle\EntityExtendBundle\Extend\ReflectionExtractor;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Symfony\Component\Cache\Adapter\AdapterInterface;
use Symfony\Component\Cache\Adapter\ApcuAdapter;
use Symfony\Component\Cache\Adapter\NullAdapter;
use Symfony\Component\PropertyAccess\Exception\AccessException;
use Symfony\Component\PropertyAccess\Exception\InvalidArgumentException;
use Symfony\Component\PropertyAccess\Exception\NoSuchIndexException;
use Symfony\Component\PropertyAccess\Exception\NoSuchPropertyException;
use Symfony\Component\PropertyAccess\Exception\UnexpectedTypeException;
use Symfony\Component\PropertyAccess\Exception\UninitializedPropertyException;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Symfony\Component\PropertyAccess\PropertyPath;
use Symfony\Component\PropertyAccess\PropertyPathInterface;
use Symfony\Component\PropertyInfo\PropertyReadInfo;
use Symfony\Component\PropertyInfo\PropertyReadInfoExtractorInterface;
use Symfony\Component\PropertyInfo\PropertyWriteInfo;
use Symfony\Component\PropertyInfo\PropertyWriteInfoExtractorInterface;

/**
 * Writes and reads values to/from an object/array graph.
 *
 * This class is mostly a copy of {@see \Symfony\Component\PropertyAccess\PropertyAccessor}
 * but it has the following advantages:
 * * allows using the same syntax of the property path for objects and arrays
 * * magic __get __set methods are always enabled to support ORO extended entities
 * * objects implementing \ArrayAccess are accessed as arrays, getter and setter methods are ignored
 * New features:
 * * 'remove' method is added to allow to remove items from arrays or objects
 *
 * @SuppressWarnings(PHPMD)
 */
class PropertyAccessorWithDotArraySyntax implements PropertyAccessorInterface
{
    /** @var int Allow none of the magic methods */
    public const DISALLOW_MAGIC_METHODS = ReflectionExtractor::DISALLOW_MAGIC_METHODS;
    /** @var int Allow magic __get methods */
    public const MAGIC_GET = ReflectionExtractor::ALLOW_MAGIC_GET;
    /** @var int Allow magic __set methods */
    public const MAGIC_SET = ReflectionExtractor::ALLOW_MAGIC_SET;
    /** @var int Allow magic __call methods */
    public const MAGIC_CALL = ReflectionExtractor::ALLOW_MAGIC_CALL;

    public const DO_NOT_THROW = 0;
    public const THROW_ON_INVALID_INDEX = 1;
    public const THROW_ON_INVALID_PROPERTY_PATH = 2;

    private const VALUE = 0;
    private const REF = 1;
    private const IS_REF_CHAINED = 2;
    private const CACHE_PREFIX_READ = 'r';
    private const CACHE_PREFIX_WRITE = 'w';
    private const CACHE_PREFIX_PROPERTY_PATH = 'p';

    private $magicMethodsFlags;
    private $ignoreInvalidIndices;
    private $ignoreInvalidProperty;

    /**
     * @var CacheItemPoolInterface
     */
    private $cacheItemPool;

    // customization start
    private $propertyPathCache = [];
    // customization end

    private $reflectionClassesCache = [];

    /**
     * @var PropertyReadInfoExtractorInterface
     */
    private $readInfoExtractor;

    /**
     * @var PropertyWriteInfoExtractorInterface
     */
    private $writeInfoExtractor;
    private $readPropertyCache = [];
    private $writePropertyCache = [];
    private const RESULT_PROTO = [self::VALUE => null];

    /**
     * Should not be used by application code. Use
     * {@link PropertyAccess::createPropertyAccessor()} instead.
     *
     * @param int $magicMethods A bitwise combination of the MAGIC_* constants
     *                          to specify the allowed magic methods (__get, __set, __call)
     *                          or self::DISALLOW_MAGIC_METHODS for none
     * @param int $throw A bitwise combination of the THROW_* constants
     *                   to specify when exceptions should be thrown
     * @param PropertyReadInfoExtractorInterface $readInfoExtractor
     * @param PropertyWriteInfoExtractorInterface $writeInfoExtractor
     */
    public function __construct(
        $magicMethods = self::MAGIC_GET | self::MAGIC_SET,
        $throw = self::THROW_ON_INVALID_PROPERTY_PATH,
        CacheItemPoolInterface $cacheItemPool = null,
        $readInfoExtractor = null,
        $writeInfoExtractor = null
    ) {
        if (\is_bool($magicMethods)) {
            $message = 'Passing a boolean as the first argument to "%s()" is deprecated.'
                . ' Pass a combination of bitwise flags instead (i.e an integer).';
            trigger_deprecation(
                'symfony/property-access',
                '5.2',
                $message,
                __METHOD__
            );

            $magicMethods = ($magicMethods ? self::MAGIC_CALL : 0) | self::MAGIC_GET | self::MAGIC_SET;
        } elseif (!\is_int($magicMethods)) {
            throw new \TypeError(
                sprintf(
                    'Argument 1 passed to "%s()" must be an integer, "%s" given.',
                    __METHOD__,
                    get_debug_type($readInfoExtractor)
                )
            );
        }

        if (\is_bool($throw)) {
            $message = 'Passing a boolean as the second argument to "%s()" is deprecated.'
                . ' Pass a combination of bitwise flags instead (i.e an integer).';
            trigger_deprecation(
                'symfony/property-access',
                '5.3',
                $message,
                __METHOD__
            );

            $throw = $throw ? self::THROW_ON_INVALID_INDEX : self::DO_NOT_THROW;

            if (!\is_bool($readInfoExtractor)) {
                $throw |= self::THROW_ON_INVALID_PROPERTY_PATH;
            }
        }

        if (\is_bool($readInfoExtractor)) {
            $message = 'Passing a boolean as the fourth argument to "%s()" is deprecated. '
                . 'Pass a combination of bitwise flags as the second argument instead (i.e an integer).';
            trigger_deprecation(
                'symfony/property-access',
                '5.3',
                $message,
                __METHOD__
            );

            if ($readInfoExtractor) {
                $throw |= self::THROW_ON_INVALID_PROPERTY_PATH;
            }

            $readInfoExtractor = $writeInfoExtractor;
            $writeInfoExtractor = 4 < \func_num_args() ? func_get_arg(4) : null;
        }

        if (null !== $readInfoExtractor && !$readInfoExtractor instanceof PropertyReadInfoExtractorInterface) {
            throw new \TypeError(
                sprintf(
                    'Argument 4 passed to "%s()" must be null or an instance of "%s", "%s" given.',
                    __METHOD__,
                    PropertyReadInfoExtractorInterface::class,
                    get_debug_type($readInfoExtractor)
                )
            );
        }

        if (null !== $writeInfoExtractor && !$writeInfoExtractor instanceof PropertyWriteInfoExtractorInterface) {
            throw new \TypeError(
                sprintf(
                    'Argument 5 passed to "%s()" must be null or an instance of "%s", "%s" given.',
                    __METHOD__,
                    PropertyWriteInfoExtractorInterface::class,
                    get_debug_type($writeInfoExtractor)
                )
            );
        }

        $this->magicMethodsFlags = $magicMethods;
        $this->ignoreInvalidIndices = 0 === ($throw & self::THROW_ON_INVALID_INDEX);
        // Replace the NullAdapter by the null value
        $this->cacheItemPool = $cacheItemPool instanceof NullAdapter ? null : $cacheItemPool;
        $this->ignoreInvalidProperty = 0 === ($throw & self::THROW_ON_INVALID_PROPERTY_PATH);
        $this->readInfoExtractor = $readInfoExtractor
            ?? new ReflectionExtractor([], null, null, false);
        $this->writeInfoExtractor = $writeInfoExtractor
            ?? new ReflectionExtractor(['set'], null, null, false);
    }

    /**
     * {@inheritdoc}
     */
    public function getValue($objectOrArray, $propertyPath)
    {
        $zval = [
            self::VALUE => $objectOrArray,
        ];
        if (\is_object($objectOrArray)
            // customization start
            && !$objectOrArray instanceof \ArrayAccess
            // customization end
            && false === strpbrk((string)$propertyPath, '.[')) {
            return $this->readProperty($zval, $propertyPath, $this->ignoreInvalidProperty)[self::VALUE];
        }

        $propertyPath = $this->getPropertyPath($propertyPath);
        $propertyValues = $this->readPropertiesUntil(
            $zval,
            $propertyPath,
            $propertyPath->getLength(),
            $this->ignoreInvalidIndices
        );

        return $propertyValues[\count($propertyValues) - 1][self::VALUE];
    }

    /**
     * {@inheritdoc}
     */
    public function setValue(&$objectOrArray, $propertyPath, $value)
    {
        if (\is_object($objectOrArray) && false === strpbrk((string)$propertyPath, '.[')) {
            $zval = [
                self::VALUE => $objectOrArray,
            ];

            try {
                $this->writeProperty($zval, $propertyPath, $value);

                return;
            } catch (\TypeError $e) {
                self::throwInvalidArgumentException($e->getMessage(), $e->getTrace(), 0, $propertyPath, $e);
                // It wasn't thrown in this class so rethrow it
                throw $e;
            }
        }

        $propertyPath = $this->getPropertyPath($propertyPath);

        $zval = [
            self::VALUE => $objectOrArray,
            self::REF => &$objectOrArray,
        ];
        $propertyValues = $this->readPropertiesUntil($zval, $propertyPath, $propertyPath->getLength() - 1);
        $overwrite = true;

        try {
            for ($i = \count($propertyValues) - 1; 0 <= $i; --$i) {
                $zval = $propertyValues[$i];
                unset($propertyValues[$i]);

                // You only need set value for current element if:
                // 1. it's the parent of the last index element
                // OR
                // 2. its child is not passed by reference
                //
                // This may avoid uncessary value setting process for array elements.
                // For example:
                // '[a][b][c]' => 'old-value'
                // If you want to change its value to 'new-value',
                // you only need set value for '[a][b][c]' and it's safe to ignore '[a][b]' and '[a]'
                if ($overwrite) {
                    $property = $propertyPath->getElement($i);
                    // customization start
                    // if ($propertyPath->isIndex($i)) {
                    // if ($overwrite = !isset($zval[self::REF])) {
                    if ($this->isIndex($zval)) {
                        $overwrite = !isset($zval[self::REF])
                            || $zval[self::REF] === false
                            || is_array($zval[self::REF]);
                        if ($overwrite) {
                            // customization end
                            $ref = &$zval[self::REF];
                            $ref = $zval[self::VALUE];
                        }
                        $this->writeIndex($zval, $property, $value);
                        if ($overwrite) {
                            $zval[self::VALUE] = $zval[self::REF];
                        }
                    } else {
                        $this->writeProperty($zval, $property, $value);
                    }

                    // if current element is an object
                    // OR
                    // if current element's reference chain is not broken - current element
                    // as well as all its ancients in the property path are all passed by reference,
                    // then there is no need to continue the value setting process
                    if (\is_object($zval[self::VALUE]) || isset($zval[self::IS_REF_CHAINED])) {
                        break;
                    }
                }

                $value = $zval[self::VALUE];
            }
        } catch (\TypeError $e) {
            self::throwInvalidArgumentException($e->getMessage(), $e->getTrace(), 0, $propertyPath, $e);

            // It wasn't thrown in this class so rethrow it
            throw $e;
        }
    }

    private static function throwInvalidArgumentException(
        string     $message,
        array      $trace,
        int        $i,
        string     $propertyPath,
        \Throwable $previous = null
    ): void {
        if (!isset($trace[$i]['file']) || __FILE__ !== $trace[$i]['file']) {
            return;
        }

        if (\PHP_VERSION_ID < 80000) {
            if (!str_starts_with($message, 'Argument ')) {
                return;
            }

            $delimMust = 'must be an instance of ';
            $pos = strpos($message, $delim = 'must be of the type ')
                ?: (strpos($message, $delim = $delimMust) ?: strpos($message, $delim = 'must implement interface '));
            $pos += \strlen($delim);
            $j = strpos($message, ',', $pos);
            $type = substr($message, 2 + $j, strpos($message, ' given', $j) - $j - 2);
            $message = substr($message, $pos, $j - $pos);

            throw new InvalidArgumentException(
                sprintf(
                    'Expected argument of type "%s", "%s" given at property path "%s".',
                    $message,
                    'NULL' === $type ? 'null' : $type,
                    $propertyPath
                ),
                0,
                $previous
            );
        }

        if (preg_match(
            '/^\S+::\S+\(\): Argument #\d+ \(\$\S+\) must be of type (\S+), (\S+) given/',
            $message,
            $matches
        )) {
            [, $expectedType, $actualType] = $matches;

            throw new InvalidArgumentException(
                sprintf(
                    'Expected argument of type "%s", "%s" given at property path "%s".',
                    $expectedType,
                    'NULL' === $actualType ? 'null' : $actualType,
                    $propertyPath
                ),
                0,
                $previous
            );
        }
    }

    /**
     * {@inheritdoc}
     */
    public function isReadable($objectOrArray, $propertyPath)
    {
        if (!$propertyPath instanceof PropertyPathInterface) {
            $propertyPath = new PropertyPath($propertyPath);
        }

        try {
            $zval = [
                self::VALUE => $objectOrArray,
            ];
            $this->readPropertiesUntil($zval, $propertyPath, $propertyPath->getLength(), $this->ignoreInvalidIndices);

            return true;
        } catch (AccessException $e) {
            return false;
        } catch (UnexpectedTypeException $e) {
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
            $zval = [
                self::VALUE => $objectOrArray,
            ];
            $propertyValues = $this->readPropertiesUntil($zval, $propertyPath, $propertyPath->getLength() - 1);

            for ($i = \count($propertyValues) - 1; 0 <= $i; --$i) {
                $zval = $propertyValues[$i];
                unset($propertyValues[$i]);
                // customization start
                if ($this->isIndex($zval)) {
                    // customization end
                    if (!$zval[self::VALUE] instanceof \ArrayAccess && !\is_array($zval[self::VALUE])) {
                        return false;
                    }
                } elseif (!\is_object($zval[self::VALUE])
                    || !$this->isPropertyWritable($zval[self::VALUE], $propertyPath->getElement($i))) {
                    return false;
                }

                if (\is_object($zval[self::VALUE])) {
                    return true;
                }
            }

            return true;
        } catch (AccessException $e) {
            return false;
        } catch (UnexpectedTypeException $e) {
            return false;
        }
    }

    /**
     * Reads the path from an object up to a given path index.
     *
     * @throws UnexpectedTypeException if a value within the path is neither object nor array
     * @throws NoSuchIndexException    If a non-existing index is accessed
     */
    private function readPropertiesUntil(
        array                 $zval,
        PropertyPathInterface $propertyPath,
        int                   $lastIndex,
        bool                  $ignoreInvalidIndices = true
    ): array {
        if (!\is_object($zval[self::VALUE]) && !\is_array($zval[self::VALUE])) {
            throw new UnexpectedTypeException($zval[self::VALUE], $propertyPath, 0);
        }
        // customization start (moved logic from ORO PropertyAccessor, needed for $this->remove() method.
        // // Add the root object to the list
        // $propertyValues = [$zval];
        $propertyValues = [];
        $length = count($propertyPath->getElements());
        if (-1 === $lastIndex) {
            $lastIndex = $length - 1;
        } else {
            // Add the root object to the list
            $propertyValues = [$zval];
        }
        // customization end

        for ($i = 0; $i < $lastIndex; ++$i) {
            $property = $propertyPath->getElement($i);
            // customization start
            $isIndex = $this->isIndex($zval);
            // customization end

            if ($isIndex) {
                // Create missing nested arrays on demand
                $isKeyExists = \is_array($zval[self::VALUE])
                    && !isset($zval[self::VALUE][$property])
                    && !\array_key_exists($property, $zval[self::VALUE]);
                if (($zval[self::VALUE] instanceof \ArrayAccess && !$zval[self::VALUE]->offsetExists($property))
                    || $isKeyExists
                ) {
                    if (!$ignoreInvalidIndices) {
                        if (!\is_array($zval[self::VALUE])) {
                            if (!$zval[self::VALUE] instanceof \Traversable) {
                                throw new NoSuchPropertyException(
                                    sprintf(
                                        'Cannot read index "%s" while trying to traverse path "%s".',
                                        $property,
                                        (string)$propertyPath
                                    )
                                );
                            }

                            $zval[self::VALUE] = iterator_to_array($zval[self::VALUE]);
                        }
                        $format = 'Cannot read index "%s" while trying to traverse path'
                            . ' "%s". Available indices are "%s".';
                        throw new NoSuchPropertyException(
                            sprintf(
                                $format,
                                $property,
                                (string)$propertyPath,
                                print_r(array_keys($zval[self::VALUE]), true)
                            )
                        );
                    }

                    if ($i + 1 < $propertyPath->getLength()) {
                        if (isset($zval[self::REF])) {
                            $zval[self::VALUE][$property] = [];
                            $zval[self::REF] = $zval[self::VALUE];
                        } else {
                            $zval[self::VALUE] = [$property => []];
                        }
                    }
                }

                $zval = $this->readIndex($zval, $property);
            } else {
                $zval = $this->readProperty($zval, $property, $this->ignoreInvalidProperty);
            }

            // the final value of the path must not be validated
            if ($i + 1 < $propertyPath->getLength()
                && !\is_object($zval[self::VALUE])
                && !\is_array($zval[self::VALUE])) {
                throw new UnexpectedTypeException($zval[self::VALUE], $propertyPath, $i + 1);
            }
            // customization start
            // if (isset($zval[self::REF]) && (0 === $i || isset($propertyValues[$i - 1][self::IS_REF_CHAINED]))) {
            if (isset($zval[self::REF])
                && $zval[self::REF] !== false
                && (0 === $i || isset($propertyValues[$i - 1][self::IS_REF_CHAINED]))) {
                // customization end
                // Set the IS_REF_CHAINED flag to true if:
                // current property is passed by reference and
                // it is the first element in the property path or
                // the IS_REF_CHAINED flag of its parent element is true
                // Basically, this flag is true only when the reference chain from the top element to current element
                // is not broken
                $zval[self::IS_REF_CHAINED] = true;
            }

            $propertyValues[] = $zval;
        }

        return $propertyValues;
    }

    /**
     * Reads a key from an array-like structure.
     *
     * @param string|int $index The key to read
     *
     * @throws NoSuchIndexException If the array does not implement \ArrayAccess or it is not an array
     */
    private function readIndex(array $zval, $index): array
    {
        if (!$zval[self::VALUE] instanceof \ArrayAccess && !\is_array($zval[self::VALUE])) {
            $format = 'Cannot read index "%s" from object of type "%s" because it doesn\'t implement \ArrayAccess.';
            throw new NoSuchPropertyException(
                sprintf(
                    $format,
                    $index,
                    get_debug_type($zval[self::VALUE])
                )
            );
        }

        // Use an array instead of an object since performance is very crucial here
        // customization start
        // $result = self::RESULT_PROTO;
        $result = [self::VALUE => null, self::REF => false];
        // customization end

        if (isset($zval[self::VALUE][$index])) {
            $result[self::VALUE] = $zval[self::VALUE][$index];
            // Save creating references when doing read-only lookups
            if (!isset($zval[self::REF])) {
            } elseif (\is_array($zval[self::VALUE])) {
                $result[self::REF] = &$zval[self::REF][$index];
            } elseif (\is_object($result[self::VALUE])) {
                $result[self::REF] = $result[self::VALUE];
            }
        // customization start
        } elseif (null !== $index
            && is_array($zval[self::VALUE])
            && !array_key_exists($index, $zval[self::VALUE])
            && !$this->ignoreInvalidIndices
        ) {
            throw new NoSuchPropertyException(sprintf('The key "%s" does exist in an array.', $index));
        }
        // customization end

        return $result;
    }

    /**
     * Reads the a property from an object.
     *
     * @throws NoSuchPropertyException If $ignoreInvalidProperty is false
     * and the property does not exist or is not public
     */
    private function readProperty(array $zval, string $property, bool $ignoreInvalidProperty = false): array
    {
        if (!\is_object($zval[self::VALUE])) {
            $format = 'Cannot read property "%s" from an array. Maybe you intended to write the property path'
                . ' as "[%1$s]" instead.';
            throw new NoSuchPropertyException(sprintf($format, $property));
        }

        $result = self::RESULT_PROTO;
        $object = $zval[self::VALUE];
        $class = \get_class($object);
        $access = $this->getReadInfo($class, $property);

        if (null !== $access) {
            $name = $access->getName();
            $type = $access->getType();

            try {
                if (PropertyReadInfo::TYPE_METHOD === $type) {
                    try {
                        $result[self::VALUE] = $object->$name();
                    } catch (\TypeError $e) {
                        [$trace] = $e->getTrace();

                        // handle uninitialized properties in PHP >= 7
                        $pattern = '/Return value (?:of .*::\w+\(\) )?must be of (?:the )?type (\w+), null returned$/';
                        if (__FILE__ === $trace['file']
                            && $name === $trace['function']
                            && $object instanceof $trace['class']
                            && preg_match($pattern, $e->getMessage(), $matches)
                        ) {
                            $format = 'The method "%s::%s()" returned "null", but expected type "%3$s". Did you forget '
                                . 'to initialize a property or to make the return type nullable using "?%3$s"?';
                            $getParentClass = get_parent_class($object) ?: key(class_implements($object)) ?: 'class';
                            $values = !str_contains(\get_class($object), "@anonymous\0")
                                ? \get_class($object)
                                : $getParentClass . '@anonymous';
                            throw new UninitializedPropertyException(
                                sprintf(
                                    $format,
                                    $values,
                                    $name,
                                    $matches[1]
                                ),
                                0,
                                $e
                            );
                        }

                        throw $e;
                    }
                } elseif (PropertyReadInfo::TYPE_PROPERTY === $type) {
                    if ($access->canBeReference()
                        && !isset($object->$name)
                        && !\array_key_exists($name, (array)$object)
                        && (\PHP_VERSION_ID < 70400 || !(new \ReflectionProperty($class, $name))->hasType())) {
                        throw new UninitializedPropertyException(
                            sprintf(
                                'The property "%s::$%s" is not initialized.',
                                $class,
                                $name
                            )
                        );
                    }

                    $result[self::VALUE] = $object->$name;

                    if (isset($zval[self::REF]) && $access->canBeReference()) {
                        $result[self::REF] = &$object->$name;
                    }
                }
            } catch (\Error $e) {
                // handle uninitialized properties in PHP >= 7.4
                $pattern = '/^Typed property (' . preg_quote(get_debug_type($object), '/')
                    . ')::\$(\w+) must not be accessed before initialization$/';
                $checkInit = preg_match($pattern, $e->getMessage(), $matches);
                if (\PHP_VERSION_ID >= 70400 && $checkInit) {
                    $r = new \ReflectionProperty($class, $matches[2]);
                    $type = ($type = $r->getType()) instanceof \ReflectionNamedType ? $type->getName() : (string)$type;
                    $format = 'The property "%s::$%s" is not readable because it is typed "%s". '
                        . 'You should initialize it or declare a default value instead.';

                    throw new UninitializedPropertyException(
                        sprintf($format, $matches[1], $r->getName(), $type),
                        0,
                        $e
                    );
                }

                throw $e;
            }
        } elseif (property_exists($object, $property) && \array_key_exists($property, (array)$object)) {
            $result[self::VALUE] = $object->$property;
            if (isset($zval[self::REF])) {
                $result[self::REF] = &$object->$property;
            }
        // customization start
        } elseif ($object instanceof \ArrayAccess) {
            if (isset($object[$property])) {
                $result[self::VALUE] = $object[$property];
            } elseif (!$ignoreInvalidProperty) {
                $reflClass = new \ReflectionClass($object);
                throw new NoSuchPropertyException(
                    sprintf('The key "%s" does exist in class "%s".', $property, $reflClass->name)
                );
            }
        } elseif ($this->getReflectionClass($object::class)->hasProperty($property)) {
            $prop = $this->getReflectionClass($object::class)->getProperty($property);
            $prop->setAccessible(true);
            $value = $prop->getValue($object);
            $result[self::VALUE] = $value;
            if (isset($zval[self::REF])) {
                $result[self::REF] = &$value;
            }
        //customization end
        } elseif (!$ignoreInvalidProperty) {
            throw new NoSuchPropertyException(
                sprintf(
                    'Can\'t get a way to read the property "%s" in class "%s".',
                    $property,
                    $class
                )
            );
        }

        // Objects are always passed around by reference
        if (isset($zval[self::REF]) && \is_object($result[self::VALUE])) {
            $result[self::REF] = $result[self::VALUE];
        }

        return $result;
    }

    /**
     * Guesses how to read the property value.
     */
    private function getReadInfo(string $class, string $property): ?PropertyReadInfo
    {
        $key = str_replace('\\', '.', $class) . '..' . $property;

        if (null !== $this->getReadPropertyCache($key)) {
            return $this->getReadPropertyCache($key);
        }

        if ($this->cacheItemPool) {
            $item = $this->cacheItemPool->getItem(self::CACHE_PREFIX_READ . rawurlencode($key));
            if ($item->isHit()) {
                $this->setReadPropertyCache($key, $item->get());

                return $this->getReadPropertyCache($key);
            }
        }

        $accessor = $this->readInfoExtractor->getReadInfo($class, $property, [
            'enable_getter_setter_extraction' => true,
            'enable_magic_methods_extraction' => $this->magicMethodsFlags,
            'enable_constructor_extraction' => false,
        ]);

        if (isset($item)) {
            $this->cacheItemPool->save($item->set($accessor));
        }
        $this->setReadPropertyCache($key, $accessor);

        return $this->getReadPropertyCache($key);
    }

    /**
     * Sets the value of an index in a given array-accessible value.
     *
     * @param string|int $index The index to write at
     * @param mixed $value The value to write
     *
     * @throws NoSuchPropertyException If the array does not implement \ArrayAccess or it is not an array
     */
    private function writeIndex(array &$zval, $index, $value)
    {
        if (!$zval[self::VALUE] instanceof \ArrayAccess && !\is_array($zval[self::VALUE])) {
            throw new NoSuchPropertyException(
                sprintf(
                    'Cannot modify index "%s" in object of type "%s" because it doesn\'t implement \ArrayAccess.',
                    $index,
                    get_debug_type($zval[self::VALUE])
                )
            );
        }

        $zval[self::REF][$index] = $value;
    }

    /**
     * Sets the value of a property in the given object.
     *
     * @param mixed $value The value to write
     *
     * @throws NoSuchPropertyException if the property does not exist or is not public
     */
    private function writeProperty(array $zval, string $property, $value)
    {
        if (!\is_object($zval[self::VALUE])) {
            $format = 'Cannot write property "%s" to an array. Maybe you should write the property '
                . 'path as "[%1$s]" instead?';
            throw new NoSuchPropertyException(sprintf($format, $property));
        }
        // customization start
        if ($zval[self::VALUE] instanceof \ArrayAccess) {
            $zval[self::VALUE][$property] = $value;

            return;
        }
        // customization end

        $object = $zval[self::VALUE];
        $class = \get_class($object);
        $mutator = $this->getWriteInfo($class, $property, $value);

        if (PropertyWriteInfo::TYPE_NONE !== $mutator->getType()) {
            $type = $mutator->getType();

            if (PropertyWriteInfo::TYPE_METHOD === $type) {
                $object->{$mutator->getName()}($value);
            } elseif (PropertyWriteInfo::TYPE_PROPERTY === $type) {
                $object->{$mutator->getName()} = $value;
            } elseif (PropertyWriteInfo::TYPE_ADDER_AND_REMOVER === $type) {
                // customization start
                $this->checkValueIsCollectionByMethods(
                    $zval,
                    $property,
                    $value,
                    $mutator->getAdderInfo(),
                    $mutator->getRemoverInfo()
                );
                // customization end
            }
        } elseif ($object instanceof \stdClass && property_exists($object, $property)) {
            $object->$property = $value;
        } elseif (!$this->ignoreInvalidProperty) {
            if ($mutator->hasErrors()) {
                throw new NoSuchPropertyException(implode('. ', $mutator->getErrors()) . '.');
            }

            throw new NoSuchPropertyException(
                sprintf(
                    'Could not determine access type for property "%s" in class "%s".',
                    $property,
                    get_debug_type($object)
                )
            );
        }
    }

    /**
     * Adjusts a collection-valued property by calling add*() and remove*() methods.
     */
    private function writeCollection(
        array             $zval,
        string            $property,
        iterable          $collection,
        PropertyWriteInfo $addMethod,
        PropertyWriteInfo $removeMethod,
        $shouldRemoveItems = true
    ) {
        // At this point the add and remove methods have been found
        $previousValue = $this->readProperty($zval, $property);
        $previousValue = $previousValue[self::VALUE];

        $removeMethodName = $removeMethod->getName();
        $addMethodName = $addMethod->getName();

        if ($previousValue instanceof \Traversable) {
            $previousValue = iterator_to_array($previousValue);
        }
        if ($previousValue && \is_array($previousValue)) {
            if (\is_object($collection)) {
                $collection = iterator_to_array($collection);
            }
            foreach ($previousValue as $key => $item) {
                if ($shouldRemoveItems && !\in_array($item, $collection, true)) {
                    unset($previousValue[$key]);
                    $zval[self::VALUE]->$removeMethodName($item);
                }
            }
        } else {
            $previousValue = false;
        }

        foreach ($collection as $item) {
            if (!$previousValue || !\in_array($item, $previousValue, true)) {
                $zval[self::VALUE]->$addMethodName($item);
            }
        }
    }

    private function getWriteInfo(string $class, string $property, $value): PropertyWriteInfo
    {
        // $useAdderAndRemover = is_iterable($value);
        $useAdderAndRemover = true;
        $key = str_replace('\\', '.', $class) . '..' . $property . '..' . (int)$useAdderAndRemover;

        if (isset($this->writePropertyCache[$key])) {
            return $this->writePropertyCache[$key];
        }

        if ($this->cacheItemPool) {
            $item = $this->cacheItemPool->getItem(self::CACHE_PREFIX_WRITE . rawurlencode($key));
            if ($item->isHit()) {
                return $this->writePropertyCache[$key] = $item->get();
            }
        }

        $mutator = $this->writeInfoExtractor->getWriteInfo($class, $property, [
            'enable_getter_setter_extraction' => true,
            'enable_magic_methods_extraction' => $this->magicMethodsFlags,
            'enable_constructor_extraction' => false,
            'enable_adder_remover_extraction' => $useAdderAndRemover,
        ]);

        if (isset($item)) {
            $this->cacheItemPool->save($item->set($mutator));
        }

        return $this->writePropertyCache[$key] = $mutator;
    }

    /**
     * Returns whether a property is writable in the given object.
     */
    private function isPropertyWritable(object $object, string $property): bool
    {
        $mutatorForArray = $this->getWriteInfo(\get_class($object), $property, []);

        if (PropertyWriteInfo::TYPE_NONE !== $mutatorForArray->getType()
            || ($object instanceof \stdClass && property_exists($object, $property))) {
            return true;
        }

        $mutator = $this->getWriteInfo(\get_class($object), $property, '');

        return PropertyWriteInfo::TYPE_NONE !== $mutator->getType()
            || ($object instanceof \stdClass && property_exists($object, $property));
    }

    /**
     * Gets a PropertyPath instance and caches it.
     *
     * @param string|PropertyPath $propertyPath
     */
    private function getPropertyPath($propertyPath): PropertyPath
    {
        if ($propertyPath instanceof PropertyPathInterface) {
            // Don't call the copy constructor has it is not needed here
            return $propertyPath;
        }

        if (isset($this->propertyPathCache[$propertyPath])) {
            return $this->propertyPathCache[$propertyPath];
        }

        if ($this->cacheItemPool) {
            $item = $this->cacheItemPool->getItem(self::CACHE_PREFIX_PROPERTY_PATH . rawurlencode($propertyPath));
            if ($item->isHit()) {
                return $this->propertyPathCache[$propertyPath] = $item->get();
            }
        }

        $propertyPathInstance = new PropertyPath($propertyPath);
        if (isset($item)) {
            $item->set($propertyPathInstance);
            $this->cacheItemPool->save($item);
        }

        return $this->propertyPathCache[$propertyPath] = $propertyPathInstance;
    }

    /**
     * Creates the APCu adapter if applicable.
     *
     * @return AdapterInterface
     *
     * @throws \LogicException When the Cache Component isn't available
     */
    public static function createCache(
        string          $namespace,
        int             $defaultLifetime,
        string          $version,
        LoggerInterface $logger = null
    ) {
        if (!class_exists(ApcuAdapter::class)) {
            throw new \LogicException(
                sprintf('The Symfony Cache component must be installed to use "%s()".', __METHOD__)
            );
        }

        if (!ApcuAdapter::isSupported()) {
            return new NullAdapter();
        }

        $apcu = new ApcuAdapter($namespace, $defaultLifetime / 5, $version);
        if ('cli' === \PHP_SAPI && !filter_var(ini_get('apc.enable_cli'), \FILTER_VALIDATE_BOOLEAN)) {
            $apcu->setLogger(new NullLogger());
        } elseif (null !== $logger) {
            $apcu->setLogger($logger);
        }

        return $apcu;
    }

    // customization start
    private function isIndex(mixed $zval): bool
    {
        return is_array($zval[self::VALUE]) || $zval[self::VALUE] instanceof \ArrayAccess;
    }

    private function getReflectionClass(string $className): \ReflectionClass
    {
        $realClassName = CachedClassUtils::getRealClass($className);
        if (!isset($this->reflectionClassesCache[$realClassName])) {
            $realClassUtils = CachedClassUtils::getRealClass($realClassName);
            $this->reflectionClassesCache[$realClassName] = new EntityReflectionClass($realClassUtils);
        }

        return $this->reflectionClassesCache[$realClassName];
    }

    /**
     * Removes the property at the end of the property path of the object.
     *
     * Example:
     *
     * <code>
     *     use Oro\Bundle\EntityExtendBundle\Decorator\PropertyAccess;
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
     * @param object|array $object The object or array to modify
     * @param string|PropertyPathInterface $propertyPath The property path to modify
     *
     * @throws NoSuchPropertyException If a property does not exist or is not public.
     */
    public function remove(&$object, $propertyPath)
    {
        if ((is_array($object) || is_object($object)) && empty($object)) {
            return;
        }
        $propertyPath = $this->getPropertyPath($propertyPath);
        $zval = [
            self::VALUE => $object,
        ];

        $path = $propertyPath->getElements();
        $values = $this->readPropertiesUntil($zval, $propertyPath, -1);

        if (($object instanceof \ArrayObject || $object instanceof \ArrayAccess)
            && $object->count() < count($path) - 1) {
            return;
        }

        // Add the root object to the list
        array_unshift(
            $values,
            [self::VALUE => &$object, self::REF => true]
        );

        $value = null;
        $overwrite = true;
        $lastIndex = count($values) - 1;
        for ($i = $lastIndex; $i >= 0; --$i) {
            $object = &$values[$i][self::VALUE];

            if ($overwrite) {
                if (!is_object($object) && !is_array($object)) {
                    throw new NoSuchPropertyException(
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
                    $this->setValue($object, $propertyPath, $value);
                }
            }

            $value = &$object;
            $overwrite = isset($values[$i][self::REF]) && !$values[$i][self::REF];
        }
    }

    /**
     * Unsets a property in the given object.
     *
     * @param array|object $object The object or array to unset from
     * @param mixed $property The property or index to unset
     *
     * @throws NoSuchPropertyException If the property does not exist or is not public.
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
            $unsetter = 'remove' . $this->camelize($property);

            if ($this->isMethodAccessible($reflClass, $unsetter, 0)) {
                $object->$unsetter();
            } elseif ($this->isMethodAccessible($reflClass, '__unset', 1)) {
                unset($object->$property);
            } elseif ($this->magicMethodsFlags
                && $this->isMethodAccessible($reflClass, '__call', 2)) {
                // we call the unsetter and hope the __call do the job
                $object->$unsetter();
            } else {
                throw new NoSuchPropertyException(
                    sprintf(
                        'Neither one of the methods "%s()", ' .
                        '"__unset()" or "__call()" exist and have public access in class "%s".',
                        $unsetter,
                        $reflClass->name
                    )
                );
            }
        } else {
            throw new NoSuchPropertyException(
                sprintf(
                    'Unexpected object type. Expected "array or object", "%s" given.',
                    is_object($object) ? get_class($object) : gettype($object)
                )
            );
        }
    }

    /**
     * Returns whether a method is public and has the number of required parameters.
     *
     * @param \ReflectionClass $class The class of the method
     * @param string $methodName The method name
     * @param int $parameters The number of parameters
     *
     * @return bool Whether the method is public and has $parameters required parameters
     */
    protected function isMethodAccessible(\ReflectionClass $class, $methodName, $parameters)
    {
        if ($this->hasPublicMethod($class, $methodName)) {
            $method = $class->getMethod($methodName);

            if ($method->getNumberOfRequiredParameters() <= $parameters
                && $method->getNumberOfParameters() >= $parameters
            ) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param \ReflectionClass $class
     * @param string $methodName
     *
     * @return bool
     */
    private function hasPublicMethod(\ReflectionClass $class, $methodName)
    {
        return $class->hasMethod($methodName) && $class->getMethod($methodName)->isPublic();
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
     * Checks if value is a collection and sets the value for the attribute with already defined methods
     *
     * @param array|object $object The object or array to write to
     * @param mixed $property The property or index to write
     * @param mixed $value The value to write
     * @param PropertyWriteInfo $addMethod The add*() method
     * @param PropertyWriteInfo $removeMethod The remove*() method
     * @return bool
     */
    protected function checkValueIsCollectionByMethods($object, $property, $value, $addMethod, $removeMethod)
    {
        $shouldRemoveItems = true;

        try {
            $objectValue = $this->readProperty($object, $property, true);
        } catch (NoSuchPropertyException $e) {
            //property was not set so we cannot get a value that wasn't set already
            $objectValue = null;
        }
        //if the value we want to add is not an array and we try to add it to a collection,
        // then we don't want to overwrite the old values, instead add the new value to the collection
        if ((!is_array($value) && !$value instanceof \Traversable)
            && ($objectValue instanceof \ArrayAccess || is_array($objectValue))
        ) {
            //we try to add a value to a collection and we don't want to remove old items
            $value = [$value];
            $shouldRemoveItems = false;
        }

        if (is_array($value) || $value instanceof \Traversable) {
            $this->writeCollection($object, $property, $value, $addMethod, $removeMethod, $shouldRemoveItems);

            return true;
        }

        return false;
    }

    protected function getReadPropertyCache(string $key): mixed
    {
        if (!isset($this->readPropertyCache[$key])) {
            return null;
        }

        return $this->readPropertyCache[$key];
    }

    protected function setReadPropertyCache(string $key, mixed $value): self
    {
        $this->readPropertyCache[$key] = $value;

        return $this;
    }
    // customization end
}
