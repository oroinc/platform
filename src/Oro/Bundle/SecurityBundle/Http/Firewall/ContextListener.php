<?php

namespace Oro\Bundle\SecurityBundle\Http\Firewall;

use Doctrine\ORM\NoResultException;

use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\Security\Core\Security;

use Oro\Bundle\OrganizationBundle\Entity\Manager\OrganizationManager;
use Oro\Bundle\SecurityBundle\Authentication\Token\OrganizationContextTokenInterface;
use Oro\Bundle\SecurityBundle\Exception\OrganizationAccessDeniedException;

class ContextListener
{
    /** @var TokenStorageInterface */
    protected $tokenStorage;

    /** @var OrganizationManager */
    protected $manager;

    /**
     * @param TokenStorageInterface $tokenStorage
     * @param OrganizationManager   $manager
     */
    public function __construct(TokenStorageInterface $tokenStorage, OrganizationManager $manager)
    {
        $this->tokenStorage = $tokenStorage;
        $this->manager      = $manager;
    }

    /**
     * Refresh organization context in token
     *
     * @param GetResponseEvent $event
     */
    public function onKernelRequest(GetResponseEvent $event)
    {
        $token = $this->tokenStorage->getToken();
        if ($token instanceof OrganizationContextTokenInterface && $token->getOrganizationContext()) {
            try {
                $token->setOrganizationContext(
                    $this->manager->getOrganizationById($token->getOrganizationContext()->getId())
                );

                if (!$token->getUser()->getOrganizations(true)->contains($token->getOrganizationContext())) {
                    $exception = new OrganizationAccessDeniedException();
                    $exception->setOrganizationName($token->getOrganizationContext()->getName());
                    $exception->setToken($token);
                    $event->getRequest()->getSession()->set(Security::AUTHENTICATION_ERROR, $exception);
                    $this->tokenStorage->setToken(null);
                    throw $exception;
                }
            } catch (NoResultException $e) {
                $token->setAuthenticated(false);
            }
        }
    }
}
