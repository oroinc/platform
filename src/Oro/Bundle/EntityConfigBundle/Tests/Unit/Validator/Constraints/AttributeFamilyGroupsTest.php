<?php

namespace Oro\Bundle\EntityConfigBundle\Tests\Unit\Validator\Constraints;

use Oro\Bundle\EntityConfigBundle\Validator\Constraints\AttributeFamilyGroups;
use Oro\Bundle\EntityConfigBundle\Validator\Constraints\AttributeFamilyGroupsValidator;

class AttributeFamilyGroupsTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var AttributeFamilyGroups
     */
    protected $constraint;
    
    protected function setUp()
    {
        $this->constraint = new AttributeFamilyGroups();
    }
    
    public function testGetTargets()
    {
        $this->assertEquals('class', $this->constraint->getTargets());
    }
    
    public function testValidatedBy()
    {
        $this->assertEquals(AttributeFamilyGroupsValidator::ALIAS, $this->constraint->validatedBy());
    }
}
