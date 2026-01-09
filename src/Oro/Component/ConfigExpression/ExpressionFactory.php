<?php

namespace Oro\Component\ConfigExpression;

use Oro\Component\ConfigExpression\Extension\DependencyInjection\DependencyInjectionExtension;
use Oro\Component\ConfigExpression\Extension\ExtensionInterface;

/**
 * Creates and manages expression instances from registered extensions.
 *
 * This factory implementation manages a collection of extensions that provide expression types.
 * It creates expression instances by delegating to the appropriate extension, handles context
 * accessor injection for expressions that require it, and provides methods to query available
 * expression types across all registered extensions.
 */
class ExpressionFactory implements ExpressionFactoryInterface, FactoryWithTypesInterface
{
    /** @var ContextAccessorInterface */
    protected $contextAccessor;

    /** @var ExtensionInterface[] */
    protected $extensions = [];

    public function __construct(ContextAccessorInterface $contextAccessor)
    {
        $this->contextAccessor = $contextAccessor;
    }

    #[\Override]
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
     */
    public function addExtension(ExtensionInterface $extension)
    {
        $this->extensions[] = $extension;
    }

    /**
     * @return string[]
     */
    #[\Override]
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

    #[\Override]
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
