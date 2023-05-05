<?php

namespace Oro\Component\ChainProcessor;

/**
 * Delegates the decision whether a processor should be executed in the current execution context
 * to child checkers.
 * Implemented the following algorithm:
 * * NOT_APPLICABLE if any child checker desides that a processor is not applicable
 * * APPLICABLE if at least one child checker desides that a processor is applicable
 *   and there are no checkers that deside that a processor is not applicable
 * * ABSTAIN if there are no checkers that can deside whether a processor is applicable or not
 */
class ChainApplicableChecker implements ApplicableCheckerInterface, \IteratorAggregate
{
    /** @var ApplicableCheckerInterface[] */
    private array $checkers = [];
    private int $numberOfCheckers = 0;

    /**
     * {@inheritDoc}
     */
    public function getIterator(): \Traversable
    {
        return new \ArrayIterator($this->checkers);
    }

    /**
     * Adds a checker to the chain.
     */
    public function addChecker(ApplicableCheckerInterface $checker): void
    {
        $this->checkers[] = $checker;
        $this->numberOfCheckers++;
    }

    /**
     * Indicates whether the chain has checkers.
     */
    public function isEmpty(): bool
    {
        return empty($this->checkers);
    }

    /**
     * {@inheritDoc}
     */
    public function isApplicable(ContextInterface $context, array $processorAttributes): int
    {
        // by performance reasons we do not need a loop if only one checker is registered
        if (1 === $this->numberOfCheckers) {
            return $this->executeChecker($this->checkers[0], $context, $processorAttributes);
        }

        $result = self::ABSTAIN;
        foreach ($this->checkers as $checker) {
            $checkResult = $this->executeChecker($checker, $context, $processorAttributes);
            if (self::NOT_APPLICABLE === $checkResult) {
                $result = self::NOT_APPLICABLE;
                break;
            }
            if (self::APPLICABLE === $checkResult && self::ABSTAIN === $result) {
                $result = self::APPLICABLE;
            }
        }

        return $result;
    }

    protected function executeChecker(
        ApplicableCheckerInterface $checker,
        ContextInterface $context,
        array $processorAttributes
    ): int {
        return $checker->isApplicable($context, $processorAttributes);
    }
}
