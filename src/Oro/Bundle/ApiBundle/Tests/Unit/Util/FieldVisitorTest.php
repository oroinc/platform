<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Util;

use Doctrine\Common\Collections\Expr\Comparison;
use Doctrine\Common\Collections\Expr\CompositeExpression;
use Oro\Bundle\ApiBundle\Util\FieldVisitor;

class FieldVisitorTest extends \PHPUnit\Framework\TestCase
{
    private FieldVisitor $visitor;

    protected function setUp(): void
    {
        $this->visitor = new FieldVisitor();
    }

    public function testWalkComparison(): void
    {
        self::assertSame([], $this->visitor->getFields());

        $this->visitor->walkComparison(new Comparison('fieldName', Comparison::EQ, 'value'));

        self::assertSame(['fieldName'], $this->visitor->getFields());
    }

    public function testWalkCompositeExpression(): void
    {
        self::assertSame([], $this->visitor->getFields());

        $this->visitor->walkCompositeExpression(
            new CompositeExpression(
                CompositeExpression::TYPE_AND,
                [
                    new Comparison('fieldName1', Comparison::EQ, 'value'),
                    new Comparison('fieldName2', Comparison::EQ, 'value')
                ]
            )
        );

        self::assertSame(['fieldName1', 'fieldName2'], $this->visitor->getFields());
    }
}
