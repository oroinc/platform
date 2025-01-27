<?php

namespace Oro\Bundle\WsseAuthenticationBundle\Security\Http\EntryPoint;

use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\EntryPoint\AuthenticationEntryPointInterface;

/**
 * Authentication entry point for WSSE.
 */
class WsseEntryPoint implements AuthenticationEntryPointInterface
{
    public function __construct(
        private string $env,
        private ?LoggerInterface $logger = null,
        private string $realmName = '',
        private string $profile = 'UsernameToken'
    ) {
    }

    #[\Override]
    public function start(Request $request, AuthenticationException $authException = null): Response
    {
        if ($this->env !== 'test') {
            return new Response('WSSE is deprecated', 401);
        }

        if ($authException instanceof AuthenticationException) {
            $this->logger->warning($authException->getMessage());
        }

        $headers = ['WWW-Authenticate' => sprintf('WSSE realm="%s", profile="%s"', $this->realmName, $this->profile)];

        return new Response('', 401, $headers);
    }
}
