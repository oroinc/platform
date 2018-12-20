<?php

namespace Oro\Bundle\SecurityBundle\Tests\Unit\AccessRule;

use Oro\Bundle\SecurityBundle\AccessRule\AclAccessRule;
use Oro\Bundle\SecurityBundle\AccessRule\Criteria;
use Oro\Bundle\SecurityBundle\AccessRule\Expr\AccessDenied;
use Oro\Bundle\SecurityBundle\AccessRule\Expr\Comparison;
use Oro\Bundle\SecurityBundle\AccessRule\Expr\CompositeExpression;
use Oro\Bundle\SecurityBundle\AccessRule\Expr\Path;
use Oro\Bundle\SecurityBundle\AccessRule\Expr\Value;
use Oro\Bundle\SecurityBundle\ORM\Walker\AccessRuleWalker;
use Oro\Bundle\SecurityBundle\ORM\Walker\AclConditionDataBuilderInterface;
use Oro\Bundle\SecurityBundle\Owner\Metadata\OwnershipMetadataProvider;
use Oro\Bundle\SecurityBundle\Tests\Unit\Fixtures\Models\CMS\CmsUser;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class AclAccessRuleTest extends TestCase
{
    /** @var AclAccessRule */
    private $accessRule;

    /** @var MockObject */
    private $builder;

    /** @var MockObject */
    private $ownershipMetadataProvider;

    protected function setUp()
    {
        $this->builder = $this->createMock(AclConditionDataBuilderInterface::class);
        $this->ownershipMetadataProvider = $this->createMock(OwnershipMetadataProvider::class);
        $this->accessRule = new AclAccessRule($this->builder, $this->ownershipMetadataProvider);
    }

    public function testIsApplicableIsRuleWasDisabled()
    {
        $criteria = new Criteria(AccessRuleWalker::ORM_RULES_TYPE, CmsUser::class, 'cmsUser');
        $criteria->setOption('aclDisable', true);

        $this->assertFalse($this->accessRule->isApplicable($criteria));
    }

    public function testIsApplicableIfCriteriaShouldBeProtected()
    {
        $criteria = new Criteria(AccessRuleWalker::ORM_RULES_TYPE, CmsUser::class, 'cmsUser');
        $this->assertTrue($this->accessRule->isApplicable($criteria));
    }

    public function testProcessOnEntityWithFullAccess()
    {
        $criteria = new Criteria(AccessRuleWalker::ORM_RULES_TYPE, CmsUser::class, 'cmsUser');

        $this->builder->expects($this->once())
            ->method('getAclConditionData')
            ->with(CmsUser::class, 'VIEW')
            ->willReturn([]);

        $this->accessRule->process($criteria);
        $this->assertNull($criteria->getExpression());
    }

    public function testProcessOnEntityWithNoAccess()
    {
        $criteria = new Criteria(AccessRuleWalker::ORM_RULES_TYPE, CmsUser::class, 'cmsUser');

        $this->builder->expects($this->once())
            ->method('getAclConditionData')
            ->with(CmsUser::class, 'VIEW')
            ->willReturn([null, null, null, null, null]);

        $this->accessRule->process($criteria);
        $this->assertInstanceOf(AccessDenied::class, $criteria->getExpression());
    }

    public function testProcessOnEntityWithSingleOwnerRestriction()
    {
        $criteria = new Criteria(AccessRuleWalker::ORM_RULES_TYPE, CmsUser::class, 'cmsUser');

        $this->builder->expects($this->once())
            ->method('getAclConditionData')
            ->with(CmsUser::class, 'VIEW')
            ->willReturn(['owner', 130, null, null, null]);

        $this->accessRule->process($criteria);
        $this->assertEquals(
            new Comparison(
                new Path('owner', 'cmsUser'),
                '=',
                new Value(130)
            ),
            $criteria->getExpression()
        );
    }

    public function testProcessOnEntityWithArrayOwnerRestriction()
    {
        $criteria = new Criteria(AccessRuleWalker::ORM_RULES_TYPE, CmsUser::class, 'cmsUser');

        $this->builder->expects($this->once())
            ->method('getAclConditionData')
            ->with(CmsUser::class, 'VIEW')
            ->willReturn(['owner', [5,7,6], null, null, null]);

        $this->accessRule->process($criteria);
        $this->assertEquals(
            new Comparison(
                new Path('owner', 'cmsUser'),
                'IN',
                new Value([5,7,6])
            ),
            $criteria->getExpression()
        );
    }

    public function testProcessOnEntityWithOrganizationRestriction()
    {
        $criteria = new Criteria(AccessRuleWalker::ORM_RULES_TYPE, CmsUser::class, 'cmsUser');

        $this->builder->expects($this->once())
            ->method('getAclConditionData')
            ->with(CmsUser::class, 'VIEW')
            ->willReturn([null, null, 'organization', 1, true]);

        $this->accessRule->process($criteria);
        $this->assertEquals(
            new Comparison(
                new Path('organization', 'cmsUser'),
                '=',
                new Value(1)
            ),
            $criteria->getExpression()
        );
    }

    public function testProcessOnEntityWithOwnerAndOrganizationRestriction()
    {
        $criteria = new Criteria(AccessRuleWalker::ORM_RULES_TYPE, CmsUser::class, 'cmsUser');

        $this->builder->expects($this->once())
            ->method('getAclConditionData')
            ->with(CmsUser::class, 'VIEW')
            ->willReturn(['owner', [5,7,6], 'organization', 1, false]);

        $this->accessRule->process($criteria);
        $this->assertEquals(
            new CompositeExpression(
                'AND',
                [
                    new Comparison(
                        new Path('owner', 'cmsUser'),
                        'IN',
                        new Value([5,7,6])
                    ),
                    new Comparison(
                        new Path('organization', 'cmsUser'),
                        '=',
                        new Value(1)
                    )
                ]
            ),
            $criteria->getExpression()
        );
    }
}
