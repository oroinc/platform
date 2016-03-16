<?php

namespace Oro\Bundle\ActionBundle\Model\ActionGroup;

use Oro\Bundle\ActionBundle\Model\ActionGroupExecutionArgs;
use Oro\Component\Action\Model\ContextAccessor;
use Symfony\Component\PropertyAccess\PropertyPathInterface;

class ParametersMapper
{
    /** @var ContextAccessor */
    private $accessor;

    /**
     * @param ContextAccessor|null $accessor
     */
    public function __construct(ContextAccessor $accessor = null)
    {
        $this->accessor = $accessor ?: new ContextAccessor();
    }


    /**
     * Applies mapped values from context to ActionGroupExecutionArgs arguments
     * @param ActionGroupExecutionArgs $args
     * @param array|\Traversable $parametersMap
     * @param mixed $context
     */
    public function mapToArgs(ActionGroupExecutionArgs $args, $parametersMap, $context)
    {
        $this->assertTraversable($parametersMap);

        foreach ($parametersMap as $argName => $argValue) {
            $args->addArgument($argName, $this->readValue($context, $argValue));
        }
    }

    /**
     * @param mixed $context
     * @param mixed $value
     * @return array|mixed
     */
    protected function readValue(&$context, $value)
    {
        if ($value instanceof PropertyPathInterface) {
            $value = $this->accessor->getValue($context, $value);
        } elseif (is_array($value)) {
            array_walk_recursive(
                $value,
                function (&$element) use (&$context) {
                    if ($element instanceof PropertyPathInterface) {
                        $element = $this->accessor->getValue($context, $element);
                    }
                }
            );
        }

        return $value;
    }

    /**
     * @param mixed $map
     * @throws \InvalidArgumentException
     */
    protected function assertTraversable($map)
    {
        if (!is_array($map) && !$map instanceof \Traversable) {
            throw new \InvalidArgumentException(
                'Parameters map must be array or implements \Traversable interface'
            );
        }
    }
}
