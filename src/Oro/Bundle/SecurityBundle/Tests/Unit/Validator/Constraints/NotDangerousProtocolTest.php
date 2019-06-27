<?php

namespace Oro\Bundle\SecurityBundle\Tests\Unit\Validator\Constraints;

use Oro\Bundle\SecurityBundle\Validator\Constraints\NotDangerousProtocol;

class NotDangerousProtocolTest extends \PHPUnit\Framework\TestCase
{
    public function testValidatedBy(): void
    {
        $constraint = new NotDangerousProtocol();
        $this->assertNotEmpty($constraint->message);
        $this->assertNotEmpty($constraint->validator);
        $this->assertEquals($constraint->validator, $constraint->validatedBy());
    }
}
