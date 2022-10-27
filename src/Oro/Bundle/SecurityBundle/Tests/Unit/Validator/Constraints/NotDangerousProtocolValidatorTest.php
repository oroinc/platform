<?php

namespace Oro\Bundle\SecurityBundle\Tests\Unit\Validator\Constraints;

use Oro\Bundle\SecurityBundle\Util\UriSecurityHelper;
use Oro\Bundle\SecurityBundle\Validator\Constraints\NotDangerousProtocol;
use Oro\Bundle\SecurityBundle\Validator\Constraints\NotDangerousProtocolValidator;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

class NotDangerousProtocolValidatorTest extends ConstraintValidatorTestCase
{
    /** @var UriSecurityHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $uriSecurityHelper;

    protected function setUp(): void
    {
        $this->uriSecurityHelper = $this->createMock(UriSecurityHelper::class);
        parent::setUp();
    }

    protected function createValidator()
    {
        return new NotDangerousProtocolValidator($this->uriSecurityHelper);
    }

    public function testValidateWhenSafeProtocol(): void
    {
        $this->uriSecurityHelper->expects($this->once())
            ->method('uriHasDangerousProtocol')
            ->with($value = 'safe-proto:sample-data')
            ->willReturn(false);

        $constraint = new NotDangerousProtocol();
        $this->validator->validate($value, $constraint);

        $this->assertNoViolation();
    }

    public function testValidateWhenDangerousProtocol(): void
    {
        $constraint = new NotDangerousProtocol();

        $this->uriSecurityHelper->expects($this->once())
            ->method('uriHasDangerousProtocol')
            ->with($value = 'sample-proto3:sample-data')
            ->willReturn(true);
        $this->uriSecurityHelper->expects($this->once())
            ->method('getAllowedProtocols')
            ->willReturn(['sample-proto1', 'sample-proto2']);

        $this->validator->validate($value, $constraint);

        $this->buildViolation($constraint->message)
            ->setParameters([
                '{{ allowed }}'  => 'sample-proto1, sample-proto2',
                '{{ protocol }}' => 'sample-proto3'
            ])
            ->assertRaised();
    }
}
