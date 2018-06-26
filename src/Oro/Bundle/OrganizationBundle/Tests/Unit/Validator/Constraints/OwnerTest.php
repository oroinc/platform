<?php

namespace Oro\Bundle\OrganizationBundle\Tests\Unit\Validator\Constraints;

use Oro\Bundle\OrganizationBundle\Validator\Constraints\Owner;

class OwnerTest extends \PHPUnit\Framework\TestCase
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
            'You have no access to set this value as {{ owner }}.',
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
