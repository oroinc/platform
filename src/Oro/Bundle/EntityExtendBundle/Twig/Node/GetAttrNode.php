<?php

namespace Oro\Bundle\EntityExtendBundle\Twig\Node;

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

    /**
     * @inheritdoc
     */
    public function __construct(array $nodes = [], array $attributes = [], int $lineno = 0, string $tag = null)
    {
        // Skip parent::__construct()
        Node::__construct($nodes, $attributes, $lineno, $tag);
    }

    /**
     * @inheritdoc
     */
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

        return \twig_get_attribute(
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
