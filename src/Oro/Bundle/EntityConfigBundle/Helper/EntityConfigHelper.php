<?php

namespace Oro\Bundle\EntityConfigBundle\Helper;

use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\SecurityBundle\Acl\Group\AclGroupProviderInterface;

class EntityConfigHelper
{
    /** @var ConfigProvider */
    protected $entityConfigProvider;

    /** @var AclGroupProviderInterface */
    protected $groupProvider;

    /**
     * @param ConfigProvider $configProvider
     * @param AclGroupProviderInterface $groupProvider
     */
    public function __construct(ConfigProvider $configProvider, AclGroupProviderInterface $groupProvider)
    {
        $this->configProvider = $configProvider;
        $this->groupProvider = $groupProvider;
    }

    /**
     * @param string|object $class
     * @param array $routes
     * @param string $groupName
     * @return array
     */
    public function getRoutes($class, array $routes, $groupName = '')
    {
        $metadata = $this->getConfigManager()->getEntityMetadata($this->configProvider->getClassName($class));

        $result = [];

        if ($metadata) {
            foreach ($routes as $route) {
                $routeName = $this->getRouteByGroup($route, $groupName);

                $result[$route] = $metadata->hasRoute($routeName, true) ? $metadata->getRoute($routeName, true) : null;

            }
        }

        return $result;
    }

    /**
     * @param string|object $class
     * @param string $name
     * @return mixed
     */
    public function getConfigValue($class, $name)
    {
        $config = $this->configProvider->getConfig($class);

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
    protected function getConfigManager()
    {
        return $this->configProvider->getConfigManager();
    }
}
