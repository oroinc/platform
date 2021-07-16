<?php

namespace Oro\Bundle\WindowsBundle\Manager;

use Doctrine\ORM\EntityManager;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\WindowsBundle\Entity\AbstractWindowsState;
use Oro\Bundle\WindowsBundle\Entity\Repository\AbstractWindowsStateRepository;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * Provides a way to manage windows state.
 */
class WindowsStateManager
{
    /** @var TokenStorageInterface */
    private $tokenStorage;

    /** @var ManagerRegistry */
    private $doctrine;

    /** @var WindowsStateRequestManager */
    private $requestStateManager;

    /** @var string */
    private $className;

    public function __construct(
        TokenStorageInterface $tokenStorage,
        ManagerRegistry $doctrine,
        WindowsStateRequestManager $requestStateManager,
        string $className
    ) {
        $this->tokenStorage = $tokenStorage;
        $this->doctrine = $doctrine;
        $this->requestStateManager = $requestStateManager;
        $this->className = $className;
    }

    /**
     * @return int
     */
    public function createWindowsState()
    {
        $em = $this->getEntityManager();

        /** @var AbstractWindowsState $state */
        $state = $em->getClassMetadata($this->className)->newInstance();
        $state->setData($this->requestStateManager->getData());
        $state->setUser($this->getUser());

        $em->persist($state);
        $em->flush($state);

        return $state->getId();
    }

    /**
     * @param int $windowId
     *
     * @return bool
     */
    public function updateWindowsState($windowId)
    {
        return (bool)$this->getRepository()->update(
            $this->getUser(),
            $this->filterId($windowId),
            $this->requestStateManager->getData()
        );
    }

    /**
     * @param int $windowId
     *
     * @return bool
     */
    public function deleteWindowsState($windowId)
    {
        return (bool)$this->getRepository()->delete($this->getUser(), $this->filterId($windowId));
    }

    /**
     * @return AbstractWindowsState[]
     */
    public function getWindowsStates()
    {
        return $this->getRepository()->findBy(['user' => $this->getUser()]);
    }

    /**
     * @param int $windowId
     *
     * @return AbstractWindowsState
     */
    public function getWindowsState($windowId)
    {
        return $this->getRepository()->findOneBy(['user' => $this->getUser(), 'id' => $this->filterId($windowId)]);
    }

    /**
     * @return EntityManager
     */
    private function getEntityManager()
    {
        return $this->doctrine->getManagerForClass($this->className);
    }

    /**
     * @return AbstractWindowsStateRepository
     */
    private function getRepository()
    {
        return $this->getEntityManager()->getRepository($this->className);
    }

    /**
     * @param mixed $windowId
     *
     * @return int
     */
    private function filterId($windowId)
    {
        $windowId = filter_var($windowId, FILTER_VALIDATE_INT);
        if (false === $windowId) {
            throw new \InvalidArgumentException('Wrong $windowId type');
        }

        return $windowId;
    }

    /**
     * @return UserInterface
     */
    private function getUser()
    {
        $token = $this->tokenStorage->getToken();
        if (null === $token) {
            throw new AccessDeniedException();
        }

        $user = $token->getUser();
        if (!is_object($user) || !$user instanceof UserInterface) {
            throw new AccessDeniedException();
        }

        return $user;
    }
}
