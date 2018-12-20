<?php

namespace Oro\Bundle\OrganizationBundle\Tests\Unit\Validator\Constraints;

use Oro\Bundle\OrganizationBundle\Validator\Constraints\BusinessUnitOwner;

class BusinessUnitOwnerTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var BusinessUnitOwner
     */
    protected $businessUnitOwner;

    protected function setUp()
    {
        $this->businessUnitOwner = new BusinessUnitOwner();
    }

    public function testMessage()
    {
        $this->assertEquals("Business Unit can't set self as Parent.", $this->businessUnitOwner->message);
    }

    public function getTargetsTest()
    {
        $this->assertEquals(BusinessUnitOwner::CLASS_CONSTRAINT, $this->businessUnitOwner->getTargets());
    }
}
