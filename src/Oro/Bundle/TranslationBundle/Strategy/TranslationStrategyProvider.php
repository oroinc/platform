<?php

namespace Oro\Bundle\TranslationBundle\Strategy;

class TranslationStrategyProvider
{
    /**
     * @var TranslationStrategyInterface
     */
    protected $strategy;

    /**
     * @param TranslationStrategyInterface $defaultStrategy
     */
    public function __construct(TranslationStrategyInterface $defaultStrategy)
    {
        $this->strategy = $defaultStrategy;
    }

    /**
     * @return TranslationStrategyInterface
     */
    public function getStrategy()
    {
        return $this->strategy;
    }

    /**
     * @param TranslationStrategyInterface $strategy
     */
    public function setStrategy(TranslationStrategyInterface $strategy)
    {
        $this->strategy = $strategy;
    }
}
