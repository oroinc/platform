<?php

namespace Oro\Bundle\NavigationBundle\Menu;

use Doctrine\Common\Cache\CacheProvider;

use Knp\Menu\Factory;
use Knp\Menu\ItemInterface;

use Psr\Log\LoggerInterface;

use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

use Oro\Bundle\SecurityBundle\Authentication\TokenAccessorInterface;
use Oro\Bundle\SecurityBundle\Authorization\ClassAuthorizationChecker;

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

    /** @var RouterInterface */
    private $router;

    /** @var AuthorizationCheckerInterface */
    private $authorizationChecker;

    /** @var ClassAuthorizationChecker */
    private $classAuthorizationChecker;

    /** @var TokenAccessorInterface */
    private $tokenAccessor;

    /** @var CacheProvider */
    private $cache;

    /** @var LoggerInterface */
    private $logger;

    /** @var array */
    protected $aclCache = [];

    /** @var bool */
    protected $hideAllForNotLoggedInUsers = true;

    /**
     * @param RouterInterface               $router
     * @param AuthorizationCheckerInterface $authorizationChecker
     * @param ClassAuthorizationChecker     $classAuthorizationChecker
     * @param TokenAccessorInterface        $tokenAccessor
     */
    public function __construct(
        RouterInterface $router,
        AuthorizationCheckerInterface $authorizationChecker,
        ClassAuthorizationChecker $classAuthorizationChecker,
        TokenAccessorInterface $tokenAccessor
    ) {
        $this->router = $router;
        $this->authorizationChecker = $authorizationChecker;
        $this->classAuthorizationChecker = $classAuthorizationChecker;
        $this->tokenAccessor = $tokenAccessor;
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
        $isAllowed  = self::DEFAULT_ACL_POLICY;
        $newOptions['extras']['isAllowed'] = $isAllowed;
        $options['extras']['isAllowed'] = $isAllowed;

        if ($this->getOptionValue($options, ['check_access']) === false) {
            return;
        }

        $checkAccessNotLoggedIn = $this->getOptionValue($options, ['check_access_not_logged_in']);
        if (!$checkAccessNotLoggedIn && $this->hideAllForNotLoggedInUsers && !$this->tokenAccessor->hasUser()) {
            if ($this->getOptionValue($options, ['extras', 'show_non_authorized'])) {
                return;
            }

            $isAllowed = false;
        } elseif (null !== $this->tokenAccessor->getToken()) { // don't check access if it's CLI
            $isAllowed = $this->getOptionValue($options, ['extras', self::ACL_POLICY_KEY], $isAllowed);

            if (array_key_exists(self::ACL_RESOURCE_ID_KEY, $options['extras'])) {
                $aclResourceId = $options['extras'][self::ACL_RESOURCE_ID_KEY];
                if (array_key_exists($aclResourceId, $this->aclCache)) {
                    $isAllowed = $this->aclCache[$aclResourceId];
                } else {
                    $isAllowed = $this->authorizationChecker->isGranted($aclResourceId);
                    $this->aclCache[$aclResourceId] = $isAllowed;
                }
            } else {
                $routeInfo = $this->getRouteInfo($options);
                if ($routeInfo) {
                    if (array_key_exists($routeInfo['key'], $this->aclCache)) {
                        $isAllowed = $this->aclCache[$routeInfo['key']];
                    } else {
                        $isAllowed = $this->classAuthorizationChecker->isClassMethodGranted(
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
        $params = $this->getOptionValue($options, ['routeParameters'], []);
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
        return $this->getOptionValue($options, ['extras', 'isAllowed']) === false;
    }

    /**
     * @param array $options
     * @param array $keys
     * @param mixed $default
     *
     * @return mixed
     */
    private function getOptionValue(array $options, array $keys, $default = null)
    {
        $key = array_shift($keys);
        if (!array_key_exists($key, $options)) {
            return $default;
        }

        return $keys ? $this->getOptionValue($options[$key], $keys, $default) : $options[$key];
    }
}
