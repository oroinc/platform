<?php

namespace Oro\Bundle\WindowsBundle\Manager;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\WindowsBundle\Entity\AbstractWindowsState;
use Oro\Bundle\WindowsBundle\Entity\Repository\AbstractWindowsStateRepository;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Core\User\UserInterface;

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

    /** @var string */
    protected $userClassName;

    /**
     * @param TokenStorageInterface $tokenStorage
     * @param DoctrineHelper $doctrineHelper
     * @param WindowsStateRequestManager $requestStateManager
     * @param string $className
     * @param string $userClassName
     */
    public function __construct(
        TokenStorageInterface $tokenStorage,
        DoctrineHelper $doctrineHelper,
        WindowsStateRequestManager $requestStateManager,
        $className,
        $userClassName
    ) {
        $this->tokenStorage = $tokenStorage;
        $this->doctrineHelper = $doctrineHelper;
        $this->requestStateManager = $requestStateManager;
        $this->className = $className;
        $this->userClassName = $userClassName;
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
        return (bool)$this->getRepository()->update(
            $this->getUser(),
            $this->filterId($windowId),
            $this->requestStateManager->getData()
        );
    }

    /**
     * @param int $windowId
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
     * @return AbstractWindowsState
     */
    public function getWindowsState($windowId)
    {
        return $this->getRepository()->findBy(['user' => $this->getUser(), 'id' => $this->filterId($windowId)]);
    }

    /**
     * @return AbstractWindowsStateRepository
     */
    protected function getRepository()
    {
        return $this->doctrineHelper->getEntityRepository($this->className);
    }

    /**
     * @param mixed $windowId
     * @return int
     */
    protected function filterId($windowId)
    {
        $windowId = filter_var($windowId, FILTER_VALIDATE_INT);
        if (false === $windowId) {
            throw new \InvalidArgumentException('Wrong $windowId type');
        }

        return $windowId;
    }

    /**
     * @return UserInterface
     *
     * @see TokenInterface::getUser()
     */
    protected function getUser()
    {
        if (null === $token = $this->tokenStorage->getToken()) {
            throw new AccessDeniedException();
        }

        if (!is_object($user = $token->getUser())) {
            throw new AccessDeniedException();
        }

        return $user;
    }

    /**
     * @return bool
     */
    public function isApplicable()
    {
        try {
            return is_a($this->getUser(), $this->userClassName);
        } catch (AccessDeniedException $e) {
            return false;
        }
    }
}
