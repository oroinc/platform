<?php

namespace Oro\Bundle\UserBundle\Provider;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\UserBundle\Entity\User;

/**
 * Provides methods to get a default user for system configuration fields.
 */
class DefaultUserProvider
{
    private ConfigManager $configManager;
    private ManagerRegistry $doctrine;

    public function __construct(ConfigManager $configManager, ManagerRegistry $doctrine)
    {
        $this->configManager = $configManager;
        $this->doctrine = $doctrine;
    }

    public function getDefaultUser(string $configKey): ?User
    {
        $owner = null;
        $ownerId = $this->configManager->get($configKey);
        if ($ownerId) {
            $owner = $this->doctrine->getManagerForClass(User::class)->find(User::class, $ownerId);
        }
        if (null === $owner) {
            $owner = $this->doctrine->getRepository(User::class)->findOneBy([], ['id' => 'ASC']);
        }

        return $owner;
    }
}
