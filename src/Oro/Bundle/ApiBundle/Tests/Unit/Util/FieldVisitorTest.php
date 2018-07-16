<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Util;

use Doctrine\Common\Collections\Expr\Comparison;
use Doctrine\Common\Collections\Expr\CompositeExpression;
use Oro\Bundle\ApiBundle\Util\FieldVisitor;

class FieldVisitorTest extends \PHPUnit\Framework\TestCase
{
    /** @var FieldVisitor */
    private $fieldVisitor;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->fieldVisitor = new FieldVisitor();
    }

    public function testWalkComparison()
    {
        self::assertEmpty($this->fieldVisitor->getFields());

        $this->fieldVisitor->walkComparison(new Comparison('fieldName', Comparison::EQ, 'value'));

        self::assertCount(1, $this->fieldVisitor->getFields());
        self::assertSame(['fieldName'], $this->fieldVisitor->getFields());
    }

    public function testWalkCompositeExpression()
    {
        self::assertEmpty($this->fieldVisitor->getFields());

        $this->fieldVisitor->walkCompositeExpression(
            new CompositeExpression(
                CompositeExpression::TYPE_AND,
                [
                    new Comparison('fieldName1', Comparison::EQ, 'value'),
                    new Comparison('fieldName2', Comparison::EQ, 'value')
                ]
            )
        );

        $fields = $this->fieldVisitor->getFields();

        self::assertCount(2, $fields);
        self::assertSame(['fieldName1', 'fieldName2'], $fields);
    }
}
