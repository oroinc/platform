<?php

namespace Oro\Bundle\SSOBundle\Security\Core\User;

use HWI\Bundle\OAuthBundle\OAuth\Response\UserResponseInterface;
use HWI\Bundle\OAuthBundle\Security\Core\User\OAuthAwareUserProviderInterface;
use Oro\Bundle\SSOBundle\Security\Core\Exception\EmailDomainNotAllowedException;
use Oro\Bundle\SSOBundle\Security\Core\Exception\ResourceOwnerNotAllowedException;
use Psr\Container\ContainerInterface;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;
use Symfony\Component\Security\Core\Exception\DisabledException;

/**
 * The user provider for OAuth single sign-on authentication.
 */
class OAuthUserProvider implements OAuthAwareUserProviderInterface
{
    /** @var ContainerInterface */
    private $userProviders;

    /**
     * @param ContainerInterface $userProviders
     */
    public function __construct(ContainerInterface $userProviders)
    {
        $this->userProviders = $userProviders;
    }

    /**
     * {@inheritdoc}
     */
    public function loadUserByOAuthUserResponse(UserResponseInterface $response)
    {
        $username = $response->getUsername();
        if (!$username) {
            throw new BadCredentialsException('The user name is not specified.');
        }

        $userProvider = $this->getUserProvider($response->getResourceOwner()->getName());

        $allowedDomains = $userProvider->getAllowedDomains();
        if ($allowedDomains && !$this->isEmailAllowed($response->getEmail(), $allowedDomains)) {
            throw new EmailDomainNotAllowedException('The user email is not allowed.');
        }

        $user = $userProvider->findUser($response);
        if (null === $user) {
            throw new DisabledException('The user does not exist.');
        }
        if (!$user->isEnabled()) {
            throw new DisabledException('The user is disabled.');
        }

        return $user;
    }

    /**
     * @param string $resourceOwner
     *
     * @return OAuthUserProviderInterface
     */
    private function getUserProvider(string $resourceOwner): OAuthUserProviderInterface
    {
        if (!$this->userProviders->has($resourceOwner)) {
            throw new ResourceOwnerNotAllowedException('SSO is not supported.');
        }
        $userProvider = $this->userProviders->get($resourceOwner);
        if (!$userProvider->isEnabled()) {
            throw new ResourceOwnerNotAllowedException('SSO is not enabled.');
        }

        return $userProvider;
    }

    /**
     * @param string   $email
     * @param string[] $allowedDomains
     *
     * @return bool
     */
    private function isEmailAllowed(string $email, array $allowedDomains): bool
    {
        foreach ($allowedDomains as $domain) {
            if (preg_match(sprintf('/@%s$/', $domain), $email)) {
                return true;
            }
        }

        return false;
    }
}
