<?php

namespace Oro\Bundle\SecurityBundle\Http\Firewall;

use Oro\Bundle\FormBundle\Captcha\CaptchaServiceRegistry;
use Oro\Bundle\FormBundle\Validator\Constraints\IsCaptchaVerified;
use Oro\Bundle\SecurityBundle\Authentication\Passport\Badge\CaptchaBadge;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Http\Event\CheckPassportEvent;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Checks CAPTCHA validity during authorization.
 */
class CaptchaProtectionListener implements EventSubscriberInterface
{
    public function __construct(
        private CaptchaServiceRegistry $captchaServiceRegistry,
        private TranslatorInterface $translator
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [CheckPassportEvent::class => ['checkPassport', 512]];
    }

    public function checkPassport(CheckPassportEvent $event): void
    {
        $passport = $event->getPassport();
        if (!$passport->hasBadge(CaptchaBadge::class)) {
            return;
        }

        /** @var CaptchaBadge $badge */
        $badge = $passport->getBadge(CaptchaBadge::class);
        if ($badge->isResolved()) {
            return;
        }

        if (!$this->captchaServiceRegistry->getCaptchaService()->isVerified($badge->getToken())) {
            $captchaConstraint = new IsCaptchaVerified();

            throw new CustomUserMessageAuthenticationException(
                $this->translator->trans($captchaConstraint->message, [], 'validators')
            );
        }

        $badge->markResolved();
    }
}
