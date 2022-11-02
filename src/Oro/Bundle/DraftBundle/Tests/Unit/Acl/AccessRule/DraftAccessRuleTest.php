<?php

namespace Oro\Bundle\DraftBundle\Tests\Unit\Acl\AccessRule;

use Oro\Bundle\DraftBundle\Acl\AccessRule\DraftAccessRule;
use Oro\Bundle\DraftBundle\Helper\DraftPermissionHelper;
use Oro\Bundle\DraftBundle\Tests\Unit\Stub\DraftableEntityStub;
use Oro\Bundle\SecurityBundle\AccessRule\Criteria;
use Oro\Bundle\SecurityBundle\AccessRule\Expr\Comparison;
use Oro\Bundle\SecurityBundle\AccessRule\Expr\CompositeExpression;
use Oro\Bundle\SecurityBundle\AccessRule\Expr\Path;
use Oro\Bundle\SecurityBundle\Authentication\TokenAccessorInterface;
use Oro\Bundle\SecurityBundle\ORM\Walker\AccessRuleWalker;
use Oro\Bundle\SecurityBundle\ORM\Walker\AclConditionDataBuilderInterface;

class DraftAccessRuleTest extends \PHPUnit\Framework\TestCase
{
    /** @var AclConditionDataBuilderInterface */
    private $builder;

    /** @var DraftPermissionHelper */
    private $draftPermissionHelper;

    /** @var TokenAccessorInterface */
    private $tokenAccessor;

    /** @var DraftAccessRule */
    private $rule;

    protected function setUp(): void
    {
        $this->builder = $this->createMock(AclConditionDataBuilderInterface::class);
        $this->draftPermissionHelper = $this->createMock(DraftPermissionHelper::class);
        $this->tokenAccessor = $this->createMock(TokenAccessorInterface::class);

        $this->rule = new DraftAccessRule(
            $this->builder,
            $this->draftPermissionHelper,
            $this->tokenAccessor
        );
    }

    /**
     * @dataProvider getIsApplicableDataProvider
     */
    public function testIsApplicable(
        bool $isEnabled,
        string $entityClass,
        string $permission,
        bool $expectedValue
    ): void {
        /** @var Criteria|\PHPUnit\Framework\MockObject\MockObject $criteria */
        $criteria = $this->createMock(Criteria::class);
        $criteria->expects($this->once())
            ->method('getEntityClass')
            ->willReturn($entityClass);
        $criteria->expects($this->once())
            ->method('getPermission')
            ->willReturn($permission);

        $this->rule->setEnabled($isEnabled);

        $this->assertEquals($expectedValue, $this->rule->isApplicable($criteria));
    }

    public function getIsApplicableDataProvider(): array
    {
        return [
            'disabled' => [
                'isEnabled' => false,
                'entityClass' => DraftableEntityStub::class,
                'permission' => 'VIEW',
                'expectedValue' => false
            ],
            'not DraftableInterface' => [
                'isEnabled' => true,
                'entityClass' => 'className',
                'permission' => 'VIEW',
                'expectedValue' => false
            ],
            'not view permission' => [
                'isEnabled' => true,
                'entityClass' => DraftableEntityStub::class,
                'permission' => 'CREATE',
                'expectedValue' => false
            ],
            'applicable' => [
                'isEnabled' => true,
                'entityClass' => DraftableEntityStub::class,
                'permission' => 'VIEW',
                'expectedValue' => true
            ],
        ];
    }

    public function testProcessFullAccess(): void
    {
        $permission = 'VIEW';
        $globalPermission = sprintf('%s_ALL_DRAFTS', $permission);

        $criteria = new Criteria(
            AccessRuleWalker::ORM_RULES_TYPE,
            DraftableEntityStub::class,
            'e',
            $permission
        );

        $this->draftPermissionHelper->expects($this->once())
            ->method('generateGlobalPermission')
            ->with($permission)
            ->willReturn($globalPermission);

        $this->builder->expects($this->once())
            ->method('getAclConditionData')
            ->with(DraftableEntityStub::class, $globalPermission)
            ->willReturn([]);

        $this->rule->setEnabled(true);
        $this->rule->process($criteria);

        $this->assertNull($criteria->getExpression());
    }

    public function testProcessNoAccess(): void
    {
        $permission = 'VIEW';
        $globalPermission = sprintf('%s_ALL_DRAFTS', $permission);
        $alias = 'e';

        $criteria = new Criteria(
            AccessRuleWalker::ORM_RULES_TYPE,
            DraftableEntityStub::class,
            $alias,
            $permission
        );

        $this->draftPermissionHelper->expects($this->once())
            ->method('generateGlobalPermission')
            ->with($permission)
            ->willReturn($globalPermission);

        $this->builder->expects($this->once())
            ->method('getAclConditionData')
            ->with(DraftableEntityStub::class, $globalPermission)
            ->willReturn([null, null, null, null, false]);

        $userId = 1;
        $this->tokenAccessor->expects($this->once())
            ->method('getUserId')
            ->willReturn($userId);

        $this->rule->setEnabled(true);
        $this->rule->process($criteria);

        $this->assertEquals(
            new Comparison(new Path('draftOwner', $alias), Comparison::EQ, $userId),
            $criteria->getExpression()
        );
    }

    public function testProcessOrganizationAccess(): void
    {
        $permission = 'VIEW';
        $globalPermission = sprintf('%s_ALL_DRAFTS', $permission);
        $alias = 'e';

        $criteria = new Criteria(
            AccessRuleWalker::ORM_RULES_TYPE,
            DraftableEntityStub::class,
            $alias,
            $permission
        );

        $this->draftPermissionHelper->expects($this->once())
            ->method('generateGlobalPermission')
            ->with($permission)
            ->willReturn($globalPermission);

        $userId = 1;
        $organizationId = 1;
        $this->tokenAccessor->expects($this->once())
            ->method('getUserId')
            ->willReturn($userId);
        $this->tokenAccessor->expects($this->once())
            ->method('getOrganizationId')
            ->willReturn($organizationId);

        $this->builder->expects($this->once())
            ->method('getAclConditionData')
            ->with(DraftableEntityStub::class, $globalPermission)
            ->willReturn([null, null, 'organization', $organizationId, false]);

        $this->rule->setEnabled(true);
        $this->rule->process($criteria);

        $this->assertEquals(
            new CompositeExpression(
                CompositeExpression::TYPE_OR,
                [
                    new Comparison(new Path('organization', $alias), Comparison::EQ, $organizationId),
                    new Comparison(new Path('draftOwner', $alias), Comparison::EQ, $userId)
                ]
            ),
            $criteria->getExpression()
        );
    }
}
