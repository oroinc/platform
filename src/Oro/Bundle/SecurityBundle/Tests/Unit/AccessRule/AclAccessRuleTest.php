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

class AclAccessRuleTest extends \PHPUnit\Framework\TestCase
{
    /** @var AclConditionDataBuilderInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $builder;

    /** @var OwnershipMetadataProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $ownershipMetadataProvider;

    /** @var AclAccessRule */
    private $accessRule;

    protected function setUp(): void
    {
        $this->builder = $this->createMock(AclConditionDataBuilderInterface::class);
        $this->ownershipMetadataProvider = $this->createMock(OwnershipMetadataProvider::class);

        $this->accessRule = new AclAccessRule($this->builder, $this->ownershipMetadataProvider);
    }

    public function testIsApplicableIfCriteriaShouldBeProtected(): void
    {
        $criteria = new Criteria(AccessRuleWalker::ORM_RULES_TYPE, CmsUser::class, 'cmsUser');
        self::assertTrue($this->accessRule->isApplicable($criteria));
    }

    public function testProcessOnEntityWithFullAccess(): void
    {
        $criteria = new Criteria(AccessRuleWalker::ORM_RULES_TYPE, CmsUser::class, 'cmsUser');

        $this->builder->expects(self::once())
            ->method('getAclConditionData')
            ->with(CmsUser::class, 'VIEW')
            ->willReturn([]);

        $this->accessRule->process($criteria);
        self::assertNull($criteria->getExpression());
    }

    public function testProcessOnEntityWithNoAccess(): void
    {
        $criteria = new Criteria(AccessRuleWalker::ORM_RULES_TYPE, CmsUser::class, 'cmsUser');

        $this->builder->expects(self::once())
            ->method('getAclConditionData')
            ->with(CmsUser::class, 'VIEW')
            ->willReturn([null, null, null, null, null]);

        $this->accessRule->process($criteria);
        self::assertInstanceOf(AccessDenied::class, $criteria->getExpression());
    }

    public function testProcessOnEntityWithSingleOwnerRestriction(): void
    {
        $criteria = new Criteria(AccessRuleWalker::ORM_RULES_TYPE, CmsUser::class, 'cmsUser');

        $this->builder->expects(self::once())
            ->method('getAclConditionData')
            ->with(CmsUser::class, 'VIEW')
            ->willReturn(['owner', 130, null, null, null]);

        $this->accessRule->process($criteria);
        self::assertEquals(
            new Comparison(
                new Path('owner', 'cmsUser'),
                '=',
                new Value(130)
            ),
            $criteria->getExpression()
        );
    }

    public function testProcessOnEntityWithArrayOwnerRestriction(): void
    {
        $criteria = new Criteria(AccessRuleWalker::ORM_RULES_TYPE, CmsUser::class, 'cmsUser');

        $this->builder->expects(self::once())
            ->method('getAclConditionData')
            ->with(CmsUser::class, 'VIEW')
            ->willReturn(['owner', [5,7,6], null, null, null]);

        $this->accessRule->process($criteria);
        self::assertEquals(
            new Comparison(
                new Path('owner', 'cmsUser'),
                'IN',
                new Value([5,7,6])
            ),
            $criteria->getExpression()
        );
    }

    public function testProcessOnEntityWithOrganizationRestriction(): void
    {
        $criteria = new Criteria(AccessRuleWalker::ORM_RULES_TYPE, CmsUser::class, 'cmsUser');

        $this->builder->expects(self::once())
            ->method('getAclConditionData')
            ->with(CmsUser::class, 'VIEW')
            ->willReturn([null, null, 'organization', 1, true]);

        $this->accessRule->process($criteria);
        self::assertEquals(
            new Comparison(
                new Path('organization', 'cmsUser'),
                '=',
                new Value(1)
            ),
            $criteria->getExpression()
        );
    }

    public function testProcessOnEntityWithOwnerAndOrganizationRestriction(): void
    {
        $criteria = new Criteria(AccessRuleWalker::ORM_RULES_TYPE, CmsUser::class, 'cmsUser');

        $this->builder->expects(self::once())
            ->method('getAclConditionData')
            ->with(CmsUser::class, 'VIEW')
            ->willReturn(['owner', [5,7,6], 'organization', 1, false]);

        $this->accessRule->process($criteria);
        self::assertEquals(
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

    public function testProcessOnEntityWithOrganizationArrayRestriction(): void
    {
        $criteria = new Criteria(AccessRuleWalker::ORM_RULES_TYPE, CmsUser::class, 'cmsUser');

        $this->builder->expects(self::once())
            ->method('getAclConditionData')
            ->with(CmsUser::class, 'VIEW')
            ->willReturn([null, null, 'organization', [1, 2, 3], true]);

        $this->accessRule->process($criteria);
        self::assertEquals(
            new Comparison(
                new Path('organization', 'cmsUser'),
                'IN',
                new Value([1, 2, 3])
            ),
            $criteria->getExpression()
        );
    }
}
