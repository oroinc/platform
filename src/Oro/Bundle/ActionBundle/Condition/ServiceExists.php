<?php

namespace Oro\Bundle\ActionBundle\Condition;

use Oro\Component\ConfigExpression\Condition\AbstractCondition;
use Oro\Component\ConfigExpression\ContextAccessorAwareInterface;
use Oro\Component\ConfigExpression\ContextAccessorAwareTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\PropertyAccess\PropertyPathInterface;

/**
 * Check if service exists.
 * Usage:
 * @service_exists: service_name
 */
class ServiceExists extends AbstractCondition implements ContextAccessorAwareInterface
{
    use ContextAccessorAwareTrait;

    const NAME = 'service_exists';

    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * @var PropertyPathInterface
     */
    protected $propertyPath;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    #[\Override]
    protected function isConditionAllowed($context)
    {
        $serviceName = (string) $this->resolveValue($context, $this->propertyPath);

        return $this->container->has($serviceName);
    }

    #[\Override]
    public function getName()
    {
        return static::NAME;
    }

    #[\Override]
    public function initialize(array $options)
    {
        $option = reset($options);
        $this->propertyPath = $option;

        return $this;
    }

    #[\Override]
    public function toArray()
    {
        return $this->convertToArray([$this->propertyPath]);
    }

    #[\Override]
    public function compile($factoryAccessor)
    {
        return $this->convertToPhpCode([$this->propertyPath], $factoryAccessor);
    }
}
