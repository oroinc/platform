<?php

namespace Oro\Bundle\OrganizationBundle\Tests\Unit\Validator\Constrains;

use Oro\Bundle\OrganizationBundle\Validator\Constraints\Owner;

class OwnerTest extends \PHPUnit_Framework_TestCase
{
    /** @var Owner */
    protected $owner;

    protected function setUp()
    {
        $this->owner = new Owner();
    }

    public function testMessage()
    {
        $this->assertEquals(
            'The given value "%value%" cannot be set as "%owner%" for given entity for security reason.',
            $this->owner->message
        );
    }

    public function testValidatedBy()
    {
        $this->assertEquals('owner_validator', $this->owner->validatedBy());
    }

    public function testGetTargets()
    {
        $this->assertEquals('class', $this->owner->getTargets());
    }
}
