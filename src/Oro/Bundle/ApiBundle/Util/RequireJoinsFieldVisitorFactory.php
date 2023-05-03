<?php

namespace Oro\Bundle\ApiBundle\Util;

/**
 * The factory to create a new instance of RequireJoinsFieldVisitor class.
 */
class RequireJoinsFieldVisitorFactory
{
    private RequireJoinsDecisionMakerInterface $decisionMaker;

    public function __construct(RequireJoinsDecisionMakerInterface $decisionMaker)
    {
        $this->decisionMaker = $decisionMaker;
    }

    public function createExpressionVisitor(): RequireJoinsFieldVisitor
    {
        return new RequireJoinsFieldVisitor($this->decisionMaker);
    }
}
