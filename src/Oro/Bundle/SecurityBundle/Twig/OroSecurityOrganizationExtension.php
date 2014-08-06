<?php

namespace Oro\Bundle\SecurityBundle\Twig;

use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\Security\Core\SecurityContextInterface;

class OroSecurityOrganizationExtension extends \Twig_Extension
{
    /**
     * @var SecurityContextInterface
     */
    protected $securityContext;

    /**
     * Constructor
     *
     * @param SecurityContextInterface $container
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
     * @return ArrayCollection
     */
    public function getOrganizations()
    {
        return $this->securityContext->getToken()->getUser()->getOrganizations(true);
    }
}
