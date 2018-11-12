<?php

namespace Oro\Bundle\OrganizationBundle\Tests\Unit\Validator\Constraints;

use Oro\Bundle\OrganizationBundle\Validator\Constraints\ParentBusinessUnit;

class ParentBusinessUnitTest extends \PHPUnit\Framework\TestCase
{
    /** @var ParentBusinessUnit */
    protected $constraint;

    protected function setUp()
    {
        $this->constraint = new ParentBusinessUnit();
    }

    public function testMessage()
    {
        $this->assertEquals(
            'Business Unit cannot have a child as a Parent Business Unit.',
            $this->constraint->message
        );
    }

    public function testValidatedBy()
    {
        $this->assertEquals('parent_business_unit_validator', $this->constraint->validatedBy());
    }

    public function testGetTargets()
    {
        $this->assertEquals('class', $this->constraint->getTargets());
    }
}
