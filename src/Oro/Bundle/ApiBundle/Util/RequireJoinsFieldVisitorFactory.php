<?php

namespace Oro\Bundle\ApiBundle\Util;

/**
 * The factory to create a new instance of RequireJoinsFieldVisitor class.
 */
class RequireJoinsFieldVisitorFactory
{
    /** @var RequireJoinsDecisionMakerInterface */
    private $decisionMaker;

    /**
     * @param RequireJoinsDecisionMakerInterface $decisionMaker
     */
    public function __construct(RequireJoinsDecisionMakerInterface $decisionMaker)
    {
        $this->decisionMaker = $decisionMaker;
    }

    /**
     * Creates a new instance of RequireJoinsFieldVisitor.
     *
     * @return RequireJoinsFieldVisitor
     */
    public function createExpressionVisitor()
    {
        return new RequireJoinsFieldVisitor($this->decisionMaker);
    }
}
