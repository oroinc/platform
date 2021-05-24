<?php

namespace Oro\Bundle\EntityMergeBundle\Model\Strategy;

use Oro\Bundle\EntityMergeBundle\Data\FieldData;
use Oro\Bundle\EntityMergeBundle\Exception\InvalidArgumentException;

/**
 * Delegates merging of entities to child strategies.
 */
class DelegateStrategy implements StrategyInterface
{
    private iterable $strategies;
    private ?array $initializedStrategies = null;

    /**
     * @param iterable|StrategyInterface[] $strategies
     */
    public function __construct(iterable $strategies)
    {
        $this->strategies = $strategies;
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'delegate';
    }

    /**
     * {@inheritdoc}
     */
    public function merge(FieldData $fieldData)
    {
        $strategy = $this->findStrategy($fieldData);
        if (null === $strategy) {
            throw new InvalidArgumentException(sprintf(
                'Cannot find merge strategy for "%s" field.',
                $fieldData->getFieldName()
            ));
        }

        $strategy->merge($fieldData);
    }

    /**
     * {@inheritdoc}
     */
    public function supports(FieldData $fieldData)
    {
        return $this->findStrategy($fieldData) !== null;
    }

    private function findStrategy(FieldData $fieldData): ?StrategyInterface
    {
        $strategies = $this->getStrategies();
        foreach ($strategies as $strategy) {
            if ($strategy->supports($fieldData)) {
                return $strategy;
            }
        }

        return null;
    }

    /**
     * @return StrategyInterface[]
     */
    private function getStrategies(): array
    {
        if (null === $this->initializedStrategies) {
            $initializedStrategies = [];
            /** @var StrategyInterface $strategy */
            foreach ($this->strategies as $strategy) {
                $initializedStrategies[$strategy->getName()] = $strategy;
            }
            $this->initializedStrategies = $initializedStrategies;
        }

        return $this->initializedStrategies;
    }
}
