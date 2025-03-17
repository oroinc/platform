<?php

namespace Oro\Bundle\SecurityBundle\EventListener;

use Doctrine\Common\Util\ClassUtils;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\OnClearEventArgs;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\SecurityBundle\Authentication\Token\OrganizationAwareTokenInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

/**
 * Refreshes the user and the organization in the security context when the entity manager is closed.
 */
class RefreshContextListener
{
    private bool $isClosing = false;

    public function __construct(
        private TokenStorageInterface $securityTokenStorage,
        private ManagerRegistry $doctrine
    ) {
    }

    public function preClose(): void
    {
        $this->isClosing = true;
    }

    public function onClear(OnClearEventArgs $event): void
    {
        if ($this->isClosing) {
            $this->isClosing = false;

            return;
        }

        $token = $this->securityTokenStorage->getToken();
        if (!$token) {
            return;
        }

        $this->checkUser($event, $token);

        $token = $this->securityTokenStorage->getToken();
        if ($token instanceof OrganizationAwareTokenInterface) {
            $this->checkOrganization($event, $token);
        }
    }

    private function checkUser(OnClearEventArgs $event, TokenInterface $token)
    {
        $user = $token->getUser();
        if (!\is_object($user)) {
            return;
        }
        $userClass = ClassUtils::getClass($user);
        if ($event->getEntityClass() && $event->getEntityClass() !== $userClass) {
            return;
        }

        $em = $this->doctrine->getManagerForClass($userClass);
        if (!$em) {
            return;
        }

        $user = $this->refreshEntity($user, $userClass, $em);
        if ($user) {
            $token->setUser($user);
        } else {
            $this->securityTokenStorage->setToken(null);
        }
    }

    private function checkOrganization(OnClearEventArgs $event, OrganizationAwareTokenInterface $token)
    {
        $organization = $token->getOrganization();
        if (!\is_object($organization)) {
            return;
        }
        $organizationClass = ClassUtils::getClass($organization);
        if ($event->getEntityClass() && $event->getEntityClass() !== $organizationClass) {
            return;
        }

        $em = $this->doctrine->getManagerForClass($organizationClass);
        if (!$em) {
            return;
        }

        $organization = $this->refreshEntity($organization, $organizationClass, $em);
        if (!$organization) {
            return;
        }
        $token->setOrganization($organization);
    }

    private function refreshEntity(object $entity, string $entityClass, EntityManagerInterface $em): ?object
    {
        $identifierValues = $em->getClassMetadata($entityClass)->getIdentifierValues($entity);
        if (\count($identifierValues) !== 1) {
            return null;
        }

        $entityId = current($identifierValues);
        if (!$entityId) {
            return null;
        }

        // using find instead of merge to prevent unexpected entity updates,
        // properties of an old entity which are objects(instances of \DateTime i.e.)
        // are not equal === with properties of a new entity
        return $em->find($entityClass, $entityId);
    }
}
