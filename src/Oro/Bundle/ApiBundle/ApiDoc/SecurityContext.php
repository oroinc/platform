<?php

namespace Oro\Bundle\ApiBundle\ApiDoc;

use Oro\Bundle\UserBundle\Security\AdvancedApiUserInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * The default implementation of the security context for API sandbox.
 */
class SecurityContext implements SecurityContextInterface
{
    /** @var TokenStorageInterface */
    private $tokenStorage;

    /**
     * @param TokenStorageInterface $tokenStorage
     */
    public function __construct(TokenStorageInterface $tokenStorage)
    {
        $this->tokenStorage = $tokenStorage;
    }

    /**
     * {@inheritdoc}
     */
    public function hasSecurityToken(): bool
    {
        return null !== $this->tokenStorage->getToken();
    }

    /**
     * {@inheritdoc}
     */
    public function getUserName(): ?string
    {
        $token = $this->tokenStorage->getToken();
        if (null === $token) {
            return null;
        }

        $user = $token->getUser();
        if (!$user instanceof UserInterface) {
            return null;
        }

        return $user->getUsername();
    }

    /**
     * {@inheritdoc}
     */
    public function getApiKey(): ?string
    {
        $token = $this->tokenStorage->getToken();
        if (null === $token) {
            return null;
        }

        $user = $token->getUser();
        if (!$user instanceof AdvancedApiUserInterface) {
            return null;
        }

        $apiKeyKeys = $user->getApiKeys();
        if ($apiKeyKeys->isEmpty()) {
            return null;
        }

        return $apiKeyKeys->first()->getApiKey();
    }

    /**
     * {@inheritdoc}
     */
    public function getApiKeyGenerationHint(): ?string
    {
        return
            'To use WSSE authentication you need to generate API key for the current logged-in user.'
            . ' To do this, go to the My User page and click Generate Key near to API Key.'
            . ' After that reload this page.';
    }

    /**
     * {@inheritdoc}
     */
    public function getLoginRoute(): ?string
    {
        return 'oro_user_security_login';
    }

    /**
     * {@inheritdoc}
     */
    public function getLogoutRoute(): ?string
    {
        return 'oro_user_security_logout';
    }
}
