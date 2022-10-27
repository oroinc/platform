<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Validator\Constraints;

use Oro\Bundle\ApiBundle\Validator\Constraints\AccessGranted;
use Symfony\Component\HttpFoundation\Response;

class AccessGrantedTest extends \PHPUnit\Framework\TestCase
{
    public function testGetDefaultPermission(): void
    {
        $constraint = new AccessGranted();
        self::assertEquals('VIEW', $constraint->permission);
    }

    public function testGetPermission(): void
    {
        $permission = 'EDIT';
        $constraint = new AccessGranted(['permission' => $permission]);
        self::assertEquals($permission, $constraint->permission);
    }

    public function testGetStatusCode(): void
    {
        $constraint = new AccessGranted();
        self::assertEquals(Response::HTTP_FORBIDDEN, $constraint->getStatusCode());
    }
}
