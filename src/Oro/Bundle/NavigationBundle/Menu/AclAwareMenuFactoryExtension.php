<?php

namespace Oro\Bundle\NavigationBundle\Menu;

use Knp\Menu\Factory;
use Knp\Menu\ItemInterface;
use Oro\Bundle\SecurityBundle\Authentication\TokenAccessorInterface;
use Oro\Bundle\SecurityBundle\Authorization\ClassAuthorizationChecker;
use Oro\Bundle\UIBundle\Provider\ControllerClassProvider;
use Psr\Log\LoggerInterface;
use Symfony\Component\Routing\Exception\ExceptionInterface as RoutingException;
use Symfony\Component\Routing\Matcher\UrlMatcherInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

/**
 * Disallows menu items that related to routes disabled by ACL.
 */
class AclAwareMenuFactoryExtension implements Factory\ExtensionInterface
{
    private UrlMatcherInterface $urlMatcher;
    private ControllerClassProvider $controllerClassProvider;
    private AuthorizationCheckerInterface $authorizationChecker;
    private ClassAuthorizationChecker $classAuthorizationChecker;
    private TokenAccessorInterface $tokenAccessor;
    private LoggerInterface $logger;
    private array $aclCheckCache = [];
    private array $controllerAclCheckCache = [];

    public function __construct(
        UrlMatcherInterface $urlMatcher,
        ControllerClassProvider $controllerClassProvider,
        AuthorizationCheckerInterface $authorizationChecker,
        ClassAuthorizationChecker $classAuthorizationChecker,
        TokenAccessorInterface $tokenAccessor,
        LoggerInterface $logger
    ) {
        $this->urlMatcher = $urlMatcher;
        $this->controllerClassProvider = $controllerClassProvider;
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
        if (isset($this->aclCheckCache[$aclResourceId])) {
            return $this->aclCheckCache[$aclResourceId];
        }

        $isAllowed = $this->authorizationChecker->isGranted($aclResourceId);
        $this->aclCheckCache[$aclResourceId] = $isAllowed;

        return $isAllowed;
    }

    private function isRouteAvailable(array $options, bool $defaultValue): bool
    {
        $controller = $this->getController($options);
        if (null === $controller) {
            return $defaultValue;
        }

        [$class, $method] = $controller;

        // invokable controller given (for example, with the single __invoke method)
        if (null === $method) {
            return $defaultValue;
        }

        if (isset($this->controllerAclCheckCache[$class][$method])) {
            return $this->controllerAclCheckCache[$class][$method];
        }

        $isAllowed = $this->classAuthorizationChecker->isClassMethodGranted($class, $method);
        $this->controllerAclCheckCache[$class][$method] = $isAllowed;

        return $isAllowed;
    }

    private function getController(array $options): ?array
    {
        if (!empty($options['route'])) {
            return $this->getControllerByRouteName($options['route']);
        }
        if (!empty($options['uri'])) {
            return $this->getControllerByUri($options['uri']);
        }

        return null;
    }

    private function getControllerByRouteName(string $routeName): ?array
    {
        return $this->controllerClassProvider->getControllers()[$routeName] ?? null;
    }

    private function getControllerByUri(string $uri): ?array
    {
        if ('#' !== $uri) {
            try {
                $route = $this->urlMatcher->match($uri);

                return explode('::', $route['_controller']);
            } catch (RoutingException $e) {
                $this->logger->debug($e->getMessage(), ['pathinfo' => $uri]);
            }
        }

        return null;
    }
}
