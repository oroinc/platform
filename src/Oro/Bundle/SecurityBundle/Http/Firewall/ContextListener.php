<?php

namespace Oro\Bundle\SecurityBundle\Http\Firewall;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\SecurityBundle\Authentication\Token\OrganizationAwareTokenInterface;
use Oro\Bundle\SecurityBundle\Exception\OrganizationAccessDeniedException;
use Oro\Bundle\UserBundle\Entity\AbstractUser;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Security;

/**
 * Manages the organization aware security context persistence through a session.
 */
class ContextListener
{
    private TokenStorageInterface $tokenStorage;

    private ManagerRegistry $doctrine;

    private LoggerInterface $logger;

    public function __construct(
        TokenStorageInterface $tokenStorage,
        ManagerRegistry $doctrine,
        LoggerInterface $logger
    ) {
        $this->tokenStorage = $tokenStorage;
        $this->doctrine = $doctrine;
        $this->logger = $logger;
    }

    /**
     * Refresh organization context in token
     */
    public function onKernelRequest(RequestEvent $event): void
    {
        $token = $this->tokenStorage->getToken();
        if (!$token instanceof OrganizationAwareTokenInterface) {
            return;
        }

        $organization = $token->getOrganization();
        if (null === $organization) {
            return;
        }

        $isAccessGranted = false;
        $organization = $this->refreshOrganization($organization);
        if (null !== $organization) {
            $token->setOrganization($organization);

            $user = $token->getUser();
            if (!$user instanceof AbstractUser || $user->isBelongToOrganization($organization, true)) {
                $isAccessGranted = true;
            }
        }

        if (!$isAccessGranted) {
            $this->denyAccess($event);
        }
    }

    private function refreshOrganization(Organization $organization): ?Organization
    {
        $organizationId = $organization->getId();

        $organization = $this->doctrine->getManagerForClass(Organization::class)
            ->find(Organization::class, $organizationId);

        if (null === $organization) {
            $this->logger->error(sprintf('Could not find organization by id %s', $organizationId));
        }

        return $organization;
    }

    /**
     * @throws OrganizationAccessDeniedException
     */
    private function denyAccess(RequestEvent $event): void
    {
        /** @var OrganizationAwareTokenInterface $token */
        $token = $this->tokenStorage->getToken();

        $this->tokenStorage->setToken(null);

        $exception = new OrganizationAccessDeniedException();
        $exception->setOrganizationName($token->getOrganization()->getName());
        $exception->setToken($token);

        $request = $event->getRequest();
        if ($request->hasSession()) {
            $request->getSession()->set(Security::AUTHENTICATION_ERROR, $exception);
        }

        throw $exception;
    }
}
