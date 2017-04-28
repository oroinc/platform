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
     * Check Permissions and set options for renderer.
     *
     * @param  array $options
     * @return array
     */
    public function buildOptions(array $options = [])
    {
        if (!$this->alreadyDenied($options)) {
            $newOptions = [];

            $this->processAcl($newOptions, $options);

            if ($newOptions['extras']['isAllowed'] && !empty($options['route'])) {
                $this->processRoute($newOptions, $options);
            }

            $options = array_merge_recursive($newOptions, $options);
        }

        return $options;
    }

    /**
     * Check ACL based on acl_resource_id, route or uri.
     *
     * @param array $newOptions
     * @param array $options
     *
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    protected function processAcl(array &$newOptions, $options)
    {
        $isAllowed                      = self::DEFAULT_ACL_POLICY;
        $newOptions['extras']['isAllowed'] = $isAllowed;
        $options['extras']['isAllowed'] = $isAllowed;
        $securityFacade = $this->securityFacadeLink->getService();

        if (isset($options['check_access']) && $options['check_access'] === false) {
            return;
        }

        if ($this->hideAllForNotLoggedInUsers && !$securityFacade->hasLoggedUser()) {
            if (!empty($options['extras']['show_non_authorized'])) {
                return;
            }

            $isAllowed = false;
        } elseif ($securityFacade->getToken() !== null) { // don't check access if it's CLI
            if (array_key_exists(self::ACL_POLICY_KEY, $options['extras'])) {
                $isAllowed = $options['extras'][self::ACL_POLICY_KEY];
            }

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

        $newOptions['extras']['isAllowed'] = $isAllowed;
    }

    /**
     * Add uri based on route.
     *
     * @param array $newOptions
     * @param array $options
     */
    protected function processRoute(array &$newOptions, $options)
    {
        $params = [];
        if (isset($options['routeParameters'])) {
            $params = $options['routeParameters'];
        }
        $cacheKey   = null;
        $hasInCache = false;
        $uri        = null;
        if ($this->cache) {
            $cacheKey = $this->getCacheKey('route_uri', $options['route'] . ($params ? serialize($params) : ''));
            if ($this->cache->contains($cacheKey)) {
                $uri        = $this->cache->fetch($cacheKey);
                $hasInCache = true;
            }
        }
        if (!$hasInCache) {
            $uri = $this->router->generate($options['route'], $params, !empty($options['routeAbsolute']));
            if ($this->cache) {
                $this->cache->save($cacheKey, $uri);
            }
        }

        $newOptions['uri'] = $uri;
        $newOptions['extras']['routes'] = [$options['route']];
        $newOptions['extras']['routesParameters'] = [$options['route'] => $params];
    }

    /**
     * Get route information based on MenuItem options
     *
     * @param  array         $options
     * @return array|boolean
     *
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    protected function getRouteInfo(array $options)
    {
        $key = null;
        $cacheKey = null;
        $hasInCache = false;
        if (array_key_exists('route', $options)) {
            if ($this->cache) {
                $cacheKey = $this->getCacheKey('route_acl', $options['route']);
                if ($this->cache->contains($cacheKey)) {
                    $key = $this->cache->fetch($cacheKey);
                    $hasInCache = true;
                }
            }
            if (!$hasInCache) {
                $key = $this->getRouteInfoByRouteName($options['route']);
            }
        } elseif (array_key_exists('uri', $options)) {
            if ($this->cache) {
                $cacheKey = $this->getCacheKey('uri_acl', $options['uri']);
                if ($this->cache->contains($cacheKey)) {
                    $key = $this->cache->fetch($cacheKey);
                    $hasInCache = true;
                }
            }
            if (!$hasInCache) {
                $key = $this->getRouteInfoByUri($options['uri']);
            }
        }

        if ($this->cache && $cacheKey && !$hasInCache) {
            $this->cache->save($cacheKey, $key);
        }

        $info = explode(self::CONTROLLER_ACTION_DELIMITER, $key);
        if (count($info) == 2) {
            return [
                'controller' => $info[0],
                'action' => $info[1],
                'key' => $key
            ];
        } else {
            return false;
        }
    }

    /**
     * Get route info by route name
     *
     * @param $routeName
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
     * @param  string      $uri
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
     * @param  string $space
     * @param  string $value
     * @return string
     */
    protected function getCacheKey($space, $value)
    {
        return md5($space . ':' . $value);
    }

    /**
     * @param array $options
     * @return bool
     */
    protected function alreadyDenied(array $options)
    {
        return array_key_exists('extras', $options) && array_key_exists('isAllowed', $options['extras']) &&
        ($options['extras']['isAllowed'] === false);
    }

    /**
     * @param array $options
     * @return string
     */
    private function getGlobalCacheKey(array $options)
    {
        $securityFacade = $this->securityFacadeLink->getService();

        $data = [
            $this->hideAllForNotLoggedInUsers,
            $securityFacade->hasLoggedUser(),
            $this->getOptionIfExist('route', $options),
            $this->getOptionIfExist('routeParameters', $options),
            $this->getOptionIfExist('uri', $options),
            $this->getOptionIfExist('check_access', $options) === false,
            $securityFacade->getToken() !== null,
            $this->getOptionIfExist('extras', $options) && array_key_exists(self::ACL_POLICY_KEY, $options['extras']),
            $this->getOptionIfExist('extras', $options) && !empty($options['extras']['show_non_authorized']),
            $this->getOptionIfExist(self::ACL_RESOURCE_ID_KEY, $options),
        ];

        return $this->getCacheKey('global', serialize($data));
    }

    /**
     * @param string $key
     * @param array  $options
     * @return mixed|null
     */
    private function getOptionIfExist($key, array $options)
    {
        return array_key_exists($key, $options) ? $options[$key] : null;
    }
}
