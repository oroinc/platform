<?php

namespace Oro\Bundle\SecurityBundle\EventListener;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\Common\Util\ClassUtils;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Event\OnClearEventArgs;

use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

use Oro\Bundle\EntityBundle\ORM\Event\PreCloseEventArgs;
use Oro\Bundle\SecurityBundle\Authentication\Token\OrganizationContextTokenInterface;

class RefreshContextListener
{
    /** @var TokenStorageInterface */
    protected $securityTokenStorage;

    /** @var ManagerRegistry */
    protected $doctrine;

    /** @var bool */
    protected $isClosing = false;

    /**
     * @param TokenStorageInterface $securityTokenStorage
     * @param ManagerRegistry       $doctrine
     */
    public function __construct(TokenStorageInterface $securityTokenStorage, ManagerRegistry $doctrine)
    {
        $this->securityTokenStorage = $securityTokenStorage;
        $this->doctrine             = $doctrine;
    }

    /**
     * @param PreCloseEventArgs $event
     */
    public function preClose(PreCloseEventArgs $event)
    {
        $this->isClosing = true;
    }

    /**
     * @param OnClearEventArgs $event
     */
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
        if ($token && $token instanceof OrganizationContextTokenInterface) {
            $this->checkOrganization($event, $token);
        }
    }

    /**
     * @param OnClearEventArgs $event
     * @param TokenInterface   $token
     */
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
        $em = $event->getEntityManager();
        if ($em !== $this->doctrine->getManagerForClass($userClass)) {
            return;
        }
        $user = $this->refreshEntity($user, $userClass, $em);
        if ($user) {
            $token->setUser($user);
        } else {
            $this->securityTokenStorage->setToken(null);
        }
    }

    /**
     * @param OnClearEventArgs                  $event
     * @param OrganizationContextTokenInterface $token
     */
    protected function checkOrganization(OnClearEventArgs $event, OrganizationContextTokenInterface $token)
    {
        $organization = $token->getOrganizationContext();
        if (!is_object($organization)) {
            return;
        }
        $organizationClass = ClassUtils::getClass($organization);
        if ($event->getEntityClass() && $event->getEntityClass() !== $organizationClass) {
            return;
        }
        $em = $event->getEntityManager();
        if ($em !== $this->doctrine->getManagerForClass($organizationClass)) {
            return;
        }
        $organization = $this->refreshEntity($organization, $organizationClass, $em);
        if (!$organization) {
            return;
        }
        $token->setOrganizationContext($organization);
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
