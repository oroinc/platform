<?php

namespace Oro\Bundle\SecurityBundle\Http\Firewall;

use Doctrine\ORM\NoResultException;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\Security\Core\Security;

use Oro\Bundle\OrganizationBundle\Entity\Manager\OrganizationManager;
use Oro\Bundle\SecurityBundle\Authentication\Token\OrganizationContextTokenInterface;
use Oro\Bundle\SecurityBundle\Exception\OrganizationAccessDeniedException;
use Oro\Bundle\UserBundle\Entity\AbstractUser;

class ContextListener
{
    /** @var ContainerInterface */
    private $container;

    /**
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * Refresh organization context in token
     *
     * @param GetResponseEvent $event
     */
    public function onKernelRequest(GetResponseEvent $event)
    {
        $tokenStorage = $this->getTokenStorage();
        $token = $tokenStorage->getToken();
        if ($token instanceof OrganizationContextTokenInterface && $token->getOrganizationContext()) {
            try {
                $token->setOrganizationContext(
                    $this->getOrganizationManager()->getOrganizationById($token->getOrganizationContext()->getId())
                );

                $user = $token->getUser();
                $organizationAccessDenied = $token->getUser() instanceof AbstractUser
                    && !$user->getOrganizations(true)->contains($token->getOrganizationContext());

                if ($organizationAccessDenied) {
                    $exception = new OrganizationAccessDeniedException();
                    $exception->setOrganizationName($token->getOrganizationContext()->getName());
                    $exception->setToken($token);
                    $event->getRequest()->getSession()->set(Security::AUTHENTICATION_ERROR, $exception);
                    $tokenStorage->setToken(null);
                    throw $exception;
                }
            } catch (NoResultException $e) {
                $token->setAuthenticated(false);
            }
        }
    }

    /**
     * @return TokenStorageInterface
     */
    protected function getTokenStorage()
    {
        return $this->container->get('security.token_storage');
    }

    /**
     * @return OrganizationManager
     */
    protected function getOrganizationManager()
    {
        return $this->container->get('oro_organization.organization_manager');
    }
}
