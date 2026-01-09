<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Validator\Constraints;

use Oro\Bundle\ApiBundle\Validator\Constraints\All;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Constraints\NotNull;
use Symfony\Component\Validator\Exception\ConstraintDefinitionException;

class AllTest extends TestCase
{
    public function testThatConstraintsPropertyIsSet(): void
    {
        $childConstraint = new NotNull();
        $constraint = new All($childConstraint);
        self::assertEquals([$childConstraint], $constraint->constraints);
    }

    public function testRequiredOptions(): void
    {
        $constraint = new All();
        self::assertEquals([], $constraint->constraints);
        self::assertEquals(['constraints'], $constraint->getRequiredOptions());
    }

    public function testRejectNonConstraints(): void
    {
        $this->expectException(ConstraintDefinitionException::class);
        new All('test');
    }
}
