<?php

namespace Oro\Bundle\EntityConfigBundle\Tests\Unit\Validator\Constraints;

use Oro\Bundle\EntityConfigBundle\Validator\Constraints\GroupAttributes;
use Oro\Bundle\EntityConfigBundle\Validator\Constraints\GroupAttributesValidator;

class GroupAttributesTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var GroupAttributes
     */
    protected $constraint;

    protected function setUp()
    {
        $this->constraint = new GroupAttributes();
    }

    public function testGetTargets()
    {
        $this->assertEquals('class', $this->constraint->getTargets());
    }

    public function testValidatedBy()
    {
        $this->assertEquals(GroupAttributesValidator::ALIAS, $this->constraint->validatedBy());
    }
}
