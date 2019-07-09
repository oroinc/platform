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
    private $realm;

    /** @var string */
    private $profile;

    /**
     * @param LoggerInterface|null $logger
     * @param string|null $realm
     * @param string $profile
     */
    public function __construct(LoggerInterface $logger = null, string $realm = null, string $profile = 'UsernameToken')
    {
        $this->logger = $logger ?? new NullLogger();
        $this->realm = $realm;
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

        $response = new Response();
        $response->headers->set(
            'WWW-Authenticate',
            sprintf(
                'WSSE realm="%s", profile="%s"',
                $this->realm,
                $this->profile
            )
        );

        $response->setStatusCode(401);

        return $response;
    }
}
