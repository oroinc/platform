<?php
namespace Oro\Bundle\SecurityBundle\Tests\Unit\ORM\Walker;

use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Query;
use Doctrine\ORM\Query\AST\SelectStatement;
use Doctrine\ORM\Query\AST\PathExpression;

use Oro\Bundle\TestFrameworkBundle\Test\Doctrine\ORM\OrmTestCase;

use Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper;
use Oro\Bundle\SecurityBundle\ORM\Walker\AclWalker;
use Oro\Bundle\SecurityBundle\ORM\Walker\AclConditionalFactorBuilder;
use Oro\Bundle\SecurityBundle\ORM\Walker\OwnershipConditionDataBuilder;

class AclHelperTest extends OrmTestCase
{
    /**
     * @var AclHelper
     */
    protected $helper;

    /**
     * @var OwnershipConditionDataBuilder
     */
    protected $conditionBuilder;

    /**
     * @var QueryBuilder
     */
    protected $queryBuilder;

    /**
     * @var AclWalker
     */
    protected $walker;

    public function testApplyAclToCriteria()
    {
        $conditionBuilder = $this->getMockBuilder(
            'Oro\Bundle\SecurityBundle\ORM\Walker\OwnershipConditionDataBuilder'
        )
            ->disableOriginalConstructor()
            ->getMock();
        $conditionBuilder->expects($this->any())
            ->method('getAclConditionData')
            ->will(
                $this->returnValue(
                    [
                        'owner',
                        1,
                        4,
                        'organization',
                        10,
                        false
                    ]
                )
            );
        $criteria = new Criteria();

        $eventDispatcher = $this->getMock('Symfony\Component\EventDispatcher\EventDispatcherInterface');

        $conditionalFactorBuilder = new AclConditionalFactorBuilder();

        $helper = new AclHelper($conditionBuilder, $eventDispatcher, $conditionalFactorBuilder);

        $result = $helper->applyAclToCriteria('oroTestClass', $criteria, 'TEST_PERMISSION');
        $whereExpression = $result->getWhereExpression();
        $this->assertEquals('AND', $whereExpression->getType());
        $expressions = $whereExpression->getExpressionList();
        $this->assertEquals(2, count($expressions));

        $firstExpr = $expressions[0];
        $this->assertEquals('organization', $firstExpr->getField());
        $this->assertEquals('IN', $firstExpr->getOperator());
        $this->assertEquals([10], $firstExpr->getValue()->getValue());

        $secondExpr = $expressions[1];
        $this->assertEquals('owner', $secondExpr->getField());
        $this->assertEquals('IN', $secondExpr->getOperator());
        $this->assertEquals([1], $secondExpr->getValue()->getValue());
    }

    /**
     * @dataProvider dataProvider
     */
    public function testApply(QueryBuilder $queryBuilder, $conditions, $resultHandler, $walkerResult, $exception)
    {
        $eventDispatcher = $this->getMock('Symfony\Component\EventDispatcher\EventDispatcherInterface');

        $this->conditionBuilder = $this->getMockBuilder(
            'Oro\Bundle\SecurityBundle\ORM\Walker\OwnershipConditionDataBuilder'
        )
            ->disableOriginalConstructor()
            ->getMock();

        $this->conditionBuilder->expects($this->any())
            ->method('getAclConditionData')
            ->will(
                $this->returnCallback(
                    function ($entityName, $permission) use ($conditions) {
                        if (isset($conditions[$entityName])) {
                            return $conditions[$entityName];
                        }

                        return null;
                    }
                )
            );

        $conditionalFactorBuilder = new AclConditionalFactorBuilder();

        $this->helper = new AclHelper($this->conditionBuilder, $eventDispatcher, $conditionalFactorBuilder);
        $query        = $this->helper->apply($queryBuilder);
        $this->$resultHandler($query->getHints());

        $parserResult = $this->getMockBuilder('Doctrine\ORM\Query\ParserResult')
            ->disableOriginalConstructor()
            ->getMock();
        $this->assertEquals($query->getDQL(), $queryBuilder->getDQL());

        $this->walker = new AclWalker($query, $parserResult, []);
        $resultAst    = $this->walker->walkSelectStatement($query->getAST());

        $this->$walkerResult($resultAst);

        if ($exception) {
            list($class, $message) = $exception;
            $this->setExpectedException($class, $message);
        }
        $this->assertNotEmpty($query->getSQL());
    }

    /**
     * @return array
     *
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function dataProvider()
    {
        return [
            [
                $this->getRequest0(),
                [],
                'resultHelper0',
                'resultWalker0',
                []
            ],
            [
                $this->getRequest1(),
                [
                    'Oro\Bundle\SecurityBundle\Tests\Unit\Fixtures\Models\CMS\CmsUser'    => [
                        'id',
                        [1, 2, 3],
                        PathExpression::TYPE_STATE_FIELD,
                        null,
                        null,
                        false
                    ],
                    'Oro\Bundle\SecurityBundle\Tests\Unit\Fixtures\Models\CMS\CmsAddress' => [
                        'user',
                        [1],
                        PathExpression::TYPE_SINGLE_VALUED_ASSOCIATION,
                        'organization',
                        1,
                        false
                    ]
                ],
                'resultHelper1',
                'resultWalker1',
                []
            ],
            [
                $this->getRequest2(),
                [
                    'Oro\Bundle\SecurityBundle\Tests\Unit\Fixtures\Models\CMS\CmsUser'    => [],
                    'Oro\Bundle\SecurityBundle\Tests\Unit\Fixtures\Models\CMS\CmsAddress' => [
                        'user',
                        [1],
                        PathExpression::TYPE_SINGLE_VALUED_ASSOCIATION,
                        'organization',
                        1,
                        true
                    ]
                ],
                'resultHelper2',
                'resultWalker2',
                []
            ],
            [
                $this->getRequest3(),
                [
                    'Oro\Bundle\SecurityBundle\Tests\Unit\Fixtures\Models\CMS\CmsUser'    => [
                        'id',
                        [3, 2, 1],
                        PathExpression::TYPE_STATE_FIELD,
                        null,
                        null,
                        false
                    ],
                    'Oro\Bundle\SecurityBundle\Tests\Unit\Fixtures\Models\CMS\CmsArticle' => [
                        'user',
                        [10],
                        PathExpression::TYPE_SINGLE_VALUED_ASSOCIATION,
                        'organization',
                        1,
                        false
                    ],
                    'Oro\Bundle\SecurityBundle\Tests\Unit\Fixtures\Models\CMS\CmsComment' => [
                        'article',
                        [100],
                        PathExpression::TYPE_SINGLE_VALUED_ASSOCIATION,
                        'organization',
                        1,
                        false
                    ],
                    'Oro\Bundle\SecurityBundle\Tests\Unit\Fixtures\Models\CMS\CmsAddress' => [
                        'user',
                        [150],
                        PathExpression::TYPE_SINGLE_VALUED_ASSOCIATION,
                        'organization',
                        1,
                        false
                    ]
                ],
                'resultHelper3',
                'resultWalker3',
                []
            ],
            [
                $this->getRequest4(),
                [
                    'Oro\Bundle\SecurityBundle\Tests\Unit\Fixtures\Models\CMS\CmsArticle' => [
                        'user',
                        [10],
                        PathExpression::TYPE_SINGLE_VALUED_ASSOCIATION,
                        'organization',
                        1,
                        false
                    ],
                    'Oro\Bundle\SecurityBundle\Tests\Unit\Fixtures\Models\CMS\CmsUser'    => [
                        'id',
                        [3, 2, 1],
                        PathExpression::TYPE_STATE_FIELD,
                        null,
                        null,
                        false
                    ],
                ],
                'resultHelper4',
                'resultWalker4',
                []
            ],
            [
                $this->getRequest5(),
                [
                    'Oro\Bundle\SecurityBundle\Tests\Unit\Fixtures\Models\CMS\CmsUser' => [
                        'id',
                        [3, 2, 1],
                        PathExpression::TYPE_STATE_FIELD,
                        null,
                        null,
                        false
                    ],
                ],
                'resultHelper5',
                'resultWalker5',
                []
            ],
            [
                $this->getRequest6(),
                [
                    'Oro\Bundle\SecurityBundle\Tests\Unit\Fixtures\Models\CMS\CmsArticle' => [
                        'user',
                        [10],
                        PathExpression::TYPE_SINGLE_VALUED_ASSOCIATION,
                        'organization',
                        1,
                        false
                    ],
                    'Oro\Bundle\SecurityBundle\Tests\Unit\Fixtures\Models\CMS\CmsUser'    => [
                        'id',
                        [3, 2, 1],
                        PathExpression::TYPE_STATE_FIELD,
                        null,
                        null,
                        false
                    ],
                ],
                'resultHelper6',
                'resultWalker6',
                []
            ],
        ];
    }

    protected function getRequest0()
    {
        return $this->getQueryBuilder()
            ->select('u')
            ->from('Oro\Bundle\SecurityBundle\Tests\Unit\Fixtures\Models\CMS\CmsUser', 'u');
    }

    protected function resultHelper0($hints)
    {
        $whereCondition = $hints[AclWalker::ORO_ACL_CONDITION]->getWhereConditions()[0];
        $this->assertEquals('u', $whereCondition->getEntityAlias());
        $this->assertNull($whereCondition->getEntityField());
        $this->assertNull($whereCondition->getValue());
    }

    protected function resultWalker0(SelectStatement $resultAst)
    {
        // 1=0 expression
        $expression = $resultAst
            ->whereClause
            ->conditionalExpression
            ->conditionalFactors[0]
            ->simpleConditionalExpression;

        $leftExpression  = $expression->leftExpression;
        $rightExpression = $expression->rightExpression;
        $this->assertEquals(1, $leftExpression->simpleArithmeticExpression->value);
        $this->assertEquals('=', $expression->operator);
        $this->assertEquals(0, $rightExpression->simpleArithmeticExpression->value);
    }

    protected function getRequest1()
    {
        return $this->getQueryBuilder()
            ->select('u')
            ->from('Oro\Bundle\SecurityBundle\Tests\Unit\Fixtures\Models\CMS\CmsUser', 'u')
            ->join('u.address', 'address');
    }

    protected function resultHelper1($hints)
    {
        $whereCondition = $hints[AclWalker::ORO_ACL_CONDITION]->getWhereConditions()[0];
        $this->assertEquals('u', $whereCondition->getEntityAlias());
        $this->assertEquals('id', $whereCondition->getEntityField());
        $this->assertEquals([1, 2, 3], $whereCondition->getValue());
        $joinCondition = $hints[AclWalker::ORO_ACL_CONDITION]->getJoinConditions()[0];
        $this->assertEquals('address', $joinCondition->getEntityAlias());
        $this->assertEquals(
            'Oro\Bundle\SecurityBundle\Tests\Unit\Fixtures\Models\CMS\CmsAddress',
            $joinCondition->getEntityClass()
        );
        $this->assertEquals([1], $joinCondition->getValue());
    }

    protected function resultWalker1(SelectStatement $resultAst)
    {
        $expression = $resultAst
            ->whereClause
            ->conditionalExpression
            ->conditionalFactors[0]
            ->simpleConditionalExpression;
        $this->assertEquals([1, 2, 3], $this->collectLiterals($expression->literals));
        $this->assertEquals('u', $expression->expression->simpleArithmeticExpression->identificationVariable);
        $join = $resultAst->fromClause->identificationVariableDeclarations[0]->joins[0];
        $conditionalFactors = $join->conditionalExpression->conditionalFactors;
        $this->assertCount(2, $conditionalFactors);
        $expression = $conditionalFactors[0]->simpleConditionalExpression;
        $this->assertEquals([1], $this->collectLiterals($expression->literals));
        $this->assertEquals('user', $expression->expression->simpleArithmeticExpression->field);
        $this->assertEquals('address', $expression->expression->simpleArithmeticExpression->identificationVariable);
        $expression = $conditionalFactors[1]->simpleConditionalExpression;
        $this->assertEquals(1, $expression->rightExpression->simpleArithmeticExpression->value);
        $this->assertEquals('organization', $expression->leftExpression->simpleArithmeticExpression->field);
        $this->assertEquals('address', $expression->leftExpression->simpleArithmeticExpression->identificationVariable);
    }

    protected function getRequest2()
    {
        return $this->getQueryBuilder()
            ->select('u')
            ->from('Oro\Bundle\SecurityBundle\Tests\Unit\Fixtures\Models\CMS\CmsUser', 'u')
            ->join('u.address', 'address', 'WITH', 'address.id = u.id');
    }

    protected function resultHelper2($hints)
    {
        $this->assertEmpty($hints[AclWalker::ORO_ACL_CONDITION]->getWhereConditions());
        $joinCondition = $hints[AclWalker::ORO_ACL_CONDITION]->getJoinConditions()[0];
        $this->assertEquals([1], $joinCondition->getValue());

    }

    protected function resultWalker2(SelectStatement $resultAst)
    {
        $this->assertNull($resultAst->whereClause);
        $join = $resultAst->fromClause->identificationVariableDeclarations[0]->joins[0];
        $conditionalFactors = $join->conditionalExpression->conditionalFactors;
        $this->assertCount(1, $conditionalFactors);
        $expression = $conditionalFactors[0]->simpleConditionalExpression;
        $this->assertEquals(1, $expression->rightExpression->simpleArithmeticExpression->value);
        $this->assertEquals('organization', $expression->leftExpression->simpleArithmeticExpression->field);
        $this->assertEquals('address', $expression->leftExpression->simpleArithmeticExpression->identificationVariable);
    }

    protected function getRequest3()
    {
        $subRequest = $this->getQueryBuilder()
            ->select('users.id')
            ->from('Oro\Bundle\SecurityBundle\Tests\Unit\Fixtures\Models\CMS\CmsUser', 'users')
            ->join('users.articles', 'articles')
            ->join('articles.comments', 'comments')
            ->join(
                'Oro\Bundle\SecurityBundle\Tests\Unit\Fixtures\Models\CMS\CmsAddress',
                'address',
                'WITH',
                'address.user = users.id AND address = 1'
            )
            ->where('comments.id in (1, 2, 3)');

        $qb = $this->getQueryBuilder();
        $qb->select('u')
            ->from('Oro\Bundle\SecurityBundle\Tests\Unit\Fixtures\Models\CMS\CmsUser', 'u')
            ->where(
                $qb->expr()->in('u.id', $subRequest->getDQL())
            );

        return $qb;
    }

    protected function resultHelper3($hints)
    {
        $conditions = $hints[AclWalker::ORO_ACL_CONDITION];
        $this->assertEmpty($conditions->getJoinConditions());
        $whereCondition = $conditions->getWhereConditions()[0];
        $this->assertEquals([3, 2, 1], $whereCondition->getValue());
        $subRequest = $conditions->getSubRequests()[0];
        $this->assertEquals([3, 2, 1], $subRequest->getWhereConditions()[0]->getValue());
        $this->assertEquals(
            'Oro\Bundle\SecurityBundle\Tests\Unit\Fixtures\Models\CMS\CmsArticle',
            $subRequest->getJoinConditions()[0]->getEntityClass()
        );
        $this->assertEquals(
            'Oro\Bundle\SecurityBundle\Tests\Unit\Fixtures\Models\CMS\CmsComment',
            $subRequest->getJoinConditions()[1]->getEntityClass()
        );
        $this->assertEquals([150], $subRequest->getJoinConditions()[2]->getValue());
    }

    protected function resultWalker3(SelectStatement $resultAst)
    {
        $whereExpression = $resultAst
            ->whereClause
            ->conditionalExpression
            ->conditionalFactors[1]
            ->simpleConditionalExpression;
        $this->assertEquals([3, 2, 1], $this->collectLiterals($whereExpression->literals));
        $subselect  = $resultAst->whereClause
            ->conditionalExpression
            ->conditionalFactors[0]
            ->simpleConditionalExpression
            ->subselect;
        $expression = $subselect->whereClause->conditionalExpression
            ->conditionalFactors[1]->simpleConditionalExpression;
        $this->assertEquals([3, 2, 1], $this->collectLiterals($expression->literals));
    }

    protected function getRequest4()
    {
        $qb = $this->getQueryBuilder();
        $qb->select('u')
            ->from('Oro\Bundle\SecurityBundle\Tests\Unit\Fixtures\Models\CMS\CmsUser', 'u')
            ->join('u.articles', 'art')
            ->where('art.id in (1,2,3)')
            ->orWhere(
                $qb->expr()->in(
                    'u.id',
                    'SELECT users.id FROM Oro\Bundle\SecurityBundle\Tests\Unit\Fixtures\Models\CMS\CmsUser users
                       JOIN users.articles articles
                       WHERE articles.id in (1,2,3)
                    '
                )
            );

        return $qb;
    }

    protected function resultHelper4($hints)
    {
        $whereCondition = $hints[AclWalker::ORO_ACL_CONDITION]->getWhereConditions()[0];
        $joinCondition  = $hints[AclWalker::ORO_ACL_CONDITION]->getJoinConditions()[0];
        $subRequest     = $hints[AclWalker::ORO_ACL_CONDITION]->getSubRequests()[0];
        $this->assertEquals([3, 2, 1], $whereCondition->getValue());
        $this->assertEquals([10], $joinCondition->getValue());
        $this->assertEquals(1, $subRequest->getFactorId());
    }

    protected function resultWalker4(SelectStatement $resultAst)
    {
        $subselect  = $resultAst->whereClause
            ->conditionalExpression
            ->conditionalFactors[0]
            ->conditionalExpression
            ->conditionalTerms[1]
            ->simpleConditionalExpression
            ->subselect;
        $expression = $subselect->whereClause->conditionalExpression
            ->conditionalFactors[1]->simpleConditionalExpression;
        $this->assertEquals([3, 2, 1], $this->collectLiterals($expression->literals));
    }

    protected function getRequest5()
    {
        $queryBuilder = $this->getQueryBuilder();
        $queryBuilder->select('u')
            ->from('Oro\Bundle\SecurityBundle\Tests\Unit\Fixtures\Models\CMS\CmsUser', 'u')
            ->join('u.articles', 'art')
            ->where('art.id IS NULL OR art.topic IS NULL');
        return $queryBuilder;
    }

    protected function resultHelper5($hints)
    {
        $whereCondition = $hints[AclWalker::ORO_ACL_CONDITION]->getWhereConditions()[0];
        $this->assertEquals([3, 2, 1], $whereCondition->getValue());
    }

    protected function resultWalker5(SelectStatement $resultAst)
    {
        $this->assertCount(
            2,
            $resultAst->whereClause->conditionalExpression->conditionalFactors
        );

        $this->assertInstanceOf(
            'Doctrine\ORM\Query\AST\ConditionalPrimary',
            $resultAst->whereClause->conditionalExpression->conditionalFactors[0]
        );

        $this->assertCount(
            2,
            $resultAst
                ->whereClause->conditionalExpression
                ->conditionalFactors[0]->conditionalExpression->conditionalTerms
        );
    }

    protected function getRequest6()
    {
        $qb = $this->getQueryBuilder();
        $qb->select('u')
            ->from('Oro\Bundle\SecurityBundle\Tests\Unit\Fixtures\Models\CMS\CmsUser', 'u')
            ->join('u.articles', 'art')
            ->join('art.comments', 'comments')
            ->where('art.id IS NOT NULL')
            ->andWhere('comments.id IS NOT NULL')
            ->andWhere(
                $qb->expr()->in(
                    'u.id',
                    'SELECT users.id FROM Oro\Bundle\SecurityBundle\Tests\Unit\Fixtures\Models\CMS\CmsUser users
                       JOIN users.articles articles
                       WHERE articles.id in (1,2,3)
                    '
                )
            );

        return $qb;
    }

    protected function resultHelper6($hints)
    {
        $whereCondition = $hints[AclWalker::ORO_ACL_CONDITION]->getWhereConditions()[0];
        $joinCondition  = $hints[AclWalker::ORO_ACL_CONDITION]->getJoinConditions()[0];
        $subRequest     = $hints[AclWalker::ORO_ACL_CONDITION]->getSubRequests()[0];
        $this->assertEquals([3, 2, 1], $whereCondition->getValue());
        $this->assertEquals([10], $joinCondition->getValue());
        $this->assertEquals(2, $subRequest->getFactorId());
    }

    protected function resultWalker6(SelectStatement $resultAst)
    {
        $subselect  = $resultAst->whereClause
            ->conditionalExpression
            ->conditionalFactors[2]
            ->simpleConditionalExpression
            ->subselect;
        $expression = $subselect->whereClause->conditionalExpression
            ->conditionalFactors[1]->simpleConditionalExpression;
        $this->assertEquals([3, 2, 1], $this->collectLiterals($expression->literals));
    }

    /**
     * @return QueryBuilder
     */
    protected function getQueryBuilder()
    {
        return new QueryBuilder($this->getTestEntityManager());
    }

    /**
     * Make array with literals values
     *
     * @param array $literals
     * @return array
     */
    protected function collectLiterals(array $literals)
    {
        $result = [];
        foreach ($literals as $literal) {
            $result[] = $literal->value;
        }

        return $result;
    }
}
