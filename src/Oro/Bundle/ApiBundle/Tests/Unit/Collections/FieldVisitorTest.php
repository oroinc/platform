<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Collections;

use Doctrine\Common\Collections\Expr\Comparison;
use Doctrine\Common\Collections\Expr\CompositeExpression;

use Oro\Bundle\ApiBundle\Collection\FieldVisitor;

class FieldVisitorTest extends \PHPUnit_Framework_TestCase
{
    /** @var \Oro\Bundle\ApiBundle\Collection\FieldVisitor */
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
        $this->assertEmpty($this->getObjectAttribute($this->fieldVisitor, 'fields'));

        $this->fieldVisitor->walkComparison(new Comparison('fieldName', Comparison::EQ, 'value'));

        $this->assertSame(['fieldName'], $this->fieldVisitor->getFields());

        $this->assertCount(1, $this->getObjectAttribute($this->fieldVisitor, 'fields'));
        $this->assertArrayHasKey('fieldName', $this->getObjectAttribute($this->fieldVisitor, 'fields'));
    }

    public function testWalkCompositeExpression()
    {
        $this->assertEmpty($this->getObjectAttribute($this->fieldVisitor, 'fields'));

        $this->fieldVisitor->walkCompositeExpression(
            new CompositeExpression(
                CompositeExpression::TYPE_AND,
                [
                    new Comparison('fieldName1', Comparison::EQ, 'value'),
                    new Comparison('fieldName2', Comparison::EQ, 'value'),
                ]
            )
        );

        $this->assertSame(['fieldName1', 'fieldName2'], $this->fieldVisitor->getFields());

        $fields = $this->getObjectAttribute($this->fieldVisitor, 'fields');
        $this->assertCount(2, $fields);
        $this->assertArrayHasKey('fieldName1', $fields);
        $this->assertArrayHasKey('fieldName2', $fields);
    }
}
