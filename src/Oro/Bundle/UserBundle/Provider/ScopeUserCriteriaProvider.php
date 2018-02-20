<?php

namespace Oro\Bundle\UserBundle\Provider;

use Oro\Bundle\ScopeBundle\Manager\AbstractScopeCriteriaProvider;
use Oro\Bundle\UserBundle\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class ScopeUserCriteriaProvider extends AbstractScopeCriteriaProvider
{
    const SCOPE_KEY = 'user';

    /** @var TokenStorageInterface */
    protected $tokenStorage;

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
    public function getCriteriaForCurrentScope()
    {
        $token = $this->tokenStorage->getToken();
        if (!$token) {
            return [];
        }

        $loggedUser = $token->getUser();
        if ($loggedUser instanceof User) {
            return [self::SCOPE_KEY => $loggedUser];
        }

        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function getCriteriaField()
    {
        return static::SCOPE_KEY;
    }

    /**
     * {@inheritdoc}
     */
    public function getCriteriaValueType()
    {
        return User::class;
    }
}
