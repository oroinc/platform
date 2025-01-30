<?php

namespace Oro\Bundle\SecurityBundle\Authentication;

use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\SecurityBundle\Authentication\Token\OrganizationAwareTokenInterface;
use Oro\Bundle\UserBundle\Entity\AbstractUser;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

/**
 * Default implementation of TokenAccessorInterface.
 */
class TokenAccessor implements TokenAccessorInterface
{
    /** @var TokenStorageInterface */
    private $tokenStorage;

    public function __construct(TokenStorageInterface $tokenStorage)
    {
        $this->tokenStorage = $tokenStorage;
    }

    #[\Override]
    public function getToken(): ?TokenInterface
    {
        return $this->tokenStorage->getToken();
    }

    #[\Override]
    public function setToken(?TokenInterface $token = null)
    {
        $this->tokenStorage->setToken($token);
    }

    #[\Override]
    public function hasUser(): bool
    {
        return null !== $this->getUser();
    }

    #[\Override]
    public function getUser()
    {
        $result = null;
        $token = $this->tokenStorage->getToken();
        if ($token instanceof TokenInterface) {
            $user = $token->getUser();
            if ($user instanceof AbstractUser) {
                $result = $user;
            }
        }

        return $result;
    }

    #[\Override]
    public function getUserId()
    {
        $user = $this->getUser();

        return $user?->getId();
    }

    #[\Override]
    public function getOrganization()
    {
        $result = null;
        $token = $this->tokenStorage->getToken();
        if ($token instanceof OrganizationAwareTokenInterface) {
            $organization = $token->getOrganization();
            if ($organization instanceof Organization) {
                $result = $organization;
            }
        }

        return $result;
    }

    #[\Override]
    public function getOrganizationId()
    {
        $organization = $this->getOrganization();

        return $organization?->getId();
    }
}
