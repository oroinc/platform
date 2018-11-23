<?php

namespace Oro\Bundle\NavigationBundle\Menu;

use Knp\Menu\Factory;
use Knp\Menu\ItemInterface;

use Psr\Log\LoggerInterface;

use Symfony\Component\Routing\Exception\ExceptionInterface as RoutingException;
use Symfony\Component\Routing\Router;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

use Oro\Bundle\SecurityBundle\Authentication\TokenAccessorInterface;
use Oro\Bundle\SecurityBundle\Authorization\ClassAuthorizationChecker;

/**
 * Disallows menu items that related to routes disabled by ACL.
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

        if ($this->getOption($options, 'check_access') === false) {
            return $options;
        }

        if ($this->skipAccessCheck($options)) {
            $options['extras']['isAllowed'] = (bool)$this->getExtraOption($options, 'show_non_authorized', false);

            return $options;
        }

        if (null === $this->tokenAccessor->getToken()) {
            return $options;
        }

        $aclResourceId = $this->getExtraOption($options, self::ACL_RESOURCE_ID_KEY);
        if ($aclResourceId) {
            $options['extras']['isAllowed'] = $this->isGranted($aclResourceId);

            return $options;
        }

        $options['extras']['isAllowed'] = $this->isRouteAvailable(
            $options,
            $this->getExtraOption($options, self::ACL_POLICY_KEY, $options['extras']['isAllowed'])
        );

        return $options;
    }

    /**
     * @param array $options
     * @return bool
     */
    protected function alreadyDenied(array $options)
    {
        return $this->getExtraOption($options, 'isAllowed') === false;
    }

    /**
     * @param array $options
     *
     * @return bool
     */
    private function skipAccessCheck(array $options)
    {
        return
            !$this->getOption($options, 'check_access_not_logged_in')
            && !$this->tokenAccessor->hasUser();
    }

    /**
     * @param string $aclResourceId
     *
     * @return bool
     */
    private function isGranted($aclResourceId)
    {
        if (array_key_exists($aclResourceId, $this->existingACLChecks)) {
            return $this->existingACLChecks[$aclResourceId];
        }

        $isAllowed = $this->authorizationChecker->isGranted($aclResourceId);
        $this->existingACLChecks[$aclResourceId] = $isAllowed;

        return $isAllowed;
    }

    /**
     * @param array $options
     * @param bool  $defaultValue
     *
     * @return bool
     */
    private function isRouteAvailable(array $options, $defaultValue)
    {
        $controller = $this->getController($options);
        if (!$controller) {
            return $defaultValue;
        }

        if (array_key_exists($controller, $this->existingACLChecks)) {
            return $this->existingACLChecks[$controller];
        }

        $parts = explode(self::CONTROLLER_ACTION_DELIMITER, $controller);
        if (count($parts) !== 2) {
            return $defaultValue;
        }

        $isAllowed = $this->classAuthorizationChecker->isClassMethodGranted($parts[0], $parts[1]);
        $this->existingACLChecks[$controller] = $isAllowed;

        return $isAllowed;
    }

    /**
     * @param array $options
     *
     * @return string|null
     */
    private function getController(array $options)
    {
        if (!empty($options['route'])) {
            return $this->getControllerByRouteName($options['route']);
        }
        if (!empty($options['uri'])) {
            return $this->getControllerByUri($options['uri']);
        }

        return null;
    }

    /**
     * @param string $routeName
     *
     * @return string|null
     */
    private function getControllerByRouteName($routeName)
    {
        if (!$this->declaredRoutes) {
            $generator = $this->router->getGenerator();

            $reflectionClass = new \ReflectionClass($generator);
            $property = $reflectionClass->getProperty('declaredRoutes');
            $property->setAccessible(true);

            $this->declaredRoutes = $property->getValue($generator);
        }

        if (!empty($this->declaredRoutes[$routeName][1][self::ROUTE_CONTROLLER_KEY])) {
            return $this->declaredRoutes[$routeName][1][self::ROUTE_CONTROLLER_KEY];
        }

        return null;
    }

    /**
     * @param string $uri
     *
     * @return string|null
     */
    private function getControllerByUri($uri)
    {
        if ('#' !== $uri) {
            try {
                $route = $this->router->match($uri);

                return $route[self::ROUTE_CONTROLLER_KEY];
            } catch (RoutingException $e) {
                $this->logger->debug($e->getMessage(), ['pathinfo' => $uri]);
            }
        }

        return null;
    }

    /**
     * @param array  $options
     * @param string $key
     * @param mixed  $default
     *
     * @return mixed
     */
    private function getOption(array $options, $key, $default = null)
    {
        if (array_key_exists($key, $options)) {
            return $options[$key];
        }

        return $default;
    }

    /**
     * @param array  $options
     * @param string $key
     * @param mixed  $default
     *
     * @return mixed
     */
    private function getExtraOption(array $options, $key, $default = null)
    {
        if (array_key_exists('extras', $options)) {
            $extras = $options['extras'];
            if (array_key_exists($key, $extras)) {
                return $extras[$key];
            }
        }

        return $default;
    }
}
