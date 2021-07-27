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
    private Router $router;
    private AuthorizationCheckerInterface $authorizationChecker;
    private ClassAuthorizationChecker $classAuthorizationChecker;
    private TokenAccessorInterface $tokenAccessor;
    private LoggerInterface $logger;
    private array $existingAclChecks = [];
    private array $declaredRoutes = [];

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
    public function buildItem(ItemInterface $item, array $options): void
    {
    }

    /**
     * {@inheritdoc}
     */
    public function buildOptions(array $options): array
    {
        if (isset($options['extras']['isAllowed'])) {
            return $options;
        }

        $options['extras']['isAllowed'] = true;

        if (!($options['check_access'] ?? true)) {
            return $options;
        }

        if ($this->skipAccessCheck($options)) {
            $options['extras']['isAllowed'] = (bool)($options['extras']['show_non_authorized'] ?? false);

            return $options;
        }

        if (null === $this->tokenAccessor->getToken()) {
            return $options;
        }

        $aclResourceId = $options['extras']['acl_resource_id'] ?? null;
        if ($aclResourceId) {
            $options['extras']['isAllowed'] = $this->isGranted($aclResourceId);

            return $options;
        }

        $options['extras']['isAllowed'] = $this->isRouteAvailable(
            $options,
            $options['extras']['acl_policy'] ?? $options['extras']['isAllowed']
        );

        return $options;
    }

    private function skipAccessCheck(array $options): bool
    {
        return
            !($options['check_access_not_logged_in'] ?? false)
            && !$this->tokenAccessor->hasUser();
    }

    private function isGranted(string $aclResourceId): bool
    {
        if (\array_key_exists($aclResourceId, $this->existingAclChecks)) {
            return $this->existingAclChecks[$aclResourceId];
        }

        $isAllowed = $this->authorizationChecker->isGranted($aclResourceId);
        $this->existingAclChecks[$aclResourceId] = $isAllowed;

        return $isAllowed;
    }

    private function isRouteAvailable(array $options, bool $defaultValue): bool
    {
        $controller = $this->getController($options);
        if (!$controller) {
            return $defaultValue;
        }

        if (\array_key_exists($controller, $this->existingAclChecks)) {
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

    private function getController(array $options): ?string
    {
        if (!empty($options['route'])) {
            return $this->getControllerByRouteName($options['route']);
        }
        if (!empty($options['uri'])) {
            return $this->getControllerByUri($options['uri']);
        }

        return null;
    }

    private function getControllerByRouteName(string $routeName): ?string
    {
        if (!$this->declaredRoutes) {
            $generator = $this->router->getGenerator();

            $reflectionClass = new \ReflectionClass($generator);
            $property = $reflectionClass->getProperty('compiledRoutes');
            $property->setAccessible(true);

            $this->declaredRoutes = $property->getValue($generator);
        }

        if (!empty($this->declaredRoutes[$routeName][1]['_controller'])) {
            return $this->declaredRoutes[$routeName][1]['_controller'];
        }

        return null;
    }

    private function getControllerByUri(string $uri): ?string
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
}
