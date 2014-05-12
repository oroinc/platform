<?php

namespace Oro\Bundle\EntityMergeBundle\Model\Strategy;

use Oro\Bundle\EntityMergeBundle\Data\FieldData;
use Oro\Bundle\EntityMergeBundle\Exception\InvalidArgumentException;

class DelegateStrategy implements StrategyInterface
{
    /**
     * @var StrategyInterface[]
     */
    protected $elements;

    /**
     * @param array $strategies
     */
    public function __construct(array $strategies = array())
    {
        $this->elements = array();

        foreach ($strategies as $strategy) {
            $this->add($strategy);
        }
    }

    /**
     * @param StrategyInterface $strategy
     */
    public function add(StrategyInterface $strategy)
    {
        if ($strategy === $this) {
            throw new InvalidArgumentException("Cannot add strategy to itself.");
        }
        $this->elements[$strategy->getName()] = $strategy;
    }

    /**
     * {@inheritdoc}
     */
    public function merge(FieldData $fieldData)
    {
        $delegate = $this->match($fieldData);

        if (!$delegate) {
            throw new InvalidArgumentException(
                sprintf('Cannot find merge strategy for "%s" field.', $fieldData->getFieldName())
            );
        }

        $delegate->merge($fieldData);
    }

    /**
     * {@inheritdoc}
     */
    public function supports(FieldData $fieldData)
    {
        return $this->match($fieldData) !== null;
    }

    /**
     * Match field data and field merger
     *
     * @param FieldData $fieldData
     * @return StrategyInterface|null
     */
    protected function match(FieldData $fieldData)
    {
        foreach ($this->elements as $strategy) {
            if ($strategy->supports($fieldData)) {
                return $strategy;
            }
        }
        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'delegate';
    }
}
