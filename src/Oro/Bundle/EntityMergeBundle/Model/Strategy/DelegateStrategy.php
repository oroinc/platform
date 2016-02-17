<?php

namespace Oro\Bundle\EntityMergeBundle\Model\Strategy;

use Oro\Bundle\EntityMergeBundle\Data\FieldData;
use Oro\Bundle\EntityMergeBundle\Exception\InvalidArgumentException;
use Oro\Component\PhpUtils\ArrayUtil;

class DelegateStrategy implements StrategyInterface
{
    const DEFAULT_PRIORITY = 0;
    const PRIORITY_KEY = 'priority';
    const STRATEGY_KEY = 'strategy';

    /**
     * @var array ['strategy' => StrategyInterface, 'priority' => int ]
     */
    protected $elements;

    /**
     * @var bool
     */
    protected $ordered = false;

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
     * @param null|int          $priority
     *
     * @return DelegateStrategy
     */
    public function add(StrategyInterface $strategy, $priority = null)
    {
        if ($strategy === $this) {
            throw new InvalidArgumentException("Cannot add strategy to itself.");
        }
        if (is_null($priority)) {
            $priority = static::DEFAULT_PRIORITY;
        }

        $this->ordered = false;

        $this->elements[$strategy->getName()] = [static::STRATEGY_KEY => $strategy, static::PRIORITY_KEY => $priority];

        return $this;
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
     *
     * @return StrategyInterface|null
     */
    protected function match(FieldData $fieldData)
    {
        foreach ($this->getElements() as $element) {
            /** @var StrategyInterface $strategy */
            $strategy = $element[static::STRATEGY_KEY];
            if ($strategy->supports($fieldData)) {
                return $strategy;
            }
        }

        return null;
    }

    /**
     * @return array|StrategyInterface[]
     */
    protected function getElements()
    {
        if (!$this->ordered) {
            ArrayUtil::sortBy($this->elements, true, static::PRIORITY_KEY);

            $this->ordered = true;
        }

        return $this->elements;
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'delegate';
    }
}
