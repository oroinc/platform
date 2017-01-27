<?php

namespace Oro\Bundle\ActionBundle\Condition;

use Oro\Component\ConfigExpression\Condition\AbstractCondition;
use Oro\Component\ConfigExpression\ContextAccessorAwareInterface;
use Oro\Component\ConfigExpression\ContextAccessorAwareTrait;
use Symfony\Component\PropertyAccess\PropertyPathInterface;
use Symfony\Component\Routing\RouterInterface;

/**
 * Check if route exists.
 * Usage:
 * @route_exists: route_name
 *
 * @deprecated since 2.1. Will be removed in 2.3.
 */
class RouteExists extends AbstractCondition implements ContextAccessorAwareInterface
{
    use ContextAccessorAwareTrait;

    const NAME = 'route_exists';

    /**
     * @var RouterInterface
     */
    protected $router;

    /**
     * @var PropertyPathInterface
     */
    protected $propertyPath;

    /**
     * @param RouterInterface $router
     */
    public function __construct(RouterInterface $router)
    {
        $this->router = $router;
    }

    /**
     * {@inheritdoc}
     */
    protected function isConditionAllowed($context)
    {
        $routeName = $this->resolveValue($context, $this->propertyPath);

        return $this->router->getRouteCollection()->get($routeName) !== null;
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return static::NAME;
    }

    /**
     * {@inheritdoc}
     */
    public function initialize(array $options)
    {
        $option = reset($options);
        $this->propertyPath = $option;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function toArray()
    {
        return $this->convertToArray([$this->propertyPath]);
    }

    /**
     * {@inheritdoc}
     */
    public function compile($factoryAccessor)
    {
        return $this->convertToPhpCode([$this->propertyPath], $factoryAccessor);
    }
}
