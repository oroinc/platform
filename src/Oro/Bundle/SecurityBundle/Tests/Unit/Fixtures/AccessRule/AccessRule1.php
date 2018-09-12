<?php

namespace Oro\Bundle\SecurityBundle\Tests\Unit\Fixtures\AccessRule;

use Oro\Bundle\SecurityBundle\AccessRule\AccessRuleInterface;
use Oro\Bundle\SecurityBundle\AccessRule\Criteria;
use Oro\Bundle\SecurityBundle\AccessRule\Expr\Comparison;
use Oro\Bundle\SecurityBundle\AccessRule\Expr\Path;

class AccessRule1 implements AccessRuleInterface
{
    public function process(Criteria $criteria): void
    {
        $criteria->andExpression(new Comparison(new Path('owner'), Comparison::IN, [1,2,3,4,5]));
    }

    public function isApplicable(Criteria $criteria): bool
    {
        return true;
    }
}
