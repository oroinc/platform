<?php

namespace Oro\Bundle\OrganizationBundle\Provider;

use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\ScopeBundle\Manager\AbstractScopeCriteriaProvider;
use Oro\Bundle\SecurityBundle\Authentication\Token\OrganizationAwareTokenInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

/**
 * Provides the current organization for Scope Criteria.
 */
class ScopeOrganizationCriteriaProvider extends AbstractScopeCriteriaProvider
{
    const SCOPE_KEY = 'organization';

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

        if ($token instanceof OrganizationAwareTokenInterface) {
            return [self::SCOPE_KEY => $token->getOrganization()];
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
        return Organization::class;
    }
}
