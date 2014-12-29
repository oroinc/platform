<?php

namespace Oro\Bundle\SecurityBundle\Http\Firewall;

use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\Security\Core\SecurityContextInterface;

use Oro\Bundle\OrganizationBundle\Entity\Manager\OrganizationManager;
use Oro\Bundle\SecurityBundle\Authentication\Token\OrganizationContextTokenInterface;

class ContextListener
{
    /** @var SecurityContextInterface */
    protected $context;

    /** @var OrganizationManager */
    protected $manager;

    /**
     * @param SecurityContextInterface $context
     * @param OrganizationManager      $manager
     */
    public function __construct(SecurityContextInterface $context, OrganizationManager $manager)
    {
        $this->context = $context;
        $this->manager = $manager;
    }

    /**
     * Refresh organization context in token
     *
     * @param GetResponseEvent $event
     */
    public function onKernelRequest(GetResponseEvent $event)
    {
        $token = $this->context->getToken();
        if ($token instanceof OrganizationContextTokenInterface) {
            $token->setOrganizationContext(
                $this->manager->getOrganizationById($token->getOrganizationContext()->getId())
            );
        }
    }
}
