<?php

namespace Oro\Bundle\SSOBundle\Event;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\EmailBundle\Model\EmailHolderInterface;
use Oro\Bundle\SecurityBundle\Authentication\Token\UsernamePasswordOrganizationToken;
use Oro\Bundle\UserBundle\Exception\BadCredentialsException;
use Symfony\Component\Security\Core\Event\AuthenticationSuccessEvent;

/**
 * Disables log in for users with emails from SSO domains if this feature is enabled.
 */
class CheckPassportEventListener
{
    private ConfigManager $configManager;
    private string $enableSsoConfigKey;
    private string $domainsConfigKey;
    private string $ssoOnlyLoginConfigKey;
    private string $firewallName;

    public function __construct(
        ConfigManager $configManager,
        string $enableSsoConfigKey,
        string $domainsConfigKey,
        string $ssoOnlyLoginConfigKey,
        string $firewallName
    ) {
        $this->configManager = $configManager;
        $this->enableSsoConfigKey = $enableSsoConfigKey;
        $this->domainsConfigKey = $domainsConfigKey;
        $this->ssoOnlyLoginConfigKey = $ssoOnlyLoginConfigKey;
        $this->firewallName = $firewallName;
    }

    public function onAuthenticationSuccess(AuthenticationSuccessEvent $event): void
    {
        $token = $event->getAuthenticationToken();
        if (!$token instanceof UsernamePasswordOrganizationToken
            || !$token->getUser() instanceof EmailHolderInterface
            ||$token->getFirewallName() !== $this->firewallName
        ) {
            return;
        }

        if (!$this->configManager->get($this->enableSsoConfigKey)
            || !$this->configManager->get($this->ssoOnlyLoginConfigKey)
        ) {
            return;
        }

        $domains = (array)$this->configManager->get($this->domainsConfigKey);
        if (empty($domains)) {
            return;
        }

        $emailAddress = $token->getUser()->getEmail();
        if ($this->isConfiguredEmailDomain($emailAddress, $domains)) {
            $exception = new BadCredentialsException(sprintf(
                'Authentication failed; Given user with email "%s" should log in via SSO.',
                $emailAddress
            ));
            $exception->setMessageKey('oro_sso.sso_login.message');

            throw $exception;
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
