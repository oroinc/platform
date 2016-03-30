<?php

namespace Oro\Bundle\ActionBundle\Model\ActionGroup;

use Symfony\Component\PropertyAccess\PropertyPathInterface;

use Oro\Bundle\ActionBundle\Model\ActionGroupExecutionArgs;

use Oro\Component\Action\Model\ContextAccessor;

class PropertyMapper
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
    public function toArgs(ActionGroupExecutionArgs $args, $parametersMap, $context)
    {
        $this->assertTraversable($parametersMap);

        foreach ($parametersMap as $argName => $argValue) {
            $args->addParameter($argName, $this->readValue($context, $argValue));
        }
    }

    /**
     * @param array|\ArrayAccess|object $from context source
     * @param array|\Traversable $map
     * @param array|\ArrayAccess|object $to context target
     */
    public function transfer(&$from, $map, &$to)
    {
        $this->assertTraversable($map);

        foreach ($map as $target => $source) {
            $this->accessor->setValue($to, $target, $this->readValue($from, $source));
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
