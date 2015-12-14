<?php

namespace Oro\Bundle\WindowsBundle\Manager;

use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\User\UserInterface;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\WindowsBundle\Entity\AbstractWindowsState;
use Oro\Bundle\WindowsBundle\Entity\Repository\AbstractWindowsStateRepository;

class WindowsStateManager
{
    /** @var TokenStorageInterface */
    protected $tokenStorage;

    /** @var DoctrineHelper */
    protected $doctrineHelper;

    /** @var WindowsStateRequestManager */
    protected $requestStateManager;

    /** @var string */
    protected $className;

    /**
     * @param TokenStorageInterface $tokenStorage
     * @param DoctrineHelper $doctrineHelper
     * @param WindowsStateRequestManager $requestStateManager
     * @param string $className
     */
    public function __construct(
        TokenStorageInterface $tokenStorage,
        DoctrineHelper $doctrineHelper,
        WindowsStateRequestManager $requestStateManager,
        $className
    ) {
        $this->tokenStorage = $tokenStorage;
        $this->doctrineHelper = $doctrineHelper;
        $this->className = $className;
        $this->requestStateManager = $requestStateManager;
    }

    /**
     * @return int
     */
    public function createWindowsState()
    {
        /** @var AbstractWindowsState $state */
        $state = $this->doctrineHelper->createEntityInstance($this->className);
        $state->setData($this->requestStateManager->getData());
        $state->setUser($this->getUser());

        $em = $this->doctrineHelper->getEntityManagerForClass($this->className);
        $em->persist($state);
        $em->flush($state);

        return $state->getId();
    }

    /**
     * @param int $windowId
     * @return bool
     */
    public function updateWindowsState($windowId)
    {
        return (bool)$this->getRepository()->update($this->getUser(), $windowId, $this->requestStateManager->getData());
    }

    /**
     * @param int $windowId
     * @return bool
     */
    public function deleteWindowsState($windowId)
    {
        return (bool)$this->getRepository()->delete($this->getUser(), $windowId);
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
     * @return AbstractWindowsState
     */
    public function getWindowsState($windowId)
    {
        return $this->getRepository()->findBy(['user' => $this->getUser(), 'id' => (int)$windowId]);
    }

    /**
     * @return AbstractWindowsStateRepository
     */
    protected function getRepository()
    {
        return $this->doctrineHelper->getEntityRepository($this->className);
    }

    /**
     * @return UserInterface
     *
     * @see TokenInterface::getUser()
     */
    public function getUser()
    {
        if (null === $token = $this->tokenStorage->getToken()) {
            return null;
        }

        if (!is_object($user = $token->getUser())) {
            return null;
        }

        return $user;
    }
}
