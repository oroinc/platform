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
    protected $checkers = [];

    /** @var int */
    protected $numberOfCheckers = 0;

    /**
     * {@inheritdoc}
     */
    public function getIterator()
    {
        return new \ArrayIterator($this->checkers);
    }

    /**
     * Adds a checker to the chain
     *
     * @param ApplicableCheckerInterface $checker
     */
    public function addChecker(ApplicableCheckerInterface $checker)
    {
        $this->checkers[] = $checker;
        $this->numberOfCheckers++;
    }

    /**
     * Indicates whether the chain has checkers
     *
     * @return bool
     */
    public function isEmpty()
    {
        return empty($this->checkers);
    }

    /**
     * {@inheritdoc}
     */
    public function isApplicable(ContextInterface $context, array $processorAttributes)
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

    /**
     * @param ApplicableCheckerInterface $checker
     * @param ContextInterface           $context
     * @param array                      $processorAttributes
     *
     * @return int
     */
    protected function executeChecker(
        ApplicableCheckerInterface $checker,
        ContextInterface $context,
        array $processorAttributes
    ) {
        return $checker->isApplicable($context, $processorAttributes);
    }
}
