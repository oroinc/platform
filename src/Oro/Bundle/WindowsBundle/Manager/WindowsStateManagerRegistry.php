<?php

namespace Oro\Bundle\WindowsBundle\Manager;

use Psr\Container\ContainerInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

/**
 * The registry of windows state managers.
 */
class WindowsStateManagerRegistry
{
    /** @var string[] */
    private $userClasses;

    /** @var ContainerInterface */
    private $managerContainer;

    /** @var TokenStorageInterface */
    private $tokenStorage;

    /**
     * @param string[]              $userClasses
     * @param ContainerInterface    $managerContainer
     * @param TokenStorageInterface $tokenStorage
     */
    public function __construct(
        array $userClasses,
        ContainerInterface $managerContainer,
        TokenStorageInterface $tokenStorage
    ) {
        $this->userClasses = $userClasses;
        $this->managerContainer = $managerContainer;
        $this->tokenStorage = $tokenStorage;
    }

    public function getManager(): ?WindowsStateManager
    {
        $userClass = $this->getUserClass();
        if ($userClass) {
            foreach ($this->userClasses as $managerUserClass) {
                if (is_a($userClass, $managerUserClass, true)) {
                    return $this->managerContainer->get($managerUserClass);
                }
            }
        }

        return null;
    }

    private function getUserClass(): ?string
    {
        $token = $this->tokenStorage->getToken();
        if (null === $token) {
            return null;
        }

        $user = $token->getUser();
        if (!is_object($user)) {
            return null;
        }

        return get_class($user);
    }
}
