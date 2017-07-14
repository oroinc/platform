<?php

namespace Oro\Bundle\UserBundle\Provider;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\ConfigBundle\Utils\TreeUtils;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\UserBundle\Entity\User;

class DefaultUserProvider
{
    /** @var ConfigManager */
    private $configManager;

    /** @var DoctrineHelper */
    private $doctrineHelper;

    /**
     * @param ConfigManager $configManager
     * @param DoctrineHelper $doctrineHelper
     */
    public function __construct(ConfigManager $configManager, DoctrineHelper $doctrineHelper)
    {
        $this->configManager = $configManager;
        $this->doctrineHelper = $doctrineHelper;
    }

    /**
     * @param string $alias
     * @param string $configKey
     *
     * @return User|null
     */
    public function getDefaultUser($alias, $configKey)
    {
        $settingsKey = TreeUtils::getConfigKey($alias, $configKey);
        $ownerId = $this->configManager->get($settingsKey);
        $repository = $this->doctrineHelper->getEntityRepositoryForClass(User::class);
        $owner = null;
        if ($ownerId) {
            $owner = $repository->find($ownerId);
        }
        if (!$owner) {
            $owner = $repository->findOneBy([], ['id' => 'ASC']);
        }

        return $owner;
    }
}
