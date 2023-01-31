<?php

namespace Oro\Bundle\ApiBundle\Util;

/**
 * The factory to create a new instance of OptimizeJoinsFieldVisitor class.
 */
class OptimizeJoinsFieldVisitorFactory
{
    private OptimizeJoinsDecisionMakerInterface $decisionMaker;

    public function __construct(OptimizeJoinsDecisionMakerInterface $decisionMaker)
    {
        $this->decisionMaker = $decisionMaker;
    }

    public function createExpressionVisitor(): OptimizeJoinsFieldVisitor
    {
        return new OptimizeJoinsFieldVisitor($this->decisionMaker);
    }
}
