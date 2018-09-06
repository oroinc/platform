<?php

namespace Oro\Bundle\ApiBundle\Util;

/**
 * The factory to create a new instance of OptimizeJoinsFieldVisitor class.
 */
class OptimizeJoinsFieldVisitorFactory
{
    /** @var OptimizeJoinsDecisionMakerInterface */
    private $decisionMaker;

    /**
     * @param OptimizeJoinsDecisionMakerInterface $decisionMaker
     */
    public function __construct(OptimizeJoinsDecisionMakerInterface $decisionMaker)
    {
        $this->decisionMaker = $decisionMaker;
    }

    /**
     * Creates a new instance of OptimizeJoinsFieldVisitor.
     *
     * @return OptimizeJoinsFieldVisitor
     */
    public function createExpressionVisitor()
    {
        return new OptimizeJoinsFieldVisitor($this->decisionMaker);
    }
}
