<?php

namespace Oro\Bundle\UserBundle\Provider;

use Oro\Bundle\ScopeBundle\Manager\ScopeCriteriaProviderInterface;
use Oro\Bundle\UserBundle\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

/**
 * The scope criteria provider for the current user.
 */
class ScopeUserCriteriaProvider implements ScopeCriteriaProviderInterface
{
    public const USER = 'user';

    /** @var TokenStorageInterface */
    private $tokenStorage;

    public function __construct(TokenStorageInterface $tokenStorage)
    {
        $this->tokenStorage = $tokenStorage;
    }

    /**
     * {@inheritdoc}
     */
    public function getCriteriaField()
    {
        return self::USER;
    }

    /**
     * {@inheritdoc}
     */
    public function getCriteriaValue()
    {
        $token = $this->tokenStorage->getToken();
        if (null !== $token) {
            $user = $token->getUser();
            if ($user instanceof User) {
                return $user;
            }
        }

        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function getCriteriaValueType()
    {
        return User::class;
    }
}
