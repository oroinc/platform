<?php

namespace Oro\Bundle\SecurityBundle\Tests\Unit\Validator\Constraints;

use Oro\Bundle\SecurityBundle\Util\UriSecurityHelper;
use Oro\Bundle\SecurityBundle\Validator\Constraints\NotDangerousProtocol;
use Oro\Bundle\SecurityBundle\Validator\Constraints\NotDangerousProtocolValidator;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Violation\ConstraintViolationBuilderInterface;

class NotDangerousProtocolValidatorTest extends \PHPUnit\Framework\TestCase
{
    /** @var ExecutionContextInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $context;

    /** @var UriSecurityHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $uriSecurityHelper;

    /** @var NotDangerousProtocolValidator */
    private $validator;

    protected function setUp(): void
    {
        $this->uriSecurityHelper = $this->createMock(UriSecurityHelper::class);
        $this->validator = new NotDangerousProtocolValidator($this->uriSecurityHelper);
        $this->validator->initialize($this->context = $this->createMock(ExecutionContextInterface::class));
    }

    public function testValidateWhenSafeProtocol(): void
    {
        $this->context
            ->expects($this->never())
            ->method($this->anything());

        $this->uriSecurityHelper
            ->expects($this->once())
            ->method('uriHasDangerousProtocol')
            ->with($value = 'safe-proto:sample-data')
            ->willReturn(false);

        $constraint = new NotDangerousProtocol();
        $this->validator->validate($value, $constraint);
    }

    public function testValidateWhenDangerousProtocol(): void
    {
        $constraint = new NotDangerousProtocol();

        $this->uriSecurityHelper
            ->expects($this->once())
            ->method('uriHasDangerousProtocol')
            ->with($value = 'sample-proto3:sample-data')
            ->willReturn(true);

        $this->uriSecurityHelper
            ->expects($this->once())
            ->method('getAllowedProtocols')
            ->willReturn(['sample-proto1', 'sample-proto2']);

        $this->context
            ->expects($this->once())
            ->method('buildViolation')
            ->with($constraint->message)
            ->willReturn($violationBuilder = $this->createMock(ConstraintViolationBuilderInterface::class));

        $violationBuilder
            ->expects($this->once())
            ->method('setParameters')
            ->with([
                '{{ allowed }}' => 'sample-proto1, sample-proto2',
                '{{ protocol }}' => 'sample-proto3',
            ])
            ->willReturnSelf();

        $violationBuilder
            ->expects($this->once())
            ->method('addViolation');

        $this->validator->validate($value, $constraint);
    }
}
