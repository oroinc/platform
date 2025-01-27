<?php

namespace Oro\Bundle\ApiBundle\ApiDoc;

use Oro\Bundle\SecurityBundle\Authentication\Token\AnonymousToken;
use Oro\Bundle\SecurityBundle\Authentication\Token\OrganizationAwareTokenInterface;
use Oro\Bundle\SecurityBundle\Csrf\CsrfRequestManager;
use Oro\Bundle\UserBundle\Entity\AbstractUser;
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

    #[\Override]
    public function hasSecurityToken(): bool
    {
        return null !== $this->tokenStorage->getToken();
    }

    #[\Override]
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

    #[\Override]
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

    #[\Override]
    public function getUserName(): ?string
    {
        $token = $this->tokenStorage->getToken();
        if (null === $token || $token instanceof AnonymousToken) {
            return null;
        }

        $user = $token->getUser();
        if (!$user instanceof UserInterface) {
            return null;
        }

        return $user->getUserIdentifier();
    }

    #[\Override]
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

    #[\Override]
    public function getSwitchOrganizationRoute(): ?string
    {
        return 'oro_security_switch_organization';
    }

    #[\Override]
    public function getLoginRoute(): ?string
    {
        return 'oro_user_security_login';
    }

    #[\Override]
    public function getLogoutRoute(): ?string
    {
        return 'oro_user_security_logout';
    }
}
