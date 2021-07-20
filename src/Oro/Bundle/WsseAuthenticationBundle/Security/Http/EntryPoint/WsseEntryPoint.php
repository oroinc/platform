<?php

namespace Oro\Bundle\WsseAuthenticationBundle\Security\Http\EntryPoint;

use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\EntryPoint\AuthenticationEntryPointInterface;

/**
 * Authentication entry point for WSSE.
 */
class WsseEntryPoint implements AuthenticationEntryPointInterface
{
    /** @var LoggerInterface */
    private $logger;

    /** @var string */
    private $realmName;

    /** @var string */
    private $profile;

    public function __construct(
        ?LoggerInterface $logger = null,
        string $realmName = '',
        string $profile = 'UsernameToken'
    ) {
        $this->logger = $logger ?? new NullLogger();
        $this->realmName = $realmName;
        $this->profile = $profile;
    }

    /**
     * {@inheritdoc}
     */
    public function start(Request $request, AuthenticationException $authException = null): Response
    {
        if ($authException instanceof AuthenticationException) {
            $this->logger->warning($authException->getMessage());
        }

        $headers = ['WWW-Authenticate' => sprintf('WSSE realm="%s", profile="%s"', $this->realmName, $this->profile)];

        return new Response('', 401, $headers);
    }
}
