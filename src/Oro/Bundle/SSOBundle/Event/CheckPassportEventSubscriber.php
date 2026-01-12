<?php

namespace Oro\Bundle\SSOBundle\Event;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\EmailBundle\Model\EmailHolderInterface;
use Oro\Bundle\SecurityBundle\Authentication\Authenticator\UsernamePasswordOrganizationAuthenticator;
use Oro\Bundle\UserBundle\Exception\BadCredentialsException;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Security\Http\Authenticator\AbstractLoginFormAuthenticator;
use Symfony\Component\Security\Http\Event\CheckPassportEvent;

/**
 * Disables log in for users with emails from SSO domains if this feature is enabled.
 */
class CheckPassportEventSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly ConfigManager $configManager,
        private readonly string $enableSsoConfigKey,
        private readonly string $domainsConfigKey,
        private readonly string $ssoOnlyLoginConfigKey,
        private readonly string $firewallName
    ) {
    }

    #[\Override]
    public static function getSubscribedEvents(): array
    {
        return [CheckPassportEvent::class => 'onCheckPassport'];
    }

    public function onCheckPassport(CheckPassportEvent $event): void
    {
        if (
            !$event->getAuthenticator() instanceof AbstractLoginFormAuthenticator
            || !$event->getPassport()->getUser() instanceof EmailHolderInterface
            || ($event->getAuthenticator() instanceof UsernamePasswordOrganizationAuthenticator
                && $event->getAuthenticator()->getFirewallName() !== $this->firewallName)
        ) {
            return;
        }

        if (
            !$this->configManager->get($this->enableSsoConfigKey)
            || !$this->configManager->get($this->ssoOnlyLoginConfigKey)
        ) {
            return;
        }

        $domains = (array)$this->configManager->get($this->domainsConfigKey);
        if (empty($domains)) {
            return;
        }

        $emailAddress = $event->getPassport()->getUser()->getEmail();
        if ($this->isConfiguredEmailDomain($emailAddress, $domains)) {
            $exeption = new BadCredentialsException(sprintf(
                'Authentication failed; Given user with email "%s" should log in via SSO.',
                $emailAddress
            ));
            $exeption->setMessageKey('oro_sso.sso_login.message');

            throw $exeption;
        }
    }

    private function isConfiguredEmailDomain(string $email, array $domains): bool
    {
        foreach ($domains as $domain) {
            if (preg_match(sprintf('/@%s$/', $domain), $email)) {
                return true;
            }
        }

        return false;
    }
}
