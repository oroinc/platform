<?php

namespace Oro\Bundle\ActivityListBundle\Tests\Unit\AccessRule;

use Oro\Bundle\ActivityListBundle\AccessRule\ActivityListAccessRule;
use Oro\Bundle\ActivityListBundle\Entity\ActivityList;
use Oro\Bundle\ActivityListBundle\Provider\ActivityListChainProvider;
use Oro\Bundle\ActivityListBundle\Tests\Unit\Provider\Fixture\TestActivityProvider;
use Oro\Bundle\SecurityBundle\AccessRule\Criteria;
use Oro\Bundle\SecurityBundle\AccessRule\Expr\Comparison;
use Oro\Bundle\SecurityBundle\AccessRule\Expr\CompositeExpression;
use Oro\Bundle\SecurityBundle\AccessRule\Expr\NullComparison;
use Oro\Bundle\SecurityBundle\AccessRule\Expr\Path;
use Oro\Bundle\SecurityBundle\ORM\Walker\AccessRuleWalker;
use Oro\Bundle\SecurityBundle\ORM\Walker\AclConditionDataBuilderInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ActivityListAccessRuleTest extends TestCase
{
    /** @var MockObject */
    private $builder;

    /** @var MockObject */
    private $activityListProvider;

    /** @var ActivityListAccessRule */
    private $rule;

    protected function setUp()
    {
        $this->builder = $this->createMock(AclConditionDataBuilderInterface::class);
        $this->activityListProvider = $this->createMock(ActivityListChainProvider::class);
        $this->rule = new ActivityListAccessRule($this->builder, $this->activityListProvider);
    }

    public function testIsApplicableWithNonORMcriteriaType()
    {
        $criteria = new Criteria('NonOrmType', \stdClass::class, 'e');
        $this->assertFalse($this->rule->isApplicable($criteria));
    }

    public function testIsApplicableWithNotSupportedEntity()
    {
        $criteria = new Criteria(AccessRuleWalker::ORM_RULES_TYPE, \stdClass::class, 'e');
        $this->assertFalse($this->rule->isApplicable($criteria));
    }

    public function testIsApplicableWithSupportedEntity()
    {
        $this->markTestSkipped('should be fixed in BAP-17679');
        $criteria = new Criteria(AccessRuleWalker::ORM_RULES_TYPE, ActivityList::class, 'e');
        $this->assertTrue($this->rule->isApplicable($criteria));
    }

    /**
     * @expectedException \LogicException
     * @codingStandardsIgnoreStart
     * @expectedExceptionMessage The "activityListActivityOwnerTableAlias" option was not set to ActivityListAccessRule access rule.
     * @codingStandardsIgnoreEnd
     */
    public function testProcessWithoutActivityOwnerTableAliasOption()
    {
        $criteria = new Criteria(AccessRuleWalker::ORM_RULES_TYPE, ActivityList::class, 'e');
        $this->rule->process($criteria);
    }

    public function testProcessWithEmptyActivityListProviders()
    {
        $criteria = new Criteria(AccessRuleWalker::ORM_RULES_TYPE, ActivityList::class, 'e');
        $criteria->setOption(ActivityListAccessRule::ACTIVITY_OWNER_TABLE_ALIAS, 'oa');

        $this->activityListProvider->expects($this->once())
            ->method('getProviders')
            ->willReturn([]);

        $this->rule->process($criteria);
        $this->assertEquals(
            new CompositeExpression(
                CompositeExpression::TYPE_OR,
                [
                    new CompositeExpression(
                        CompositeExpression::TYPE_AND,
                        [
                            new NullComparison(new Path('user', 'oa')),
                            new NullComparison(new Path('organization', 'oa')),
                        ]
                    )
                ]
            ),
            $criteria->getExpression()
        );
    }

    public function testProcess()
    {
        $criteria = new Criteria(AccessRuleWalker::ORM_RULES_TYPE, ActivityList::class, 'e');
        $criteria->setOption(ActivityListAccessRule::ACTIVITY_OWNER_TABLE_ALIAS, 'oa');

        $this->activityListProvider->expects($this->once())
            ->method('getProviders')
            ->willReturn([new TestActivityProvider()]);

        $this->builder->expects($this->once())
            ->method('getAclConditionData')
            ->with(TestActivityProvider::ACL_CLASS, 'VIEW')
            ->willReturn(['owner', [5,7,6], 'organization', 1, false]);

        $this->rule->process($criteria);
        $this->assertEquals(
            new CompositeExpression(
                CompositeExpression::TYPE_OR,
                [
                    new CompositeExpression(
                        CompositeExpression::TYPE_AND,
                        [
                            new Comparison(new Path('user', 'oa'), Comparison::IN, [5, 7, 6]),
                            new Comparison(new Path('organization', 'oa'), Comparison::EQ, 1),
                            new Comparison(
                                new Path('relatedActivityClass'),
                                Comparison::EQ,
                                TestActivityProvider::ACL_CLASS
                            )
                        ]
                    ),
                    new CompositeExpression(
                        CompositeExpression::TYPE_AND,
                        [
                            new NullComparison(new Path('user', 'oa')),
                            new NullComparison(new Path('organization', 'oa')),
                        ]
                    )
                ]
            ),
            $criteria->getExpression()
        );
    }
}
