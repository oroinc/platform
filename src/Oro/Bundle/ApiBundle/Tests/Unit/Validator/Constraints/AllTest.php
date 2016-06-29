<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Validator\Constraints;

use Symfony\Component\Validator\Constraints\NotNull;

use Oro\Bundle\ApiBundle\Validator\Constraints\All;

class AllTest extends \PHPUnit_Framework_TestCase
{
    public function testGetTargets()
    {
        $constraint = new All(new NotNull());
        $this->assertEquals('property', $constraint->getTargets());
    }

    public function testThatConstraintsPropertyIsSet()
    {
        $childConstraint = new NotNull();
        $constraint = new All($childConstraint);
        $this->assertEquals([$childConstraint], $constraint->constraints);
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
