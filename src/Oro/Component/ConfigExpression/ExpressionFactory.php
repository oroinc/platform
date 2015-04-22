<?php

namespace Oro\Component\ConfigExpression;

use Oro\Component\ConfigExpression\Extension\ExtensionInterface;

class ExpressionFactory implements ExpressionFactoryInterface
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
                $expr = $extension->getExpression($name);
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
}
