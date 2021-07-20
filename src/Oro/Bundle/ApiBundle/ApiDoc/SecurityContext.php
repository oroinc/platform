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
    /** @var TokenStorageInterface */
    private $tokenStorage;

    /** @var RequestStack|null */
    private $requestStack;

    public function __construct(TokenStorageInterface $tokenStorage, RequestStack $requestStack = null)
    {
        $this->tokenStorage = $tokenStorage;
        $this->requestStack = $requestStack;
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
     * {@inheritdoc}
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
    public function getCsrfCookieName(): ?string
    {
        if (null === $this->requestStack) {
            return null;
        }

        $request = $this->requestStack->getMasterRequest();
        if (null === $request) {
            return null;
        }

        return $request->isSecure()
            ? 'https-' . CsrfRequestManager::CSRF_TOKEN_ID
            : CsrfRequestManager::CSRF_TOKEN_ID;
    }

    /**
     * {@inheritdoc}
     */
    public function getSwitchOrganizationRoute(): ?string
    {
        return 'oro_security_switch_organization';
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
