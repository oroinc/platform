<?php

namespace Oro\Bundle\NavigationBundle\Menu;

use Knp\Menu\Factory;
use Knp\Menu\ItemInterface;
use Oro\Bundle\SecurityBundle\Authentication\TokenAccessorInterface;
use Oro\Bundle\SecurityBundle\Authorization\ClassAuthorizationChecker;
use Psr\Log\LoggerInterface;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Symfony\Component\Routing\Router;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

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

    /** @var Router */
    private $router;

    /** @var AuthorizationCheckerInterface */
    private $authorizationChecker;

    /** @var ClassAuthorizationChecker */
    private $classAuthorizationChecker;

    /** @var TokenAccessorInterface */
    private $tokenAccessor;

    /** @var LoggerInterface */
    private $logger;

    /** @var array */
    private $existingACLChecks = [];

    /** @var array */
    private $declaredRoutes = [];

    /**
     * @param Router $router
     * @param AuthorizationCheckerInterface $authorizationChecker
     * @param ClassAuthorizationChecker $classAuthorizationChecker
     * @param TokenAccessorInterface $tokenAccessor
     * @param LoggerInterface $logger
     */
    public function __construct(
        Router $router,
        AuthorizationCheckerInterface $authorizationChecker,
        ClassAuthorizationChecker $classAuthorizationChecker,
        TokenAccessorInterface $tokenAccessor,
        LoggerInterface $logger
    ) {
        $this->router = $router;
        $this->authorizationChecker = $authorizationChecker;
        $this->classAuthorizationChecker = $classAuthorizationChecker;
        $this->tokenAccessor = $tokenAccessor;
        $this->logger = $logger;
    }

    /**
     * {@inheritdoc}
     */
    public function buildItem(ItemInterface $item, array $options)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function buildOptions(array $options = [])
    {
        if ($this->alreadyDenied($options)) {
            return $options;
        }

        $options['extras']['isAllowed'] = self::DEFAULT_ACL_POLICY;

        if ($this->getOptionValue($options, ['check_access']) === false) {
            return $options;
        }

        if ($this->skipAccessCheck($options)) {
            $isAllowed = (bool) $this->getOptionValue($options, ['extras', 'show_non_authorized'], false);
            $options['extras']['isAllowed'] = $isAllowed;

            return $options;
        }

        if (null === $this->tokenAccessor->getToken()) {
            return $options;
        }

        if (array_key_exists(self::ACL_RESOURCE_ID_KEY, $options['extras'])) {
            $options['extras']['isAllowed'] = $this->isGranted($options);

            return $options;
        }

        $isAllowed = $options['extras']['isAllowed'];
        $options['extras']['isAllowed'] = $this->getOptionValue($options, ['extras', self::ACL_POLICY_KEY], $isAllowed);
        $options['extras']['isAllowed'] = $this->isRouteAvailable($options);

        return $options;
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
     *
     * @return bool
     */
    private function skipAccessCheck(array $options)
    {
        return !$this->getOptionValue($options, ['check_access_not_logged_in'])
        && !$this->tokenAccessor->hasUser();
    }

    /**
     * @param array $options
     *
     * @return bool
     */
    private function isGranted(array $options)
    {
        $aclResourceId = $this->getOptionValue($options, ['extras', self::ACL_RESOURCE_ID_KEY]);
        if (array_key_exists($aclResourceId, $this->existingACLChecks)) {
            return $this->existingACLChecks[$aclResourceId];
        }

        $isAllowed = $this->authorizationChecker->isGranted($aclResourceId);
        $this->existingACLChecks[$aclResourceId] = $isAllowed;

        return $isAllowed;
    }

    /**
     * @param array $options
     *
     * @return bool
     */
    private function isRouteAvailable(array $options)
    {
        $routeInfo = $this->getRouteInfo($options);
        if (!$routeInfo) {
            return $this->getOptionValue($options, ['extras', 'isAllowed']);
        }

        if (array_key_exists($routeInfo['key'], $this->existingACLChecks)) {
            return $this->existingACLChecks[$routeInfo['key']];
        }

        $isAllowed = $this->classAuthorizationChecker
            ->isClassMethodGranted($routeInfo['controller'], $routeInfo['action']);
        $this->existingACLChecks[$routeInfo['key']] = $isAllowed;

        return $isAllowed;
    }

    /**
     * Get route information based on MenuItem options
     *
     * @param array $options
     *
     * @return array
     */
    private function getRouteInfo(array $options)
    {
        $key = null;
        if (array_key_exists('route', $options)) {
            $route = $this->getRouteDefaults($options['route']);

            if (array_key_exists(self::ROUTE_CONTROLLER_KEY, $route)) {
                $key = $route[self::ROUTE_CONTROLLER_KEY];
            }
        } elseif (array_key_exists('uri', $options)) {
            try {
                $routeInfo = $this->router->match($options['uri']);

                $key = $routeInfo[self::ROUTE_CONTROLLER_KEY];
            } catch (ResourceNotFoundException $e) {
                $this->logger->debug($e->getMessage(), ['pathinfo' => $options['uri']]);
            }
        }

        if (!$key) {
            return [];
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
     * @param string $routeName
     *
     * @return array
     */
    private function getRouteDefaults($routeName)
    {
        if (!$this->declaredRoutes) {
            $generator = $this->router->getGenerator();
            $reflectionClass = new \ReflectionClass($generator);

            $property = $reflectionClass->getProperty('declaredRoutes');
            $property->setAccessible(true);

            $declaredRoutes = $property->getValue($generator);

            $this->declaredRoutes = $declaredRoutes;
        }

        if (array_key_exists($routeName, $this->declaredRoutes) && isset($this->declaredRoutes[$routeName][1])) {
            return $this->declaredRoutes[$routeName][1];
        }

        return [];
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
