<?php

namespace Oro\Bundle\SecurityBundle\Tests\Unit\Fixtures\AccessRule;

use Oro\Bundle\SecurityBundle\AccessRule\AccessRuleInterface;
use Oro\Bundle\SecurityBundle\AccessRule\Criteria;
use Oro\Bundle\SecurityBundle\AccessRule\Expr\Comparison;
use Oro\Bundle\SecurityBundle\AccessRule\Expr\Path;

class AccessRule2 implements AccessRuleInterface
{
    private $isApplicable = true;

    public function process(Criteria $criteria): void
    {
        $criteria->andExpression(new Comparison(new Path('organization'), Comparison::EQ, 1));
    }

    public function isApplicable(Criteria $criteria): bool
    {
        return $this->isApplicable;
    }

    public function setIsApplicable(bool $isApplicable)
    {
        $this->isApplicable = $isApplicable;
    }
}
