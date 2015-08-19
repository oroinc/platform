<?php

namespace Oro\Component\ConfigExpression;

use Oro\Component\ConfigExpression\ConfigurationPass\ReplacePropertyPath;
use Oro\Component\ConfigExpression\Extension\Core\CoreExtension;
use Oro\Component\ConfigExpression\Extension\ExtensionInterface;

/**
 * Entry point of the Config Expression component.
 *
 * Example of usage:
 *
 * <code>
 * $context = ['foo' => ' '];
 * $expr    = [
 *     '@empty' => [
 *         ['@trim' => '$foo']
 *     ]
 * ];
 *
 * $language = new ConfigExpressions();
 * echo $language->evaluate($expr, $context)
 * </code>
 */
class ConfigExpressions
{
    /** @var AssemblerInterface */
    protected $assembler;

    /** @var ExpressionFactoryInterface */
    protected $factory;

    /** @var ContextAccessorInterface */
    protected $contextAccessor;

    /**
     * Evaluates an expression.
     *
     * @param array|ExpressionInterface $expr
     * @param mixed                     $context
     * @param \ArrayAccess|null         $errors
     *
     * @return mixed
     */
    public function evaluate($expr, $context, \ArrayAccess $errors = null)
    {
        if ($expr === null) {
            return null;
        }
        if (!$expr instanceof ExpressionInterface) {
            $expr = $this->getExpression($expr);
            if (!$expr) {
                return null;
            }
        }

        return $expr->evaluate($context, $errors);
    }

    /**
     * @param array $configuration
     *
     * @return ExpressionInterface|null
     */
    public function getExpression(array $configuration)
    {
        return $this->getAssembler()->assemble($configuration);
    }

    /**
     * @return AssemblerInterface
     */
    public function getAssembler()
    {
        if (!$this->assembler) {
            $this->assembler = new ExpressionAssembler($this->getFactory());
            $this->assembler->addConfigurationPass(new ReplacePropertyPath());
        }

        return $this->assembler;
    }

    /**
     * @param AssemblerInterface $assembler
     */
    public function setAssembler(AssemblerInterface $assembler)
    {
        $this->assembler = $assembler;
    }

    /**
     * @return ExpressionFactoryInterface
     */
    public function getFactory()
    {
        if (!$this->factory) {
            $this->factory = new ExpressionFactory($this->getContextAccessor());
            $this->factory->addExtension(new CoreExtension());
        }

        return $this->factory;
    }

    /**
     * @param ExpressionFactoryInterface $factory
     */
    public function setFactory(ExpressionFactoryInterface $factory)
    {
        $this->factory = $factory;
    }

    /**
     * @return ContextAccessorInterface
     */
    public function getContextAccessor()
    {
        if (!$this->contextAccessor) {
            $this->contextAccessor = new ContextAccessor();
        }

        return $this->contextAccessor;
    }

    /**
     * @param ContextAccessorInterface $contextAccessor
     */
    public function setContextAccessor(ContextAccessorInterface $contextAccessor)
    {
        $this->contextAccessor = $contextAccessor;
    }

    /**
     * Registers new extension.
     *
     * @param ExtensionInterface $extension
     */
    public function addExtension(ExtensionInterface $extension)
    {
        $factory = $this->getFactory();
        if ($factory instanceof ExpressionFactory) {
            $factory->addExtension($extension);
        }
    }
}
