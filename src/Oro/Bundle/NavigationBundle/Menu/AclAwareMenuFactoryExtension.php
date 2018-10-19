<?php

namespace Oro\Bundle\NavigationBundle\Menu;

use Knp\Menu\Factory;
use Knp\Menu\ItemInterface;
use Oro\Bundle\SecurityBundle\Authentication\TokenAccessorInterface;
use Oro\Bundle\SecurityBundle\Authorization\ClassAuthorizationChecker;
use Psr\Log\LoggerInterface;
use Symfony\Component\Routing\Exception\ExceptionInterface as RoutingException;
use Symfony\Component\Routing\Router;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

/**
 * Disallows menu items that related to routes disabled by ACL.
 */
class AclAwareMenuFactoryExtension implements Factory\ExtensionInterface
{
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
    private $existingAclChecks = [];

    /** @var array */
    private $declaredRoutes = [];

    /**
     * @param Router                        $router
     * @param AuthorizationCheckerInterface $authorizationChecker
     * @param ClassAuthorizationChecker     $classAuthorizationChecker
     * @param TokenAccessorInterface        $tokenAccessor
     * @param LoggerInterface               $logger
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
        if ($this->getExtraOption($options, 'isAllowed') === false) {
            return $options;
        }

        $options['extras']['isAllowed'] = true;

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

        $aclResourceId = $this->getExtraOption($options, 'acl_resource_id');
        if ($aclResourceId) {
            $options['extras']['isAllowed'] = $this->isGranted($aclResourceId);

            return $options;
        }

        $options['extras']['isAllowed'] = $this->isRouteAvailable(
            $options,
            $this->getExtraOption($options, 'acl_policy', $options['extras']['isAllowed'])
        );

        return $options;
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
        if (array_key_exists($aclResourceId, $this->existingAclChecks)) {
            return $this->existingAclChecks[$aclResourceId];
        }

        $isAllowed = $this->authorizationChecker->isGranted($aclResourceId);
        $this->existingAclChecks[$aclResourceId] = $isAllowed;

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

        if (array_key_exists($controller, $this->existingAclChecks)) {
            return $this->existingAclChecks[$controller];
        }

        $parts = explode('::', $controller);
        if (count($parts) !== 2) {
            return $defaultValue;
        }

        $isAllowed = $this->classAuthorizationChecker->isClassMethodGranted($parts[0], $parts[1]);
        $this->existingAclChecks[$controller] = $isAllowed;

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

        if (!empty($this->declaredRoutes[$routeName][1]['_controller'])) {
            return $this->declaredRoutes[$routeName][1]['_controller'];
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

                return $route['_controller'];
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
