<?php

namespace Oro\Bundle\NavigationBundle\Menu;

use Doctrine\Common\Cache\CacheProvider;

use Knp\Menu\Factory;
use Knp\Menu\ItemInterface;

use Psr\Log\LoggerInterface;

use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;

use Oro\Component\DependencyInjection\ServiceLink;

/**
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
class AclAwareMenuFactoryExtension implements Factory\ExtensionInterface
{
    /**#@+
     * ACL Aware MenuFactory constants
     */
    const ACL_RESOURCE_ID_KEY = 'acl_resource_id';
    const ROUTE_CONTROLLER_KEY = '_controller';
    const CONTROLLER_ACTION_DELIMITER = '::';
    const DEFAULT_ACL_POLICY = true;
    const ACL_POLICY_KEY = 'acl_policy';
    /**#@-*/

    /**
     * @var \Symfony\Component\Routing\RouterInterface
     */
    private $router;

    /**
     * @var ServiceLink
     */
    private $securityFacadeLink;

    /**
     * @var \Doctrine\Common\Cache\CacheProvider
     */
    private $cache;

    /** @var LoggerInterface */
    private $logger;

    /**
     * @var array
     */
    protected $aclCache = [];

    /**
     * @var bool
     */
    protected $hideAllForNotLoggedInUsers = true;

    /** @var array */
    private $optionsByCacheKey = [];

    /**
     * @param RouterInterface $router
     * @param ServiceLink     $securityFacadeLink
     */
    public function __construct(RouterInterface $router, ServiceLink $securityFacadeLink)
    {
        $this->router = $router;
        $this->securityFacadeLink = $securityFacadeLink;
    }

    /**
     * Set cache instance
     *
     * @param \Doctrine\Common\Cache\CacheProvider $cache
     */
    public function setCache(CacheProvider $cache)
    {
        $this->cache = $cache;
    }

    /**
     * @param LoggerInterface $logger
     */
    public function setLogger(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * @param bool $value
     */
    public function setHideAllForNotLoggedInUsers($value)
    {
        $this->hideAllForNotLoggedInUsers = $value;
    }

    /**
     * Configures the item with the passed options
     *
     * @param ItemInterface $item
     * @param array         $options
     */
    public function buildItem(ItemInterface $item, array $options)
    {
    }

    /**
     * Check permissions and set options for renderer.
     *
     * @param array $options
     * @return array
     */
    public function buildOptions(array $options = [])
    {
        $cacheKey = $this->getCacheKey('all_options', $options);
        if (!array_key_exists($cacheKey, $this->optionsByCacheKey)) {
            if (!$this->alreadyDenied($options)) {
                $aclCacheKey = $this->getCacheKey('acl_options', $options);
                $aclOptions = $this->getFromCache($aclCacheKey);
                if ($aclOptions === false) {
                    $aclOptions = $this->getAclOptions($options);
                    $this->saveToCache($aclCacheKey, $aclOptions);
                }

                $this->appendOptions($options, $aclOptions);

                $routeCacheKey = $this->getCacheKey('route_options', $options);
                $hasRouteOptions = $options['extras']['isAllowed'] && !empty($options['route']);
                if ($hasRouteOptions) {
                    $routeOptions = $this->getFromCache($routeCacheKey);
                    if ($routeOptions === false) {
                        $routeOptions = $this->getRouteOptions($options);
                        $this->saveToCache($routeCacheKey, $routeOptions);
                    }

                    $this->appendOptions($options, $routeOptions);
                }
            }

            $this->optionsByCacheKey[$cacheKey] = $options;
        }

        return $this->optionsByCacheKey[$cacheKey];
    }

    /**
     * Check ACL based on acl_resource_id, route or uri.
     *
     * @param array $options
     * @return array
     */
    protected function getAclOptions(array $options = [])
    {
        $isAllowed = self::DEFAULT_ACL_POLICY;

        if ($this->useDefaultAclPolicy($options, $isAllowed)) {
            return [
                'extras' => [
                    'isAllowed' => $isAllowed
                ]
            ];
        }

        $securityFacade = $this->securityFacadeLink->getService();
        if ($securityFacade->getToken() !== null) { // don't check access if it's CLI
            $isAllowed = $this->getOptionExtras($options, self::ACL_POLICY_KEY, $isAllowed);

            if (array_key_exists(self::ACL_RESOURCE_ID_KEY, $options)) {
                if (array_key_exists($options[self::ACL_RESOURCE_ID_KEY], $this->aclCache)) {
                    $isAllowed = $this->aclCache[$options[self::ACL_RESOURCE_ID_KEY]];
                } else {
                    $isAllowed = $securityFacade->isGranted($options[self::ACL_RESOURCE_ID_KEY]);
                    $this->aclCache[$options[self::ACL_RESOURCE_ID_KEY]] = $isAllowed;
                }
            } else {
                $routeInfo = $this->getRouteInfo($options);
                if ($routeInfo) {
                    if (array_key_exists($routeInfo['key'], $this->aclCache)) {
                        $isAllowed = $this->aclCache[$routeInfo['key']];
                    } else {
                        $isAllowed = $securityFacade->isClassMethodGranted(
                            $routeInfo['controller'],
                            $routeInfo['action']
                        );

                        $this->aclCache[$routeInfo['key']] = $isAllowed;
                    }
                }
            }
        }

        return [
            'extras' => [
                'isAllowed' => $isAllowed
            ]
        ];
    }

    /**
     * @param array $options
     * @param bool  $isAllowed
     * @return bool
     */
    private function useDefaultAclPolicy(array $options, &$isAllowed)
    {
        $hasAccess = array_key_exists('check_access', $options) && $options['check_access'] === false;
        if ($hasAccess) {
            return true;
        }

        $securityFacade = $this->securityFacadeLink->getService();
        $hideForNotLoggedIn = $this->hideAllForNotLoggedInUsers && !$securityFacade->hasLoggedUser();
        if ($hideForNotLoggedIn) {
            if ($this->getOptionExtras($options, 'show_non_authorized', false) !== false) {
                return true;
            }

            $isAllowed = false;

            return true;
        }

        return false;
    }

    /**
     * Add uri based on route.
     *
     * @param array $options
     * @return array
     */
    protected function getRouteOptions(array $options = [])
    {
        $params = [];
        if (isset($options['routeParameters'])) {
            $params = $options['routeParameters'];
        }

        $absolute = false;
        if (isset($options['routeAbsolute'])) {
            $absolute = $options['routeAbsolute'];
        }

        $uri = $this->router->generate($options['route'], $params, $absolute);

        return [
            'uri' => $uri,
            'extras' => [
                'routes' => [$options['route']],
                'routesParameters' => [$options['route'] => $params],
            ]
        ];
    }

    /**
     * Get route information based on MenuItem options
     *
     * @param array $options
     * @return array
     */
    protected function getRouteInfo(array $options = [])
    {
        $key = null;
        if (array_key_exists('route', $options)) {
            $key = $this->getRouteInfoByRouteName($options['route']);
        } elseif (array_key_exists('uri', $options)) {
            $key = $this->getRouteInfoByUri($options['uri']);
        }

        $info = explode(self::CONTROLLER_ACTION_DELIMITER, $key);
        if (count($info) === 2) {
            return [
                'controller' => $info[0],
                'action' => $info[1],
                'key' => $key
            ];
        }

        return [];
    }

    /**
     * Get route info by route name
     *
     * @param string $routeName
     * @return string|null
     */
    protected function getRouteInfoByRouteName($routeName)
    {
        $route = $this->router->getRouteCollection()->get($routeName);
        if ($route) {
            return $route->getDefault(self::ROUTE_CONTROLLER_KEY);
        }

        return null;
    }

    /**
     * Get route info by uri
     *
     * @param string $uri
     * @return null|string
     */
    protected function getRouteInfoByUri($uri)
    {
        try {
            $routeInfo = $this->router->match($uri);

            return $routeInfo[self::ROUTE_CONTROLLER_KEY];
        } catch (ResourceNotFoundException $e) {
            $this->logger->debug($e->getMessage(), ['pathinfo' => $uri]);
        }

        return null;
    }

    /**
     * Get safe cache key
     *
     * @param string $space
     * @param array  $value
     * @return string
     */
    protected function getCacheKey($space, array $value)
    {
        return md5($space . ':' . serialize($value));
    }

    /**
     * @param array $options
     * @return bool
     */
    protected function alreadyDenied(array $options)
    {
        $isAllowed = $this->getOptionExtras($options, 'isAllowed');
        return $isAllowed === false;
    }

    /**
     * @param array      $options
     * @param string     $key
     * @param mixed|null $default
     * @return mixed|null
     */
    private function getOptionExtras(array $options, $key, $default = null)
    {
        if (array_key_exists('extras', $options) && array_key_exists($key, $options['extras'])) {
            return $options['extras'][$key];
        }

        return $default;
    }

    /**
     * @param string $cacheKey
     * @param mixed  $data
     */
    private function saveToCache($cacheKey, $data)
    {
        if ($this->cache) {
            $this->cache->save($cacheKey, $data);
        }
    }

    /**
     * @param string $cacheKey
     * @return array|bool
     */
    private function getFromCache($cacheKey)
    {
        if ($this->cache && $this->cache->contains($cacheKey)) {
            return $this->cache->fetch($cacheKey);
        }

        return false;
    }

    /**
     * @param array $options
     * @param array $newOptions
     */
    private function appendOptions(array &$options, array $newOptions)
    {
        foreach ($newOptions as $key => $value) {
            if (is_array($value)) {
                if (!array_key_exists($key, $options) || !is_array($options[$key])) {
                    $options[$key] = [];
                }
                $this->appendOptions($options[$key], $value);
            } else {
                $options[$key] = $value;
            }
        }
    }
}
