<?php

namespace Oro\Bundle\SecurityBundle\Tests\Unit\Http\Firewall;

use Oro\Bundle\FormBundle\Captcha\CaptchaServiceInterface;
use Oro\Bundle\FormBundle\Captcha\CaptchaServiceRegistry;
use Oro\Bundle\FormBundle\Validator\Constraints\IsCaptchaVerified;
use Oro\Bundle\SecurityBundle\Authentication\Passport\Badge\CaptchaBadge;
use Oro\Bundle\SecurityBundle\Http\Firewall\CaptchaProtectionListener;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Event\CheckPassportEvent;
use Symfony\Contracts\Translation\TranslatorInterface;

class CaptchaProtectionListenerTest extends TestCase
{
    private CaptchaServiceRegistry|MockObject $captchaServiceRegistry;
    private TranslatorInterface|MockObject $translator;
    private CaptchaProtectionListener $listener;

    protected function setUp(): void
    {
        $this->captchaServiceRegistry = $this->createMock(CaptchaServiceRegistry::class);
        $this->translator = $this->createMock(TranslatorInterface::class);
        $this->listener = new CaptchaProtectionListener($this->captchaServiceRegistry, $this->translator);
    }

    public function testCheckPassportWithoutCaptchaBadge(): void
    {
        $passport = $this->createMock(Passport::class);
        $passport->expects($this->once())
            ->method('hasBadge')
            ->with(CaptchaBadge::class)
            ->willReturn(false);

        $event = $this->createMock(CheckPassportEvent::class);
        $event->expects($this->once())
            ->method('getPassport')
            ->willReturn($passport);

        $this->captchaServiceRegistry->expects($this->never())
            ->method('getCaptchaService');

        $this->listener->checkPassport($event);
    }

    public function testCheckPassportWithResolvedBadge(): void
    {
        $badge = $this->createMock(CaptchaBadge::class);
        $badge->expects($this->once())
            ->method('isResolved')
            ->willReturn(true);

        $passport = $this->createMock(Passport::class);
        $passport->expects($this->once())
            ->method('hasBadge')
            ->with(CaptchaBadge::class)
            ->willReturn(true);

        $passport->expects($this->once())
            ->method('getBadge')
            ->with(CaptchaBadge::class)
            ->willReturn($badge);

        $event = $this->createMock(CheckPassportEvent::class);
        $event->expects($this->once())
            ->method('getPassport')
            ->willReturn($passport);

        $this->captchaServiceRegistry->expects($this->never())
            ->method('getCaptchaService');

        $this->listener->checkPassport($event);
    }

    public function testCheckPassportWithInvalidCaptcha(): void
    {
        $badge = $this->createMock(CaptchaBadge::class);
        $badge->expects($this->once())
            ->method('isResolved')
            ->willReturn(false);

        $badge->expects($this->once())
            ->method('getToken')
            ->willReturn('invalid-token');

        $captchaService = $this->createMock(CaptchaServiceInterface::class);
        $captchaService->expects($this->once())
            ->method('isVerified')
            ->with('invalid-token')
            ->willReturn(false);

        $this->captchaServiceRegistry->expects($this->once())
            ->method('getCaptchaService')
            ->willReturn($captchaService);

        $captchaConstraint = new IsCaptchaVerified();
        $this->translator->expects($this->once())
            ->method('trans')
            ->with($captchaConstraint->message, [], 'validators')
            ->willReturn('Translated captcha error message');

        $this->expectException(CustomUserMessageAuthenticationException::class);
        $this->expectExceptionMessage('Translated captcha error message');

        $passport = $this->createMock(Passport::class);
        $passport->expects($this->once())
            ->method('hasBadge')
            ->with(CaptchaBadge::class)
            ->willReturn(true);

        $passport->expects($this->once())
            ->method('getBadge')
            ->with(CaptchaBadge::class)
            ->willReturn($badge);

        $event = $this->createMock(CheckPassportEvent::class);
        $event->expects($this->once())
            ->method('getPassport')
            ->willReturn($passport);

        $this->listener->checkPassport($event);
    }

    public function testCheckPassportWithValidCaptcha(): void
    {
        $badge = $this->createMock(CaptchaBadge::class);
        $badge->expects($this->once())
            ->method('isResolved')
            ->willReturn(false);

        $badge->expects($this->once())
            ->method('getToken')
            ->willReturn('valid-token');

        $captchaService = $this->createMock(CaptchaServiceInterface::class);
        $captchaService->expects($this->once())
            ->method('isVerified')
            ->with('valid-token')
            ->willReturn(true);

        $this->captchaServiceRegistry->expects($this->once())
            ->method('getCaptchaService')
            ->willReturn($captchaService);

        $badge->expects($this->once())
            ->method('markResolved');

        $passport = $this->createMock(Passport::class);
        $passport->expects($this->once())
            ->method('hasBadge')
            ->with(CaptchaBadge::class)
            ->willReturn(true);

        $passport->expects($this->once())
            ->method('getBadge')
            ->with(CaptchaBadge::class)
            ->willReturn($badge);

        $event = $this->createMock(CheckPassportEvent::class);
        $event->expects($this->once())
            ->method('getPassport')
            ->willReturn($passport);

        $this->listener->checkPassport($event);
    }
}
