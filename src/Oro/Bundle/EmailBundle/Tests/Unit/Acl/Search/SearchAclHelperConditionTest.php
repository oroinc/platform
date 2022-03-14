<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\Acl\Search;

use Doctrine\Common\Collections\Expr\Comparison;
use Doctrine\Common\Collections\Expr\Expression;
use Oro\Bundle\EmailBundle\Acl\Search\SearchAclHelperCondition;
use Oro\Bundle\EmailBundle\Entity\EmailUser;
use Oro\Bundle\SearchBundle\Query\Query;
use Oro\Bundle\SearchBundle\Query\QueryStringExpressionVisitor;
use Oro\Bundle\SecurityBundle\ORM\Walker\AclConditionDataBuilderInterface;

class SearchAclHelperConditionTest extends \PHPUnit\Framework\TestCase
{
    /** @var AclConditionDataBuilderInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $aclConditionDataBuilder;

    /** @var SearchAclHelperCondition */
    private $searchAclHelperCondition;

    protected function setUp(): void
    {
        $this->aclConditionDataBuilder = $this->createMock(AclConditionDataBuilderInterface::class);

        $this->searchAclHelperCondition = new SearchAclHelperCondition($this->aclConditionDataBuilder);
    }

    private function getQueryString(Expression $expression): string
    {
        return $expression->visit(new QueryStringExpressionVisitor());
    }

    public function testIsApplicableForNonEmailUserEntityClass(): void
    {
        self::assertFalse($this->searchAclHelperCondition->isApplicable(\stdClass::class, 'VIEW'));
    }

    public function testIsApplicableForNonViewPermission(): void
    {
        self::assertFalse($this->searchAclHelperCondition->isApplicable(EmailUser::class, 'EDIT'));
    }

    public function testIsApplicableForUserEmailClassAndViewPermission(): void
    {
        self::assertTrue($this->searchAclHelperCondition->isApplicable(EmailUser::class, 'VIEW'));
    }

    /**
     * @dataProvider getTestData
     */
    public function testAddRestriction(
        $publicCondition,
        $privateCondition,
        $expectedExpression
    ): void {
        $query = new Query();
        $query->from([]);
        $this->aclConditionDataBuilder->expects(self::exactly(2))
            ->method('getAclConditionData')
            ->willReturnMap([
                [EmailUser::class, 'VIEW', $publicCondition],
                [EmailUser::class, 'VIEW_PRIVATE', $privateCondition]
            ]);

        $expression = $this->searchAclHelperCondition->addRestriction($query, 'em', null);
        self::assertEquals($expectedExpression, $this->getQueryString($expression));
        self::assertEquals(['em'], $query->getFrom());
    }

    public function getTestData(): array
    {
        return [
            'full access' => [
                [],
                [],
                '((integer email_user_private = 0 and integer oro_email_owner >= 0) '
                . 'or (integer email_user_private = 1 and integer oro_email_owner >= 0))'
            ],
            'private have partial access' => [
                [],
                ['owner', [3, 5, 4, 6]],
                '((integer email_user_private = 0 and integer oro_email_owner >= 0) '
                . 'or (integer email_user_private = 1 and integer oro_email_owner in (3, 5, 4, 6)))'
            ],
            'private have no access' => [
                [],
                [null, null, null, null],
                '((integer email_user_private = 0 and integer oro_email_owner >= 0))'
            ],
            'public have partial access' => [
                ['owner', [3, 5, 4, 6]],
                [],
                '((integer email_user_private = 0 and integer oro_email_owner in (3, 5, 4, 6)) '
                . 'or (integer email_user_private = 1 and integer oro_email_owner >= 0))'
            ],
            'public have no access' => [
                [null, null, null, null],
                [],
                '((integer email_user_private = 1 and integer oro_email_owner >= 0))'
            ]
        ];
    }

    public function testAddRestrictionWithNoAccess()
    {
        $query = new Query();
        $query->from([]);
        $query->getCriteria()->andWhere(new Comparison('test_data', Comparison::EQ, 'value'));

        $this->aclConditionDataBuilder->expects(self::exactly(2))
            ->method('getAclConditionData')
            ->willReturnMap([
                [EmailUser::class, 'VIEW', [null, null, null, null]],
                [EmailUser::class, 'VIEW_PRIVATE', [null, null, null, null]]
            ]);

        $expression = $this->searchAclHelperCondition->addRestriction(
            $query,
            'em',
            $query->getCriteria()->getWhereExpression()
        );

        self::assertSame($query->getCriteria()->getWhereExpression(), $expression);
        self::assertEquals([], $query->getFrom());
    }

    public function testAddRestrictionWithAccessAndExistingExpressions()
    {
        $query = new Query();
        $query->from([]);
        $query->getCriteria()->andWhere(new Comparison('test_data', Comparison::EQ, 'value'));

        $this->aclConditionDataBuilder->expects(self::exactly(2))
            ->method('getAclConditionData')
            ->willReturnMap([
                [EmailUser::class, 'VIEW', ['owner', [3, 5, 4, 6]]],
                [EmailUser::class, 'VIEW_PRIVATE', ['owner', [1, 2, 3]]]
            ]);

        $expression = $this->searchAclHelperCondition->addRestriction(
            $query,
            'em',
            $query->getCriteria()->getWhereExpression()
        );

        self::assertEquals(
            '(text test_data = "value"'
            . ' or (integer email_user_private = 0 and integer oro_email_owner in (3, 5, 4, 6))'
            . ' or (integer email_user_private = 1 and integer oro_email_owner in (1, 2, 3)))',
            $this->getQueryString($expression)
        );
        self::assertEquals(['em'], $query->getFrom());
    }
}
