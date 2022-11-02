<?php

namespace Oro\Bundle\SecurityBundle\EventListener;

use Doctrine\Common\Util\ClassUtils;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Event\OnClearEventArgs;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\EntityBundle\ORM\Event\PreCloseEventArgs;
use Oro\Bundle\SecurityBundle\Authentication\Token\OrganizationAwareTokenInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

/**
 * Refreshes the user and the organization in the security context when the entity manager is closed.
 */
class RefreshContextListener
{
    /** @var TokenStorageInterface */
    protected $securityTokenStorage;

    /** @var ManagerRegistry */
    protected $doctrine;

    /** @var bool */
    protected $isClosing = false;

    public function __construct(TokenStorageInterface $securityTokenStorage, ManagerRegistry $doctrine)
    {
        $this->securityTokenStorage = $securityTokenStorage;
        $this->doctrine             = $doctrine;
    }

    public function preClose(PreCloseEventArgs $event)
    {
        $this->isClosing = true;
    }

    public function onClear(OnClearEventArgs $event)
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
        if ($token && $token instanceof OrganizationAwareTokenInterface) {
            $this->checkOrganization($event, $token);
        }
    }

    protected function checkUser(OnClearEventArgs $event, TokenInterface $token)
    {
        $user = $token->getUser();
        if (!is_object($user)) {
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

    protected function checkOrganization(OnClearEventArgs $event, OrganizationAwareTokenInterface $token)
    {
        $organization = $token->getOrganization();
        if (!is_object($organization)) {
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

    /**
     * @param object        $entity
     * @param string        $entityClass
     * @param EntityManager $em
     *
     * @return object|null
     */
    protected function refreshEntity($entity, $entityClass, EntityManager $em)
    {
        $identifierValues = $em->getClassMetadata($entityClass)->getIdentifierValues($entity);
        if (count($identifierValues) !== 1) {
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
