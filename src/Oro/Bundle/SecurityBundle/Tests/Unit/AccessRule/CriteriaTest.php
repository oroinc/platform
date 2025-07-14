<?php

namespace Oro\Bundle\SecurityBundle\Tests\Unit\AccessRule;

use Oro\Bundle\SecurityBundle\AccessRule\Criteria;
use Oro\Bundle\SecurityBundle\AccessRule\Expr\Comparison;
use Oro\Bundle\SecurityBundle\AccessRule\Expr\CompositeExpression;
use Oro\Bundle\SecurityBundle\AccessRule\Expr\Path;
use Oro\Bundle\SecurityBundle\ORM\Walker\AccessRuleWalker;
use PHPUnit\Framework\TestCase;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class CriteriaTest extends TestCase
{
    public function testGetDefaultPermission(): void
    {
        $criteria = new Criteria(AccessRuleWalker::ORM_RULES_TYPE, \stdClass::class, 'alias');
        $this->assertEquals('VIEW', $criteria->getPermission());
    }

    public function testGetPermission(): void
    {
        $criteria = new Criteria(AccessRuleWalker::ORM_RULES_TYPE, \stdClass::class, 'alias', 'EDIT');
        $this->assertEquals('EDIT', $criteria->getPermission());
    }

    public function testGetEntityClass(): void
    {
        $criteria = new Criteria(AccessRuleWalker::ORM_RULES_TYPE, \stdClass::class, 'alias');
        $this->assertEquals(\stdClass::class, $criteria->getEntityClass());
    }

    public function testGetAlias(): void
    {
        $criteria = new Criteria(AccessRuleWalker::ORM_RULES_TYPE, \stdClass::class, 'alias');
        $this->assertEquals('alias', $criteria->getAlias());
    }

    public function testDefaultIsRoot(): void
    {
        $criteria = new Criteria(AccessRuleWalker::ORM_RULES_TYPE, \stdClass::class, 'alias', "VIEW");
        $this->assertTrue($criteria->isRoot());
    }

    public function testIsRoot(): void
    {
        $criteria = new Criteria(AccessRuleWalker::ORM_RULES_TYPE, \stdClass::class, 'alias', "VIEW", false);
        $this->assertFalse($criteria->isRoot());
    }

    public function testEmptyExpression(): void
    {
        $criteria = new Criteria(AccessRuleWalker::ORM_RULES_TYPE, \stdClass::class, 'alias');
        $this->assertNull($criteria->getExpression());
    }

    public function testAndOnEmptyCriteriaExpression(): void
    {
        $criteria = new Criteria(AccessRuleWalker::ORM_RULES_TYPE, \stdClass::class, 'alias');
        $criteria->andExpression(new Comparison(new Path('owner'), Comparison::IN, [1,2,3,4,5]));

        $this->assertEquals(
            new Comparison(new Path('owner'), Comparison::IN, [1,2,3,4,5]),
            $criteria->getExpression()
        );
    }

    public function testOrOnEmptyCriteriaExpression(): void
    {
        $criteria = new Criteria(AccessRuleWalker::ORM_RULES_TYPE, \stdClass::class, 'alias');
        $criteria->orExpression(new Comparison(new Path('owner'), Comparison::IN, [1,2,3,4,5]));

        $this->assertEquals(
            new Comparison(new Path('owner'), Comparison::IN, [1,2,3,4,5]),
            $criteria->getExpression()
        );
    }

    public function testAnd(): void
    {
        $criteria = new Criteria(AccessRuleWalker::ORM_RULES_TYPE, \stdClass::class, 'alias');
        $criteria->andExpression(new Comparison(new Path('owner'), Comparison::IN, [1,2,3,4,5]));
        $criteria->andExpression(new Comparison(new Path('organization'), Comparison::EQ, 1));

        $this->assertEquals(
            new CompositeExpression(
                CompositeExpression::TYPE_AND,
                [
                    new Comparison(new Path('owner'), Comparison::IN, [1,2,3,4,5]),
                    new Comparison(new Path('organization'), Comparison::EQ, 1)
                ]
            ),
            $criteria->getExpression()
        );
    }

    public function testOr(): void
    {
        $criteria = new Criteria(AccessRuleWalker::ORM_RULES_TYPE, \stdClass::class, 'alias');
        $criteria->andExpression(new Comparison(new Path('owner'), Comparison::IN, [1,2,3,4,5]));
        $criteria->orExpression(new Comparison(new Path('owner'), Comparison::EQ, 10));

        $this->assertEquals(
            new CompositeExpression(
                CompositeExpression::TYPE_OR,
                [
                    new Comparison(new Path('owner'), Comparison::IN, [1,2,3,4,5]),
                    new Comparison(new Path('owner'), Comparison::EQ, 10)
                ]
            ),
            $criteria->getExpression()
        );
    }

    public function testHasOnNotExistAdditionalDataItem(): void
    {
        $criteria = new Criteria(AccessRuleWalker::ORM_RULES_TYPE, \stdClass::class, 'alias');
        $this->assertFalse($criteria->hasOption('test'));
    }

    public function testGetOnNotExistAdditionalDataItemWithDefaultValue(): void
    {
        $criteria = new Criteria(AccessRuleWalker::ORM_RULES_TYPE, \stdClass::class, 'alias');
        $this->assertNull($criteria->getOption('test'));
    }

    public function testGetOnNotExistAdditionalDataItem(): void
    {
        $criteria = new Criteria(AccessRuleWalker::ORM_RULES_TYPE, \stdClass::class, 'alias');
        $this->assertEquals('default', $criteria->getOption('test', 'default'));
    }

    public function testSetOption(): void
    {
        $criteria = new Criteria(AccessRuleWalker::ORM_RULES_TYPE, \stdClass::class, 'alias');
        $criteria->setOption('test', 'value');

        $this->assertTrue($criteria->hasOption('test'));
        $this->assertEquals('value', $criteria->getOption('test'));
    }

    public function testGetType(): void
    {
        $type = 'test';
        $criteria = new Criteria($type, \stdClass::class, 'alias');

        $this->assertEquals($type, $criteria->getType());
    }

    public function testSetExpression(): void
    {
        $expression = new Comparison(new Path('owner'), Comparison::IN, [1,2,3,4,5]);
        $criteria = new Criteria(AccessRuleWalker::ORM_RULES_TYPE, \stdClass::class, 'alias');

        $criteria->setExpression($expression);
        $this->assertEquals($expression, $criteria->getExpression());
    }
}
