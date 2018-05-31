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

class ExceptionListener extends BaseExceptionListener
{
    /** @var string */
    private $providerKey;

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

    /**
     * {@inheritdoc}
     */
    protected function setTargetPath(Request $request)
    {
        if (!$request->hasSession() ||
            !$request->isMethodSafe(false) ||
            ($request->isXmlHttpRequest() && !$request->headers->get(ResponseHashnavListener::HASH_NAVIGATION_HEADER))
        ) {
            return;
        }

        $request->getSession()->set('_security.'.$this->providerKey.'.target_path', $request->getUri());
    }
}
