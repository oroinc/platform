<?php

namespace Oro\Bundle\EntityConfigBundle\Config;

use Doctrine\Common\Util\ClassUtils;
use Doctrine\ORM\EntityManager;

use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\User\UserInterface;

use Oro\Bundle\EntityConfigBundle\Entity\ConfigLogDiff;
use Oro\Bundle\EntityConfigBundle\Entity\ConfigLog;
use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;

/**
 * Audit entity config changes
 */
class AuditEntityBuilder
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
     * Creates an audit entity contains all changed configs
     *
     * @param ConfigManager $configManager
     *
     * @return ConfigLog|null
     */
    public function buildEntity(ConfigManager $configManager)
    {
        $user = $this->getUser($configManager->getEntityManager());
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
    protected function getUser(EntityManager $em)
    {
        $token = $this->securityTokenStorage->getToken();
        if (null === $token) {
            return null;
        }
        $user = $token->getUser();
        if (!$user instanceof UserInterface) {
            return null;
        }

        $className = ClassUtils::getClass($user);
        $id = $em->getClassMetadata($className)->getIdentifierValues($user);
        $id = reset($id);

        return $em->getReference($className, $id);
    }
}
