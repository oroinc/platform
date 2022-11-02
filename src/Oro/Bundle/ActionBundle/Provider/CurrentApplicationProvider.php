<?php

namespace Oro\Bundle\ActionBundle\Provider;

use Oro\Bundle\UserBundle\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

/**
 * The default implementation of the current application provider.
 */
class CurrentApplicationProvider implements CurrentApplicationProviderInterface
{
    /** @var TokenStorageInterface */
    protected $tokenStorage;

    public function __construct(TokenStorageInterface $tokenStorage)
    {
        $this->tokenStorage = $tokenStorage;
    }

    /**
     * {@inheritdoc}
     */
    public function isApplicationsValid(array $applications): bool
    {
        if (empty($applications)) {
            return true;
        }

        $currentApplication = $this->getCurrentApplication();

        return $currentApplication && in_array($currentApplication, $applications, true);
    }

    /**
     * {@inheritdoc}
     */
    public function getCurrentApplication(): ?string
    {
        $token = $this->tokenStorage->getToken();

        return null !== $token && $token->getUser() instanceof User
            ? static::DEFAULT_APPLICATION
            : null;
    }
}
