<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Util;

use Doctrine\Common\Collections\Expr\Comparison;
use Doctrine\Common\Collections\Expr\CompositeExpression;

use Oro\Bundle\ApiBundle\Util\FieldVisitor;

class FieldVisitorTest extends \PHPUnit_Framework_TestCase
{
    /** @var FieldVisitor */
    protected $fieldVisitor;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->fieldVisitor = new FieldVisitor();
    }

    public function testWalkComparison()
    {
        $this->assertEmpty($this->fieldVisitor->getFields());

        $this->fieldVisitor->walkComparison(new Comparison('fieldName', Comparison::EQ, 'value'));

        $this->assertCount(1, $this->fieldVisitor->getFields());
        $this->assertSame(['fieldName'], $this->fieldVisitor->getFields());
    }

    public function testWalkCompositeExpression()
    {
        $this->assertEmpty($this->fieldVisitor->getFields());

        $this->fieldVisitor->walkCompositeExpression(
            new CompositeExpression(
                CompositeExpression::TYPE_AND,
                [
                    new Comparison('fieldName1', Comparison::EQ, 'value'),
                    new Comparison('fieldName2', Comparison::EQ, 'value'),
                ]
            )
        );

        $fields = $this->fieldVisitor->getFields();

        $this->assertCount(2, $fields);
        $this->assertSame(['fieldName1', 'fieldName2'], $fields);
    }
}
