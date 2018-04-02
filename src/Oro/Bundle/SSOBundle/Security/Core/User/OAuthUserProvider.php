<?php

namespace Oro\Bundle\SSOBundle\Security\Core\User;

use HWI\Bundle\OAuthBundle\OAuth\Response\UserResponseInterface;
use HWI\Bundle\OAuthBundle\Security\Core\User\OAuthAwareUserProviderInterface;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\SSOBundle\Security\Core\Exception\EmailDomainNotAllowedException;
use Oro\Bundle\UserBundle\Entity\UserManager;
use RuntimeException;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;

/**
 * OAuth user provider
 */
class OAuthUserProvider implements OAuthAwareUserProviderInterface
{
    /**
     * @var ConfigManager
     */
    protected $cm;

    /**
     * @var UserManager
     */
    protected $userManager;

    /**
     * Constructor
     *
     * @param UserManager   $userManager
     * @param ConfigManager $cm
     */
    public function __construct(UserManager $userManager, ConfigManager $cm)
    {
        $this->userManager = $userManager;
        $this->cm          = $cm;
    }

    /**
     * {@inheritdoc}
     */
    public function loadUserByOAuthUserResponse(UserResponseInterface $response)
    {
        if (!$this->cm->get('oro_sso.enable_google_sso')) {
            throw new \Exception('SSO is not enabled');
        }

        $username = $response->getUsername();
        if ($username === null) {
            throw new BadCredentialsException('Bad credentials.');
        }

        if (!$this->isEmailEnabledForOauth($response->getEmail())) {
            throw new EmailDomainNotAllowedException('Bad credentials.');
        }

        $user = $this->userManager->findUserBy([$this->getOAuthProperty($response) => $username]);

        if (!$user) {
            $user = $this->userManager->findUserByEmail($response->getEmail());
            if ($user) {
                $user->setGoogleId($username);
                $this->userManager->updateUser($user);
            }
        }

        if (!$user || !$user->isEnabled()) {
            throw new BadCredentialsException('Bad credentials.');
        }

        return $user;
    }

    /**
     * Returns if given email can be used for oauth
     *
     * @param string $email
     *
     * @return boolean
     */
    protected function isEmailEnabledForOauth($email)
    {
        $enabledDomains = $this->cm->get('oro_sso.domains');
        if (!$enabledDomains) {
            return true;
        }

        foreach ($enabledDomains as $enabledDomain) {
            if (preg_match(sprintf('/@%s$/', $enabledDomain), $email)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Gets the property for the response.
     *
     * @param UserResponseInterface $response
     *
     * @return string
     *
     * @throws RuntimeException
     */
    protected function getOAuthProperty(UserResponseInterface $response)
    {
        $resourceOwnerName = $response->getResourceOwner()->getName();

        return sprintf('%sId', $resourceOwnerName);
    }
}
