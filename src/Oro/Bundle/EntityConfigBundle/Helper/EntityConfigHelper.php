<?php

namespace Oro\Bundle\EntityConfigBundle\Helper;

use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\SecurityBundle\Acl\Group\AclGroupProviderInterface;

/**
 * The utility class that can be used to:
 * * get entity routes
 * * safe get entity config values in YAML files like action definition files
 */
class EntityConfigHelper
{
    /** @var ConfigProvider */
    private $configProvider;

    /** @var AclGroupProviderInterface */
    private $groupProvider;

    public function __construct(ConfigProvider $configProvider, AclGroupProviderInterface $groupProvider)
    {
        $this->configProvider = $configProvider;
        $this->groupProvider = $groupProvider;
    }

    /**
     * @param string|object $class
     * @param array         $routes
     * @param string        $groupName
     *
     * @return array
     */
    public function getRoutes($class, array $routes, $groupName = '')
    {
        $result = [];
        $metadata = $this->configProvider
            ->getConfigManager()
            ->getEntityMetadata($this->getClassName($class));
        if (null !== $metadata) {
            foreach ($routes as $route) {
                $routeName = $this->getRouteByGroup($route, $groupName);
                $result[$route] = $metadata->hasRoute($routeName, true)
                    ? $metadata->getRoute($routeName, true)
                    : null;
            }
        }

        return $result;
    }

    /**
     * @param string|object $class
     * @param string        $name
     * @param bool          $strict
     *
     * @return mixed
     */
    public function getConfigValue($class, $name, $strict = false)
    {
        $data = null;

        try {
            $config = $this->configProvider->getConfig($this->getClassName($class));
            $data = $config->get($name);
        } catch (\RuntimeException $e) {
            if ($strict) {
                throw $e;
            }
        }

        return $data;
    }

    /**
     * @param string|object $class
     *
     * @return string
     */
    private function getClassName($class)
    {
        if (\is_object($class)) {
            return \get_class($class);
        }

        return $class;
    }

    /**
     * @param string $route
     * @param string $groupName
     *
     * @return string
     */
    private function getRouteByGroup($route, $groupName = '')
    {
        $group = $groupName ?: $this->groupProvider->getGroup();

        if ($group === AclGroupProviderInterface::DEFAULT_SECURITY_GROUP) {
            return $route;
        }

        return $group . ucfirst($route);
    }
}
