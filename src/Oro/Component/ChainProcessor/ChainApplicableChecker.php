<?php

namespace Oro\Component\ChainProcessor;

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
            if ($checkResult === self::NOT_APPLICABLE) {
                $result = self::NOT_APPLICABLE;
                break;
            } elseif ($checkResult === self::APPLICABLE && $result === self::ABSTAIN) {
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
