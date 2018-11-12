<?php

namespace Oro\Bundle\SecurityBundle\Tests\Unit\AccessRule;

use Oro\Bundle\SecurityBundle\AccessRule\ChainAccessRule;
use Oro\Bundle\SecurityBundle\AccessRule\Criteria;
use Oro\Bundle\SecurityBundle\AccessRule\Expr\Comparison;
use Oro\Bundle\SecurityBundle\AccessRule\Expr\CompositeExpression;
use Oro\Bundle\SecurityBundle\AccessRule\Expr\Path;
use Oro\Bundle\SecurityBundle\ORM\Walker\AccessRuleWalker;
use Oro\Bundle\SecurityBundle\Tests\Unit\Fixtures\AccessRule\AccessRule1;
use Oro\Bundle\SecurityBundle\Tests\Unit\Fixtures\AccessRule\AccessRule2;
use PHPUnit\Framework\TestCase;

class ChainAccessRuleTest extends TestCase
{
    public function testChainAccessRule()
    {
        $rule1 = new AccessRule1();
        $rule2 = new AccessRule2();

        $ruleCollection = new ChainAccessRule();
        $ruleCollection->addRule($rule1);
        $ruleCollection->addRule($rule2);

        $criteria = new Criteria(AccessRuleWalker::ORM_RULES_TYPE, \stdClass::class, 'std');

        $ruleCollection->process($criteria);

        /** @var CompositeExpression $expression */
        $expression = $criteria->getExpression();
        $this->assertTrue($expression instanceof CompositeExpression);
        $this->assertEquals(CompositeExpression::TYPE_AND, $expression->getType());
        $expressions = $expression->getExpressionList();
        $this->assertCount(2, $expressions);
        $this->assertEquals(new Comparison(new Path('owner'), Comparison::IN, [1,2,3,4,5]), $expressions[0]);
        $this->assertEquals(new Comparison(new Path('organization'), Comparison::EQ, 1), $expressions[1]);
    }

    public function testChainAccessRuleWithNonApplicableRule()
    {
        $rule1 = new AccessRule1();
        $rule2 = new AccessRule2();
        $rule2->setIsApplicable(false);

        $ruleCollection = new ChainAccessRule();
        $ruleCollection->addRule($rule1);
        $ruleCollection->addRule($rule2);

        $criteria = new Criteria(AccessRuleWalker::ORM_RULES_TYPE, \stdClass::class, 'std');

        $ruleCollection->process($criteria);

        $expression = $criteria->getExpression();
        $this->assertTrue($expression instanceof Comparison);
        $this->assertEquals(new Comparison(new Path('owner'), Comparison::IN, [1,2,3,4,5]), $expression);
    }
}
