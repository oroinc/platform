<?php

namespace Oro\Bundle\SecurityBundle\Twig;

use Symfony\Component\Security\Core\SecurityContextInterface;

use Oro\Bundle\SecurityBundle\Authentication\Token\OrganizationContextTokenInterface;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\UserBundle\Entity\User;

class OroSecurityOrganizationExtension extends \Twig_Extension
{
    /** @var SecurityContextInterface */
    protected $securityContext;

    /**
     * Constructor
     *
     * @param SecurityContextInterface $securityContext
     */
    public function __construct(SecurityContextInterface $securityContext)
    {
        $this->securityContext = $securityContext;
    }

    /**
     * {@inheritdoc}
     */
    public function getFunctions()
    {
        return [
            'get_enabled_organizations' => new \Twig_SimpleFunction('get_enabled_organizations', [
                $this,
                'getOrganizations'
            ]),
            'get_current_organization' => new \Twig_SimpleFunction('get_current_organization', [
                $this,
                'getCurrentOrganization'
            ]),
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'oro_security_organization_extension';
    }

    /**
     * Get list with all enabled organizations for current user
     *
     * @return Organization[]
     */
    public function getOrganizations()
    {
        $token = $this->securityContext->getToken();
        $user = $token ? $token->getUser() : null;
        return $user instanceof User ? $user->getOrganizations(true)->toArray() : [];
    }

    /**
     * Returns current organization
     *
     * @return Organization|null
     */
    public function getCurrentOrganization()
    {
        $token = $this->securityContext->getToken();
        return $token instanceof OrganizationContextTokenInterface ? $token->getOrganizationContext() : null;
    }
}
