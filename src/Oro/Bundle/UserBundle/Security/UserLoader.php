<?php

namespace Oro\Bundle\UserBundle\Security;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\UserBundle\Entity\Repository\UserRepository;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\UserBundle\Entity\UserInterface;

/**
 * Loads User entity from the database for the authentication system.
 */
class UserLoader implements UserLoaderInterface
{
    /** @var ManagerRegistry */
    private $doctrine;

    /** @var ConfigManager */
    private $configManager;

    public function __construct(ManagerRegistry $doctrine, ConfigManager $configManager)
    {
        $this->doctrine = $doctrine;
        $this->configManager = $configManager;
    }

    #[\Override]
    public function getUserClass(): string
    {
        return User::class;
    }

    #[\Override]
    public function loadUser(string $login): ?UserInterface
    {
        $user = $this->loadUserByIdentifier($login);
        if (!$user && filter_var($login, FILTER_VALIDATE_EMAIL)) {
            $user = $this->loadUserByEmail($login);
        }

        return $user;
    }

    #[\Override]
    public function loadUserByIdentifier(string $username): ?UserInterface
    {
        return $this->getRepository()->findOneBy(['username' => $username]);
    }

    #[\Override]
    public function loadUserByEmail(string $email): ?UserInterface
    {
        return $this->getRepository()->findUserByEmail(
            $email,
            (bool)$this->configManager->get('oro_user.case_insensitive_email_addresses_enabled')
        );
    }

    private function getRepository(): UserRepository
    {
        return $this->doctrine
            ->getManagerForClass($this->getUserClass())
            ->getRepository($this->getUserClass());
    }
}
