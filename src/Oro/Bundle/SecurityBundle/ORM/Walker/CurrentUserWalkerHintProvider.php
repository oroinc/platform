<?php

namespace Oro\Bundle\SecurityBundle\ORM\Walker;

use Symfony\Component\Security\Core\SecurityContextInterface;

use Oro\Bundle\EntityBundle\ORM\QueryWalkerHintProviderInterface;
use Oro\Bundle\SecurityBundle\Authentication\Token\OrganizationContextTokenInterface;
use Oro\Bundle\UserBundle\Entity\AbstractUser;

class CurrentUserWalkerHintProvider implements QueryWalkerHintProviderInterface
{
    /** @var SecurityContextInterface */
    protected $securityContext;

    /**
     * @param SecurityContextInterface $securityContext
     */
    public function __construct(SecurityContextInterface $securityContext)
    {
        $this->securityContext = $securityContext;
    }

    /**
     * {@inheritdoc}
     */
    public function getHints($params)
    {
        $securityContext = [];

        $token = $this->securityContext->getToken();
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
