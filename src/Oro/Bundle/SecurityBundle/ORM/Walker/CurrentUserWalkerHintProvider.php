<?php

namespace Oro\Bundle\SecurityBundle\ORM\Walker;

use Oro\Bundle\SecurityBundle\Authentication\Token\OrganizationAwareTokenInterface;
use Oro\Bundle\UserBundle\Entity\AbstractUser;
use Oro\Component\DoctrineUtils\ORM\QueryWalkerHintProviderInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

/**
 * Provides query walker hint to filter a query root entity by the current user and organization.
 */
class CurrentUserWalkerHintProvider implements QueryWalkerHintProviderInterface
{
    /** @var TokenStorageInterface */
    protected $tokenStorage;

    public function __construct(TokenStorageInterface $tokenStorage)
    {
        $this->tokenStorage = $tokenStorage;
    }

    /**
     * {@inheritdoc}
     */
    public function getHints($params)
    {
        $securityContext = [];

        $token = $this->tokenStorage->getToken();
        if ($token) {
            $user = $token->getUser();
            if ($user instanceof AbstractUser) {
                $field = is_array($params) && isset($params['user_field'])
                    ? $params['user_field']
                    : 'owner';
                $securityContext[$field] = $user->getId();
                if ($token instanceof OrganizationAwareTokenInterface) {
                    $field = is_array($params) && isset($params['organization_field'])
                        ? $params['organization_field']
                        : 'organization';
                    $securityContext[$field] = $token->getOrganization()->getId();
                }
            }
        }

        return [
            CurrentUserWalker::HINT_SECURITY_CONTEXT => $securityContext
        ];
    }
}
