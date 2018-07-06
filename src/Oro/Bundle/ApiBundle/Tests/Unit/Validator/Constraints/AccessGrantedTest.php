<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Validator\Constraints;

use Oro\Bundle\ApiBundle\Validator\Constraints\AccessGranted;
use Symfony\Component\HttpFoundation\Response;

class AccessGrantedTest extends \PHPUnit\Framework\TestCase
{
    public function testGetStatusCode()
    {
        $constraint = new AccessGranted();
        self::assertEquals(Response::HTTP_FORBIDDEN, $constraint->getStatusCode());
    }

    public function testGetTargets()
    {
        $constraint = new AccessGranted();
        self::assertEquals('property', $constraint->getTargets());
    }

    public function testValidatedBy()
    {
        $constraint = new AccessGranted();
        self::assertEquals('oro_api.validator.access_granted', $constraint->validatedBy());
    }
}
