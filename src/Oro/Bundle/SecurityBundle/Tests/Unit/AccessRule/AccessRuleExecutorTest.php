<?php

namespace Oro\Bundle\SecurityBundle\Tests\Unit\AccessRule;

use Oro\Bundle\SecurityBundle\AccessRule\AccessRuleExecutor;
use Oro\Bundle\SecurityBundle\AccessRule\AccessRuleOptionMatcher;
use Oro\Bundle\SecurityBundle\AccessRule\Criteria;
use Oro\Bundle\SecurityBundle\AccessRule\Expr\Comparison;
use Oro\Bundle\SecurityBundle\AccessRule\Expr\CompositeExpression;
use Oro\Bundle\SecurityBundle\AccessRule\Expr\Path;
use Oro\Bundle\SecurityBundle\Authentication\TokenAccessorInterface;
use Oro\Bundle\SecurityBundle\ORM\Walker\AccessRuleWalker;
use Oro\Bundle\SecurityBundle\Tests\Unit\Fixtures\AccessRule\AccessRule1;
use Oro\Bundle\SecurityBundle\Tests\Unit\Fixtures\AccessRule\AccessRule2;
use Psr\Container\ContainerInterface;

class AccessRuleExecutorTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @param array $rules [service id => AccessRuleInterface or [AccessRuleInterface, options], ...]
     */
    private function getAccessRuleExecutor(array $rules): AccessRuleExecutor
    {
        $container = $this->createMock(ContainerInterface::class);
        $container->expects(self::any())
            ->method('get')
            ->willReturnCallback(function ($serviceId) use ($rules) {
                return is_array($rules[$serviceId]) ? $rules[$serviceId][0] : $rules[$serviceId];
            });

        $rulesForExecutor = [];
        foreach ($rules as $serviceId => $rule) {
            $rulesForExecutor[] = [$serviceId, is_array($rule) ? $rule[1] : []];
        }

        return new AccessRuleExecutor(
            $rulesForExecutor,
            $container,
            new AccessRuleOptionMatcher(
                $this->createMock(TokenAccessorInterface::class)
            )
        );
    }

    public function testProcess()
    {
        $rule1 = new AccessRule1();
        $rule2 = new AccessRule2();

        $accessRuleExecutor = $this->getAccessRuleExecutor([
            'rule1' => $rule1,
            'rule2' => $rule2
        ]);

        $criteria = new Criteria(AccessRuleWalker::ORM_RULES_TYPE, \stdClass::class, 'std');
        $accessRuleExecutor->process($criteria);

        /** @var CompositeExpression $expression */
        $expression = $criteria->getExpression();
        $this->assertInstanceOf(CompositeExpression::class, $expression);
        $this->assertEquals(CompositeExpression::TYPE_AND, $expression->getType());
        $expressions = $expression->getExpressionList();
        $this->assertCount(2, $expressions);
        $this->assertEquals(new Comparison(new Path('owner'), Comparison::IN, [1, 2, 3, 4, 5]), $expressions[0]);
        $this->assertEquals(new Comparison(new Path('organization'), Comparison::EQ, 1), $expressions[1]);
    }

    public function testProcessWithNonApplicableRule()
    {
        $rule1 = new AccessRule1();
        $rule2 = new AccessRule2();
        $rule2->setIsApplicable(false);

        $accessRuleExecutor = $this->getAccessRuleExecutor([
            'rule1' => $rule1,
            'rule2' => [$rule2, ['option1' => true, 'option2' => true]]
        ]);

        $criteria = new Criteria(AccessRuleWalker::ORM_RULES_TYPE, \stdClass::class, 'std');
        $criteria->setOption('option1', true);
        $criteria->setOption('option2', true);
        $accessRuleExecutor->process($criteria);

        $expression = $criteria->getExpression();
        $this->assertInstanceOf(Comparison::class, $expression);
        $this->assertEquals(new Comparison(new Path('owner'), Comparison::IN, [1, 2, 3, 4, 5]), $expression);
    }

    public function testProcessWithNonApplicableRuleByOptions()
    {
        $rule1 = new AccessRule1();
        $rule2 = new AccessRule2();

        $accessRuleExecutor = $this->getAccessRuleExecutor([
            'rule1' => $rule1,
            'rule2' => [$rule2, ['option1' => true, 'option2' => true]]
        ]);

        $criteria = new Criteria(AccessRuleWalker::ORM_RULES_TYPE, \stdClass::class, 'std');
        $criteria->setOption('option1', false);
        $criteria->setOption('option2', true);
        $accessRuleExecutor->process($criteria);

        $expression = $criteria->getExpression();
        $this->assertInstanceOf(Comparison::class, $expression);
        $this->assertEquals(new Comparison(new Path('owner'), Comparison::IN, [1, 2, 3, 4, 5]), $expression);
    }
}
