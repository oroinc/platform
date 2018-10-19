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

    /**
     * @expectedException \Symfony\Component\Validator\Exception\MissingOptionsException
     */
    public function testRequiredOptions()
    {
        new All();
    }

    /**
     * @expectedException \Symfony\Component\Validator\Exception\ConstraintDefinitionException
     */
    public function testRejectNonConstraints()
    {
        new All('test');
    }
}
