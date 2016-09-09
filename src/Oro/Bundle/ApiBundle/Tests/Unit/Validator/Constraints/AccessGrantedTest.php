<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Validator\Constraints;

use Symfony\Component\HttpFoundation\Response;

use Oro\Bundle\ApiBundle\Validator\Constraints\AccessGranted;

class AssociationTest extends \PHPUnit_Framework_TestCase
{
    public function testGetStatusCode()
    {
        $constraint = new AccessGranted();
        $this->assertEquals(Response::HTTP_FORBIDDEN, $constraint->getStatusCode());
    }

    public function testGetTargets()
    {
        $constraint = new AccessGranted();
        $this->assertEquals('property', $constraint->getTargets());
    }

    public function testValidatedBy()
    {
        $constraint = new AccessGranted();
        $this->assertEquals('oro_api.validator.access_granted', $constraint->validatedBy());
    }
}
