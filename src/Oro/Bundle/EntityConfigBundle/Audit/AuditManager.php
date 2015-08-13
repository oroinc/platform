<?php

namespace Oro\Bundle\EntityConfigBundle\Audit;

use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\User\UserInterface;

use Oro\Bundle\EntityConfigBundle\Config\ConfigInterface;
use Oro\Bundle\EntityConfigBundle\Entity\ConfigLogDiff;
use Oro\Bundle\EntityConfigBundle\Entity\ConfigLog;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;

/**
 * Audit entity config changes
 */
class AuditManager
{
    /** @var TokenStorageInterface */
    protected $securityTokenStorage;

    /**
     * @param TokenStorageInterface $securityTokenStorage
     */
    public function __construct(TokenStorageInterface $securityTokenStorage)
    {
        $this->securityTokenStorage = $securityTokenStorage;
    }

    /**
     * Creates a log entry contains all changed configs
     *
     * @param ConfigManager $configManager
     *
     * @return ConfigLog|null
     */
    public function buildLogEntry(ConfigManager $configManager)
    {
        $user = $this->getUser();
        if (null === $user) {
            return null;
        }

        $log = new ConfigLog();
        foreach ($configManager->getUpdateConfig() as $config) {
            $diff = $this->computeChanges($config, $configManager);
            if (null !== $diff) {
                $log->addDiff($diff);
            }
        }

        if ($log->getDiffs()->isEmpty()) {
            return null;
        }

        $log->setUser($user);

        return $log;
    }

    /**
     * @param ConfigInterface $config
     * @param ConfigManager   $configManager
     *
     * @return ConfigLogDiff
     */
    protected function computeChanges(ConfigInterface $config, ConfigManager $configManager)
    {
        $configId       = $config->getId();
        $internalValues = $configManager
            ->getProvider($configId->getScope())
            ->getPropertyConfig()
            ->getNotAuditableValues($configId);

        $changes = array_diff_key($configManager->getConfigChangeSet($config), $internalValues);
        if (empty($changes)) {
            return null;
        }

        $diff = new ConfigLogDiff();
        $diff->setScope($configId->getScope());
        $diff->setDiff($changes);
        $diff->setClassName($configId->getClassName());
        if ($configId instanceof FieldConfigId) {
            $diff->setFieldName($configId->getFieldName());
        }

        return $diff;
    }

    /**
     * @return UserInterface|null
     */
    protected function getUser()
    {
        $token = $this->securityTokenStorage->getToken();
        if (null === $token) {
            return null;
        }
        $user = $token->getUser();
        if (!$user instanceof UserInterface) {
            return null;
        }

        return $user;
    }
}
