<?php

namespace Oro\Bundle\SecurityBundle\ORM\Walker;

use Oro\Bundle\SecurityBundle\Authentication\Token\OrganizationContextTokenInterface;
use Oro\Bundle\UserBundle\Entity\AbstractUser;
use Oro\Component\DoctrineUtils\ORM\QueryWalkerHintProviderInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class CurrentUserWalkerHintProvider implements QueryWalkerHintProviderInterface
{
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
    public function getHints($params)
    {
        $securityContext = [];

        $token = $this->tokenStorage->getToken();
        if ($token) {
            $user = $token->getUser();
            if ($user instanceof AbstractUser) {
                $field                   = is_array($params) && isset($params['user_field'])
                    ? $params['user_field']
                    : 'owner';
                $securityContext[$field] = $user->getId();
                if ($token instanceof OrganizationContextTokenInterface) {
                    $field                   = is_array($params) && isset($params['organization_field'])
                        ? $params['organization_field']
                        : 'organization';
                    $securityContext[$field] = $token->getOrganizationContext()->getId();
                }
            }
        }

        return [
            CurrentUserWalker::HINT_SECURITY_CONTEXT => $securityContext
        ];
    }
}
