<?php

namespace Oro\Component\ConfigExpression;

use Oro\Component\ConfigExpression\Extension\DependencyInjection\DependencyInjectionExtension;
use Oro\Component\ConfigExpression\Extension\ExtensionInterface;

class ExpressionFactory implements ExpressionFactoryInterface, FactoryWithTypesInterface
{
    /** @var ContextAccessorInterface */
    protected $contextAccessor;

    /** @var ExtensionInterface[] */
    protected $extensions = [];

    /**
     * @param ContextAccessorInterface $contextAccessor
     */
    public function __construct(ContextAccessorInterface $contextAccessor)
    {
        $this->contextAccessor = $contextAccessor;
    }

    /**
     * {@inheritdoc}
     */
    public function create($name, array $options = [])
    {
        foreach ($this->extensions as $extension) {
            if ($extension->hasExpression($name)) {
                $expr = clone $extension->getExpression($name);
                if (!$expr instanceof ExpressionInterface) {
                    throw new Exception\UnexpectedTypeException(
                        $expr,
                        'Oro\Component\ConfigExpression\ExpressionInterface',
                        sprintf('Invalid type of expression "%s".', $name)
                    );
                }
                if ($expr instanceof ContextAccessorAwareInterface) {
                    $expr->setContextAccessor($this->contextAccessor);
                }
                $expr->initialize($options);

                return $expr;
            }
        }

        throw new Exception\InvalidArgumentException(
            sprintf('The expression "%s" does not exist.', $name)
        );
    }

    /**
     * Registers new extension.
     *
     * @param ExtensionInterface $extension
     */
    public function addExtension(ExtensionInterface $extension)
    {
        $this->extensions[] = $extension;
    }

    /**
     * @return string[]
     */
    public function getTypes()
    {
        $services = [];
        foreach ($this->extensions as $extension) {
            if ($extension instanceof DependencyInjectionExtension) {
                $services[] = $extension->getServiceIds();
            }
        }

        return call_user_func_array('array_merge', $services);
    }

    /**
     * {@inheritdoc}
     */
    public function isTypeExists($name)
    {
        foreach ($this->extensions as $extension) {
            if ($extension->hasExpression($name)) {
                return true;
            }
        }

        return false;
    }
}
