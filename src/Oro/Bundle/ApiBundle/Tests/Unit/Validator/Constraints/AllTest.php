<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Validator\Constraints;

use Oro\Bundle\ApiBundle\Validator\Constraints\All;
use Symfony\Component\Validator\Constraints\NotNull;

class AllTest extends \PHPUnit\Framework\TestCase
{
    public function testGetTargets()
    {
        $constraint = new All(new NotNull());
        self::assertEquals('property', $constraint->getTargets());
    }

    public function testThatConstraintsPropertyIsSet()
    {
        $childConstraint = new NotNull();
        $constraint = new All($childConstraint);
        self::assertEquals([$childConstraint], $constraint->constraints);
    }

    public function testRequiredOptions()
    {
        $this->expectException(\Symfony\Component\Validator\Exception\MissingOptionsException::class);
        new All();
    }

    public function testRejectNonConstraints()
    {
        $this->expectException(\Symfony\Component\Validator\Exception\ConstraintDefinitionException::class);
        new All('test');
    }
}
