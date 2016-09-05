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

class ContextListener
{
    /** @var TokenStorageInterface */
    private $tokenStorage = false;

    /** @var OrganizationManager */
    private $manager = false;

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
        $token = $this->getTokenStorage()->getToken();
        if ($token instanceof OrganizationContextTokenInterface && $token->getOrganizationContext()) {
            try {
                $token->setOrganizationContext(
                    $this->getOrganizationManager()->getOrganizationById($token->getOrganizationContext()->getId())
                );

                if (!$token->getUser()->getOrganizations(true)->contains($token->getOrganizationContext())) {
                    $exception = new OrganizationAccessDeniedException();
                    $exception->setOrganizationName($token->getOrganizationContext()->getName());
                    $exception->setToken($token);
                    $event->getRequest()->getSession()->set(Security::AUTHENTICATION_ERROR, $exception);
                    $this->getTokenStorage()->setToken(null);
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
        if ($this->tokenStorage === false) {
            $this->tokenStorage = $this->container->get('security.token_storage');
        }

        return $this->tokenStorage;
    }

    /**
     * @return OrganizationManager
     */
    protected function getOrganizationManager()
    {
        if ($this->manager === false) {
            $this->manager = $this->container->get('oro_organization.organization_manager');
        }

        return $this->manager;
    }
}
