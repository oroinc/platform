<?php

namespace Oro\Bundle\SecurityBundle\Http\Firewall;

use Doctrine\ORM\NoResultException;
use Oro\Bundle\OrganizationBundle\Entity\Manager\OrganizationManager;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\SecurityBundle\Authentication\Token\OrganizationContextTokenInterface;
use Oro\Bundle\SecurityBundle\Exception\OrganizationAccessDeniedException;
use Oro\Bundle\UserBundle\Entity\AbstractUser;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Security;

/**
 * Manages the organization aware security context persistence through a session.
 */
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
            $user = $token->getUser();
            if ($user instanceof AbstractUser) {
                $organizationAccessDenied = true;
                $organizationId = $token->getOrganizationContext()->getId();
                /** @var Organization[] $organizations */
                $organizations = $user->getOrganizations(true);
                foreach ($organizations as $organization) {
                    if ($organizationId === $organization->getId()) {
                        $token->setOrganizationContext($organization);
                        $organizationAccessDenied = false;
                        break;
                    }
                }
                if ($organizationAccessDenied) {
                    $exception = new OrganizationAccessDeniedException();
                    $exception->setOrganizationName($token->getOrganizationContext()->getName());
                    $exception->setToken($token);
                    $session = $event->getRequest()->getSession();
                    if ($session) {
                        $session->set(Security::AUTHENTICATION_ERROR, $exception);
                    }
                    $tokenStorage->setToken(null);
                    throw $exception;
                }
            } else {
                try {
                    $token->setOrganizationContext(
                        $this->getOrganizationManager()->getOrganizationById($token->getOrganizationContext()->getId())
                    );
                } catch (NoResultException $e) {
                    $token->setAuthenticated(false);
                }
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
