<?php

namespace Oro\Bundle\EntityExtendBundle\Twig\Node;

use ArrayAccess;
use Doctrine\Inflector\Inflector;
use Oro\Bundle\EntityExtendBundle\Entity\ExtendEntityInterface;
use Oro\Bundle\EntityExtendBundle\EntityPropertyInfo;
use Oro\Component\DoctrineUtils\Inflector\InflectorFactory;
use Twig\Compiler;
use Twig\Environment;
use Twig\Error\RuntimeError;
use Twig\Extension\SandboxExtension;
use Twig\Node\Expression\GetAttrExpression;
use Twig\Node\Node;
use Twig\Source;
use Twig\Template;

/**
 * Compile a custom twig_get_attribute node.
 * @SuppressWarnings(PHPMD)
 */
class GetAttrNode extends GetAttrExpression
{
    protected static ?Inflector $inflector = null;

    public function __construct(array $nodes = [], array $attributes = [], int $lineno = 0, ?string $tag = null)
    {
        // Skip parent::__construct()
        Node::__construct($nodes, $attributes, $lineno, $tag);
    }

    #[\Override]
    public function compile(Compiler $compiler): void
    {
        $env = $compiler->getEnvironment();

        // optimize array calls
        if ($this->getAttribute('optimizable')
            && (!$env->isStrictVariables() || $this->getAttribute('ignore_strict_check'))
            && !$this->getAttribute('is_defined_test')
            && Template::ARRAY_CALL === $this->getAttribute('type')
        ) {
            $var = '$' . $compiler->getVarName();
            $compiler
                ->raw('((' . $var . ' = ')
                ->subcompile($this->getNode('node'))
                ->raw(') && is_array(')
                ->raw($var)
                ->raw(') || ')
                ->raw($var)
                ->raw(' instanceof ArrayAccess ? (')
                ->raw($var)
                ->raw('[')
                ->subcompile($this->getNode('attribute'))
                ->raw('] ?? null) : null)');

            return;
        }

        // START EDIT
        // This is the only line that should be different to the parent function.
        $compiler->raw(static::class . '::attribute($this->env, $this->source, ');
        // END EDIT

        if ($this->getAttribute('ignore_strict_check')) {
            $this->getNode('node')->setAttribute('ignore_strict_check', true);
        }

        $compiler
            ->subcompile($this->getNode('node'))
            ->raw(', ')
            ->subcompile($this->getNode('attribute'));

        if ($this->hasNode('arguments')) {
            $compiler->raw(', ')->subcompile($this->getNode('arguments'));
        } else {
            $compiler->raw(', []');
        }

        $compiler->raw(', ')
            ->repr($this->getAttribute('type'))
            ->raw(', ')->repr($this->getAttribute('is_defined_test'))
            ->raw(', ')->repr($this->getAttribute('ignore_strict_check'))
            ->raw(', ')->repr($env->hasExtension(SandboxExtension::class))
            ->raw(', ')->repr($this->getNode('node')->getTemplateLine())
            ->raw(')');
    }

    /**
     * Returns the attribute value for a given array/object.
     *
     * @param Environment $env
     * @param Source $source
     * @param mixed $object The object or array from where to get the item
     * @param mixed $item The item to get from the array or object
     * @param array $arguments An array of arguments to pass if the item is an object method
     * @param string $type The type of attribute (@see \Twig\Template constants)
     * @param bool $isDefinedTest Whether this is only a defined check
     * @param bool $ignoreStrictCheck Whether to ignore the strict attribute check or not
     * @param bool $sandboxed
     * @param int $lineno The template line where the attribute was called
     *
     * @return mixed The attribute value, or a Boolean when $isDefinedTest is true, or null when the attribute is not
     *               set and $ignoreStrictCheck is true
     *
     * @throws RuntimeError if the attribute does not exist and Twig is running in strict mode
     *         and $isDefinedTest is false
     */
    public static function attribute(
        Environment $env,
        Source $source,
        $object,
        $item,
        array $arguments = [],
        $type = /* Template::ANY_CALL */ 'any',
        $isDefinedTest = false,
        $ignoreStrictCheck = false,
        $sandboxed = false,
        int $lineno = -1
    ) {
        if ($object instanceof ExtendEntityInterface) {
            $basePropertyExists = EntityPropertyInfo::propertyExists($object, $item);
            $baseMethodExists = EntityPropertyInfo::methodExists($object, $item);
            $getMethodWithPrefixExists = self::isMethodWithPrefixExists($object, 'get', $item);

            if (!$basePropertyExists
                && !$baseMethodExists
                && !$getMethodWithPrefixExists
                && !self::isMethodWithPrefixExists($object, 'is', $item)
            ) {
                if ($isDefinedTest) {
                    return false;
                }
                if ($ignoreStrictCheck || !$env->isStrictVariables()) {
                    return;
                }
                $class = \get_class($object);
                $format = 'Neither the property "%1$s" nor one of the methods "%1$s()",' .
                    ' "get%1$s()"/"is%1$s()"/"has%1$s()" or "__call()" exist and have public access in class "%2$s".';
                throw new RuntimeError(sprintf($format, $item, $class), $lineno, $source);
            }
            if (!$basePropertyExists && !$baseMethodExists && $getMethodWithPrefixExists) {
                $item = self::getInflector()->camelize('get' . ucfirst($item));
            }
        }

        return self::twigGetAttribute(
            $env,
            $source,
            $object,
            $item,
            $arguments,
            $type,
            $isDefinedTest,
            $ignoreStrictCheck,
            $sandboxed,
            $lineno
        );
    }

    /**
     * @copyright twig/twig/src/Extension/CoreExtension.php::twig_get_attribute()
     */
    private static function twigGetAttribute(
        Environment $env,
        Source $source,
        $object,
        $item,
        array $arguments = [],
        $type = /* Template::ANY_CALL */ 'any',
        $isDefinedTest = false,
        $ignoreStrictCheck = false,
        $sandboxed = false,
        int $lineno = -1
    ) {
        if (/* Template::METHOD_CALL */ 'method' !== $type) {
            $arrayItem = \is_bool($item) || \is_float($item) ? (int)$item : $item;

            if (((\is_array($object) || $object instanceof \ArrayObject)
                    && (isset($object[$arrayItem]) || \array_key_exists($arrayItem, (array)$object)))
                || ($object instanceof ArrayAccess && isset($object[$arrayItem]))
            ) {
                if ($isDefinedTest) {
                    return true;
                }

                return $object[$arrayItem];
            }
            if (/* Template::ARRAY_CALL */ 'array' === $type || !\is_object($object)) {
                if ($isDefinedTest) {
                    return false;
                }
                if ($ignoreStrictCheck || !$env->isStrictVariables()) {
                    return;
                }
                if ($object instanceof ArrayAccess) {
                    $message = sprintf(
                        'Key "%s" in object with ArrayAccess of class "%s" does not exist.',
                        $arrayItem,
                        \get_class($object)
                    );
                } elseif (\is_object($object)) {
                    $format = 'Impossible to access a key "%s" on an object of class "%s" that '
                        . 'does not implement ArrayAccess interface.';
                    $message = sprintf($format, $item, \get_class($object));
                } elseif (\is_array($object)) {
                    if (empty($object)) {
                        $message = sprintf('Key "%s" does not exist as the array is empty.', $arrayItem);
                    } else {
                        $message = sprintf(
                            'Key "%s" for array with keys "%s" does not exist.',
                            $arrayItem,
                            implode(', ', array_keys($object))
                        );
                    }
                } elseif (/* Template::ARRAY_CALL */ 'array' === $type) {
                    if (null === $object) {
                        $message = sprintf('Impossible to access a key ("%s") on a null variable.', $item);
                    } else {
                        $message = sprintf(
                            'Impossible to access a key ("%s") on a %s variable ("%s").',
                            $item,
                            \gettype($object),
                            $object
                        );
                    }
                } elseif (null === $object) {
                    $message = sprintf(
                        'Impossible to access an attribute ("%s") on a null variable.',
                        $item
                    );
                } else {
                    $message = sprintf(
                        'Impossible to access an attribute ("%s") on a %s variable ("%s").',
                        $item,
                        \gettype($object),
                        $object
                    );
                }

                throw new RuntimeError($message, $lineno, $source);
            }
        }
        if (!\is_object($object)) {
            if ($isDefinedTest) {
                return false;
            }
            if ($ignoreStrictCheck || !$env->isStrictVariables()) {
                return;
            }
            if (null === $object) {
                $message = sprintf('Impossible to invoke a method ("%s") on a null variable.', $item);
            } elseif (\is_array($object)) {
                $message = sprintf('Impossible to invoke a method ("%s") on an array.', $item);
            } else {
                $message = sprintf(
                    'Impossible to invoke a method ("%s") on a %s variable ("%s").',
                    $item,
                    \gettype($object),
                    $object
                );
            }

            throw new RuntimeError($message, $lineno, $source);
        }
        if ($object instanceof Template) {
            throw new RuntimeError('Accessing \Twig\Template attributes is forbidden.', $lineno, $source);
        }
        // object property
        if (/* Template::METHOD_CALL */ 'method' !== $type) {
            if (isset($object->$item) || \array_key_exists((string)$item, (array)$object)) {
                if ($isDefinedTest) {
                    return true;
                }

                if ($sandboxed) {
                    $env->getExtension(SandboxExtension::class)
                        ->checkPropertyAllowed($object, $item, $lineno, $source);
                }

                return $object->$item;
            }
        }
        static $cache = [];
        $class = \get_class($object);

        // object method
        // precedence: getXxx() > isXxx() > hasXxx()
        if (!isset($cache[$class])) {
            $methods = get_class_methods($object);
            sort($methods);
            $lcMethods = array_map(function ($value) {
                return strtr($value, 'ABCDEFGHIJKLMNOPQRSTUVWXYZ', 'abcdefghijklmnopqrstuvwxyz');
            }, $methods);
            $classCache = [];
            foreach ($methods as $i => $method) {
                $classCache[$method] = $method;
                $classCache[$lcName = $lcMethods[$i]] = $method;

                if ('g' === $lcName[0] && 0 === strpos($lcName, 'get')) {
                    $name = substr($method, 3);
                    $lcName = substr($lcName, 3);
                } elseif ('i' === $lcName[0] && 0 === strpos($lcName, 'is')) {
                    $name = substr($method, 2);
                    $lcName = substr($lcName, 2);
                } elseif ('h' === $lcName[0] && 0 === strpos($lcName, 'has')) {
                    $name = substr($method, 3);
                    $lcName = substr($lcName, 3);
                    if (\in_array('is' . $lcName, $lcMethods)) {
                        continue;
                    }
                } else {
                    continue;
                }

                // skip get() and is() methods (in which case, $name is empty)
                if ($name) {
                    if (!isset($classCache[$name])) {
                        $classCache[$name] = $method;
                    }

                    if (!isset($classCache[$lcName])) {
                        $classCache[$lcName] = $method;
                    }
                }
            }
            $cache[$class] = $classCache;
        }

        $call = false;
        $upperChars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $lowerChars = 'abcdefghijklmnopqrstuvwxyz';
        if (isset($cache[$class][$item])) {
            $method = $cache[$class][$item];
        } elseif (isset($cache[$class][$lcItem = strtr($item, $upperChars, $lowerChars)])) {
            $method = $cache[$class][$lcItem];
        } elseif (isset($cache[$class]['__call'])) {
            $method = $item;
            $call = true;
        } else {
            if ($isDefinedTest) {
                return false;
            }
            if ($ignoreStrictCheck || !$env->isStrictVariables()) {
                return;
            }
            $format = 'Neither the property "%1$s" nor one of the methods "%1$s()", '
                . '"get%1$s()"/"is%1$s()"/"has%1$s()" or "__call()" exist and have public access in class "%2$s".';

            throw new RuntimeError(sprintf($format, $item, $class), $lineno, $source);
        }
        if ($isDefinedTest) {
            return true;
        }
        if ($sandboxed) {
            $env->getExtension(SandboxExtension::class)->checkMethodAllowed($object, $method, $lineno, $source);
        }
        // Some objects throw exceptions when they have __call, and the method we try
        // to call is not supported. If ignoreStrictCheck is true, we should return null.
        try {
            $ret = $object->$method(...$arguments);
        } catch (\BadMethodCallException $e) {
            if ($call && ($ignoreStrictCheck || !$env->isStrictVariables())) {
                return;
            }
            throw $e;
        }

        return $ret;
    }

    private static function isMethodWithPrefixExists(
        object|string $objectOrClass,
        string        $prefix,
        string        $methodCandidate
    ): bool {
        if (!str_starts_with($methodCandidate, $prefix)) {
            $methodCandidate = self::getInflector()->camelize($prefix . ucfirst($methodCandidate));
        }

        return EntityPropertyInfo::methodExists($objectOrClass, $methodCandidate);
    }

    private static function getInflector(): Inflector
    {
        if (null === self::$inflector) {
            self::$inflector = InflectorFactory::create();
        }

        return self::$inflector;
    }
}
