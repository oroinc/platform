<?php

namespace Oro\Bundle\SecurityBundle\Authentication;

use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\SecurityBundle\Authentication\Token\OrganizationContextTokenInterface;
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
    public function getToken()
    {
        return $this->tokenStorage->getToken();
    }

    /**
     * {@inheritdoc}
     */
    public function setToken(TokenInterface $token = null)
    {
        $this->tokenStorage->setToken($token);
    }

    /**
     * {@inheritdoc}
     */
    public function hasUser()
    {
        return null !== $this->getUser();
    }

    /**
     * {@inheritdoc}
     */
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

    /**
     * {@inheritdoc}
     */
    public function getUserId()
    {
        $user = $this->getUser();
        if (null === $user) {
            return null;
        }

        return $user->getId();
    }

    /**
     * {@inheritdoc}
     */
    public function getOrganization()
    {
        $result = null;
        $token = $this->tokenStorage->getToken();
        if ($token instanceof OrganizationContextTokenInterface) {
            $organization = $token->getOrganizationContext();
            if ($organization instanceof Organization) {
                $result = $organization;
            }
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function getOrganizationId()
    {
        $organization = $this->getOrganization();
        if (null === $organization) {
            return null;
        }

        return $organization->getId();
    }
}
