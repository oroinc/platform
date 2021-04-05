<?php

namespace Oro\Bundle\SecurityBundle\Http\Firewall;

use Oro\Bundle\NavigationBundle\Event\ResponseHashnavListener;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\AuthenticationTrustResolverInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Http\Authorization\AccessDeniedHandlerInterface;
use Symfony\Component\Security\Http\EntryPoint\AuthenticationEntryPointInterface;
use Symfony\Component\Security\Http\Firewall\ExceptionListener as BaseExceptionListener;
use Symfony\Component\Security\Http\HttpUtils;

/**
 * Sets redirect target path to the session for certain cases
 */
class ExceptionListener extends BaseExceptionListener
{
    private string $providerKey;

    private array $excludedRoutes = [];

    /**
     * {@inheritdoc}
     */
    public function __construct(
        TokenStorageInterface $tokenStorage,
        AuthenticationTrustResolverInterface $trustResolver,
        HttpUtils $httpUtils,
        $providerKey,
        AuthenticationEntryPointInterface $authenticationEntryPoint = null,
        $errorPage = null,
        AccessDeniedHandlerInterface $accessDeniedHandler = null,
        LoggerInterface $logger = null,
        $stateless = false
    ) {
        parent::__construct(
            $tokenStorage,
            $trustResolver,
            $httpUtils,
            $providerKey,
            $authenticationEntryPoint,
            $errorPage,
            $accessDeniedHandler,
            $logger,
            $stateless
        );
        $this->providerKey = $providerKey;
    }

    public function setExcludedRoutes(array $excludedRoutes): void
    {
        $this->excludedRoutes = $excludedRoutes;
    }

    /**
     * {@inheritdoc}
     */
    protected function setTargetPath(Request $request)
    {
        if (!$request->hasSession() ||
            !$request->isMethodSafe() ||
            ($request->isXmlHttpRequest() && !$request->headers->get(ResponseHashnavListener::HASH_NAVIGATION_HEADER))
            || $this->isExcludedRoute($request)
        ) {
            return;
        }

        $request->getSession()->set('_security.'.$this->providerKey.'.target_path', $request->getUri());
    }

    private function isExcludedRoute(Request $request): bool
    {
        if (empty($this->excludedRoutes) || !$request->attributes->has('_route')) {
            return false;
        }

        return in_array($request->attributes->get('_route'), $this->excludedRoutes, true);
    }
}
