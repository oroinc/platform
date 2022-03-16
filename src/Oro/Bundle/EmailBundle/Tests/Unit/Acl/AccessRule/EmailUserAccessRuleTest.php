<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\Acl\AccessRule;

use Oro\Bundle\EmailBundle\Acl\AccessRule\EmailUserAccessRule;
use Oro\Bundle\EmailBundle\Entity\EmailUser;
use Oro\Bundle\SecurityBundle\AccessRule\Criteria;
use Oro\Bundle\SecurityBundle\AccessRule\Expr\AccessDenied;
use Oro\Bundle\SecurityBundle\AccessRule\Expr\Comparison;
use Oro\Bundle\SecurityBundle\AccessRule\Expr\CompositeExpression;
use Oro\Bundle\SecurityBundle\AccessRule\Expr\NullComparison;
use Oro\Bundle\SecurityBundle\AccessRule\Expr\Path;
use Oro\Bundle\SecurityBundle\AccessRule\Expr\Value;
use Oro\Bundle\SecurityBundle\ORM\Walker\AccessRuleWalker;
use Oro\Bundle\SecurityBundle\ORM\Walker\AclConditionDataBuilderInterface;

class EmailUserAccessRuleTest extends \PHPUnit\Framework\TestCase
{
    /** @var AclConditionDataBuilderInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $aclConditionDataBuilder;

    /** @var EmailUserAccessRule */
    private $accessRule;

    protected function setUp(): void
    {
        $this->aclConditionDataBuilder = $this->createMock(AclConditionDataBuilderInterface::class);

        $this->accessRule = new EmailUserAccessRule($this->aclConditionDataBuilder);
    }

    public function testIsApplicable(): void
    {
        $criteria = new Criteria(AccessRuleWalker::ORM_RULES_TYPE, EmailUser::class, 'eu');
        self::assertTrue($this->accessRule->isApplicable($criteria));
    }

    /**
     * @dataProvider getTestData
     */
    public function testProcess(
        $publicCondition,
        $privateCondition,
        $expectedExpression
    ): void {
        $criteria = new Criteria(AccessRuleWalker::ORM_RULES_TYPE, EmailUser::class, 'eu');
        if ($publicCondition) {
            $criteria->setExpression($publicCondition);
        }
        $this->aclConditionDataBuilder->expects(self::once())
            ->method('getAclConditionData')
            ->willReturnMap([
                [EmailUser::class, 'VIEW_PRIVATE', $privateCondition]
            ]);

        $this->accessRule->process($criteria);
        self::assertEquals($expectedExpression, $criteria->getExpression());
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function getTestData(): array
    {
        return [
            'full access' => [
                null,
                [],
                new CompositeExpression(
                    CompositeExpression::TYPE_OR,
                    [
                        new CompositeExpression(
                            CompositeExpression::TYPE_OR,
                            [
                                new Comparison(new Path('isEmailPrivate', 'eu'), Comparison::EQ, new Value(false)),
                                new NullComparison(new Path('isEmailPrivate', 'eu')),
                            ]
                        ),
                        new Comparison(new Path('isEmailPrivate', 'eu'), Comparison::EQ, new Value(true)),
                    ]
                )
            ],
            'private have partial access' => [
                null,
                ['owner', [3, 5], 'organization', 1, false],
                new CompositeExpression(
                    CompositeExpression::TYPE_OR,
                    [
                        new CompositeExpression(
                            CompositeExpression::TYPE_OR,
                            [
                                new Comparison(new Path('isEmailPrivate', 'eu'), Comparison::EQ, new Value(false)),
                                new NullComparison(new Path('isEmailPrivate', 'eu')),
                            ]
                        ),
                        new CompositeExpression(
                            CompositeExpression::TYPE_AND,
                            [
                                new CompositeExpression(
                                    CompositeExpression::TYPE_AND,
                                    [
                                        new Comparison(new Path('owner', 'eu'), Comparison::IN, new Value([3, 5])),
                                        new Comparison(new Path('organization', 'eu'), Comparison::EQ, new Value(1)),
                                    ]
                                ),
                                new Comparison(new Path('isEmailPrivate', 'eu'), Comparison::EQ, new Value(true)),
                            ]
                        )

                    ]
                )
            ],
            'private have no access' => [
                null,
                [null, null, null, null, null],
                new CompositeExpression(
                    CompositeExpression::TYPE_OR,
                    [
                        new CompositeExpression(
                            CompositeExpression::TYPE_OR,
                            [
                                new Comparison(new Path('isEmailPrivate', 'eu'), Comparison::EQ, new Value(false)),
                                new NullComparison(new Path('isEmailPrivate', 'eu')),
                            ]
                        ),
                        new CompositeExpression(
                            CompositeExpression::TYPE_AND,
                            [
                                new AccessDenied(),
                                new Comparison(new Path('isEmailPrivate', 'eu'), Comparison::EQ, new Value(true)),
                            ]
                        )

                    ]
                )
            ],
            'public have partial access' => [
                new CompositeExpression(
                    CompositeExpression::TYPE_AND,
                    [
                        new Comparison(new Path('owner', 'eu'), Comparison::IN, new Value([8, 6])),
                        new Comparison(new Path('organization', 'eu'), Comparison::EQ, new Value(1)),
                    ]
                ),
                [],
                new CompositeExpression(
                    CompositeExpression::TYPE_OR,
                    [
                        new CompositeExpression(
                            CompositeExpression::TYPE_AND,
                            [
                                new CompositeExpression(
                                    CompositeExpression::TYPE_AND,
                                    [
                                        new Comparison(new Path('owner', 'eu'), Comparison::IN, new Value([8, 6])),
                                        new Comparison(new Path('organization', 'eu'), Comparison::EQ, new Value(1)),
                                    ]
                                ),
                                new CompositeExpression(
                                    CompositeExpression::TYPE_OR,
                                    [
                                        new Comparison(
                                            new Path('isEmailPrivate', 'eu'),
                                            Comparison::EQ,
                                            new Value(false)
                                        ),
                                        new NullComparison(new Path('isEmailPrivate', 'eu')),
                                    ]
                                ),
                            ]
                        ),
                        new Comparison(new Path('isEmailPrivate', 'eu'), Comparison::EQ, new Value(true))
                    ]
                )
            ]
        ];
    }
}
