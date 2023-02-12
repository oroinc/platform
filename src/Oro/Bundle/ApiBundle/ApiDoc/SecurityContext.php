<?php

namespace Oro\Bundle\ApiBundle\ApiDoc;

use Oro\Bundle\SecurityBundle\Authentication\Token\OrganizationAwareTokenInterface;
use Oro\Bundle\SecurityBundle\Csrf\CsrfRequestManager;
use Oro\Bundle\UserBundle\Entity\AbstractUser;
use Oro\Bundle\UserBundle\Security\AdvancedApiUserInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * The default implementation of the security context for API sandbox.
 */
class SecurityContext implements SecurityContextInterface
{
    private TokenStorageInterface $tokenStorage;
    private ?RequestStack $requestStack;

    public function __construct(TokenStorageInterface $tokenStorage, RequestStack $requestStack = null)
    {
        $this->tokenStorage = $tokenStorage;
        $this->requestStack = $requestStack;
    }

    /**
     * {@inheritDoc}
     */
    public function hasSecurityToken(): bool
    {
        return null !== $this->tokenStorage->getToken();
    }

    /**
     * {@inheritDoc}
     */
    public function getOrganizations(): array
    {
        $token = $this->tokenStorage->getToken();
        if (!$token instanceof OrganizationAwareTokenInterface) {
            return [];
        }

        $user = $token->getUser();
        if (!$user instanceof AbstractUser) {
            return [];
        }

        $result = [];
        $organizations = $user->getOrganizations(true);
        foreach ($organizations as $organization) {
            $result[(string)$organization->getId()] = $organization->getName();
        }

        return $result;
    }

    /**
     * {@inheritDoc}
     */
    public function getOrganization(): ?string
    {
        $token = $this->tokenStorage->getToken();
        if (!$token instanceof OrganizationAwareTokenInterface) {
            return null;
        }

        $organization = $token->getOrganization();
        if (null === $organization) {
            return null;
        }

        return (string)$organization->getId();
    }

    /**
     * {@inheritDoc}
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
     * {@inheritDoc}
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

        if ($token instanceof OrganizationAwareTokenInterface) {
            $organization = $token->getOrganization();
            foreach ($apiKeyKeys as $apiKeyKey) {
                if ($apiKeyKey->getOrganization()->getId() === $organization->getId()) {
                    return $apiKeyKey->getApiKey();
                }
            }
        }

        return null;
    }

    /**
     * {@inheritDoc}
     */
    public function getApiKeyGenerationHint(): ?string
    {
        return
            'To use WSSE authentication you need to generate API key for the current logged-in user.'
            . ' To do this, go to the My User page and click Generate Key near to API Key.'
            . ' After that reload this page.';
    }

    /**
     * {@inheritDoc}
     */
    public function getCsrfCookieName(): ?string
    {
        if (null === $this->requestStack) {
            return null;
        }

        $request = $this->requestStack->getMainRequest();
        if (null === $request) {
            return null;
        }

        return $request->isSecure()
            ? 'https-' . CsrfRequestManager::CSRF_TOKEN_ID
            : CsrfRequestManager::CSRF_TOKEN_ID;
    }

    /**
     * {@inheritDoc}
     */
    public function getSwitchOrganizationRoute(): ?string
    {
        return 'oro_security_switch_organization';
    }

    /**
     * {@inheritDoc}
     */
    public function getLoginRoute(): ?string
    {
        return 'oro_user_security_login';
    }

    /**
     * {@inheritDoc}
     */
    public function getLogoutRoute(): ?string
    {
        return 'oro_user_security_logout';
    }
}
