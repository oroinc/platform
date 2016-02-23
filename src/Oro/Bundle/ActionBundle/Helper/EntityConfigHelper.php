<?php

namespace Oro\Bundle\ActionBundle\Helper;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\SecurityBundle\Acl\Group\AclGroupProviderInterface;

class EntityConfigHelper
{
    /** @var ConfigProvider */
    protected $entityConfigProvider;

    /** @var AclGroupProviderInterface */
    protected $groupProvider;

    /** @var DoctrineHelper */
    protected $doctrineHelper;

    /**
     * @param ConfigProvider $entityConfigProvider
     * @param DoctrineHelper $doctrineHelper
     * @param AclGroupProviderInterface $groupProvider
     */
    public function __construct(
        ConfigProvider $entityConfigProvider,
        DoctrineHelper $doctrineHelper,
        AclGroupProviderInterface $groupProvider
    ) {
        $this->entityConfigProvider = $entityConfigProvider;
        $this->doctrineHelper = $doctrineHelper;
        $this->groupProvider = $groupProvider;
    }

    /**
     * @param mixed $class
     * @param array $routes
     * @param string $groupName
     * @return array
     */
    public function getRoutes($class, array $routes, $groupName = '')
    {
        $metadata = $this->getEntityConfigManager()->getEntityMetadata($this->doctrineHelper->getEntityClass($class));

        $result = [];

        foreach ($routes as $route) {
            try {
                $result[$route] = $metadata->getRoute($this->getRouteByGroup($route, $groupName));
            } catch (\Exception $e) {
                $result[$route] = null;
            }
        }

        return $result;
    }

    /**
     * @param mixed $class
     * @param string $name
     * @return mixed
     */
    public function getConfigValue($class, $name)
    {
        $config = $this->entityConfigProvider->getConfig($this->doctrineHelper->getEntityClass($class));

        return $config->get($name);
    }

    /**
     * @param string $route
     * @param string $groupName
     * @return string
     */
    protected function getRouteByGroup($route, $groupName = '')
    {
        $group = $groupName ?: $this->groupProvider->getGroup();

        if ($group === AclGroupProviderInterface::DEFAULT_SECURITY_GROUP) {
            return $route;
        }

        return $group . ucfirst($route);
    }

    /**
     * @return ConfigManager
     */
    protected function getEntityConfigManager()
    {
        return $this->entityConfigProvider->getConfigManager();
    }
}
