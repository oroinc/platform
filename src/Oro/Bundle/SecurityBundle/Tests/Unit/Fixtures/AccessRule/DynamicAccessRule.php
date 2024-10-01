<?php

namespace Oro\Bundle\SecurityBundle\Tests\Unit\Fixtures\AccessRule;

use Oro\Bundle\SecurityBundle\AccessRule\AccessRuleInterface;
use Oro\Bundle\SecurityBundle\AccessRule\Criteria;

class DynamicAccessRule implements AccessRuleInterface
{
    /** @var callable */
    private $expression;

    #[\Override]
    public function process(Criteria $criteria): void
    {
        call_user_func($this->expression, $criteria);
    }

    public function setRule(callable $expression)
    {
        $this->expression = $expression;
    }

    #[\Override]
    public function isApplicable(Criteria $criteria): bool
    {
        return true;
    }
}
