<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Validator\Constraints;

use Oro\Bundle\ApiBundle\Validator\Constraints\All;
use Symfony\Component\Validator\Constraints\NotNull;
use Symfony\Component\Validator\Exception\ConstraintDefinitionException;
use Symfony\Component\Validator\Exception\MissingOptionsException;

class AllTest extends \PHPUnit\Framework\TestCase
{
    public function testThatConstraintsPropertyIsSet()
    {
        $childConstraint = new NotNull();
        $constraint = new All($childConstraint);
        self::assertEquals([$childConstraint], $constraint->constraints);
    }

    public function testRequiredOptions()
    {
        $this->expectException(MissingOptionsException::class);
        new All();
    }

    public function testRejectNonConstraints()
    {
        $this->expectException(ConstraintDefinitionException::class);
        new All('test');
    }
}
