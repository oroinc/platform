<?php

namespace Oro\Bundle\FormBundle\Tests\Unit\Validator\Constraints;

use Oro\Bundle\FormBundle\Captcha\CaptchaServiceInterface;
use Oro\Bundle\FormBundle\Captcha\CaptchaServiceRegistry;
use Oro\Bundle\FormBundle\Validator\Constraints\IsCaptchaVerified;
use Oro\Bundle\FormBundle\Validator\Constraints\IsCaptchaVerifiedValidator;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

class IsCaptchaVerifiedValidatorTest extends ConstraintValidatorTestCase
{
    private CaptchaServiceRegistry&MockObject $captchaServiceRegistry;

    #[\Override]
    protected function createValidator()
    {
        $this->captchaServiceRegistry = $this->createMock(CaptchaServiceRegistry::class);

        return new IsCaptchaVerifiedValidator(
            $this->captchaServiceRegistry
        );
    }

    public function testValidateValid(): void
    {
        $captchaService = $this->createMock(CaptchaServiceInterface::class);
        $captchaService->expects($this->once())
            ->method('isVerified')
            ->with('test')
            ->willReturn(true);

        $this->captchaServiceRegistry->expects($this->once())
            ->method('getCaptchaService')
            ->willReturn($captchaService);

        $constraint = new IsCaptchaVerified();
        $this->validator->validate('test', $constraint);

        $this->assertNoViolation();
    }

    public function testValidateInvalid(): void
    {
        $captchaService = $this->createMock(CaptchaServiceInterface::class);
        $captchaService->expects($this->once())
            ->method('isVerified')
            ->with('test')
            ->willReturn(false);

        $this->captchaServiceRegistry->expects($this->once())
            ->method('getCaptchaService')
            ->willReturn($captchaService);

        $constraint = new IsCaptchaVerified();
        $this->validator->validate('test', $constraint);

        $this->buildViolation($constraint->message)
            ->assertRaised();
    }
}
