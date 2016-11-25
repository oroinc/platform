<?php

namespace Oro\Component\ConfigExpression\Condition;

use Oro\Component\ConfigExpression\Exception;
use Oro\Component\ConfigExpression\FactoryWithTypesInterface;

class ExpressionAvailable extends AbstractCondition
{
    /** @var FactoryWithTypesInterface */
    protected $factory;

    /** @var string */
    protected $name;

    /** @var string */
    protected $type;

    /**
     * @param FactoryWithTypesInterface $factory
     * @param string $name
     */
    public function __construct(FactoryWithTypesInterface $factory, $name)
    {
        $this->factory = $factory;
        $this->name = $name;
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * {@inheritdoc}
     */
    public function toArray()
    {
        return $this->convertToArray($this->type);
    }

    /**
     * {@inheritdoc}
     */
    public function compile($factoryAccessor)
    {
        return $this->convertToPhpCode($this->type, $factoryAccessor);
    }

    /**
     * {@inheritdoc}
     */
    public function initialize(array $options)
    {
        if (1 === count($options)) {
            $this->type = reset($options);
        } else {
            throw new Exception\InvalidArgumentException(
                sprintf('Options must have 1 element, but %d given.', count($options))
            );
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    protected function getMessageParameters($context)
    {
        return [
            '{{ type }}' => $this->type
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function isConditionAllowed($context)
    {
        return $this->factory->isTypeExists($this->type);
    }
}
