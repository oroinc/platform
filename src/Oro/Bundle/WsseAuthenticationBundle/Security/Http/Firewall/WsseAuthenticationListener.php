<?php

namespace Oro\Bundle\WsseAuthenticationBundle\Security\Http\Firewall;

use Oro\Bundle\WsseAuthenticationBundle\Security\WsseTokenFactoryInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\Security\Core\Authentication\AuthenticationManagerInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\EntryPoint\AuthenticationEntryPointInterface;

/**
 * Firewall listener for WSSE authentication.
 */
class WsseAuthenticationListener
{
    private TokenStorageInterface $tokenStorage;

    private AuthenticationManagerInterface $authenticationManager;

    private WsseTokenFactoryInterface $wsseTokenFactory;

    private AuthenticationEntryPointInterface $authenticationEntryPoint;

    /**
     * @var string The unique id of the firewall
     */
    private string $providerKey;

    public function __construct(
        TokenStorageInterface $tokenStorage,
        AuthenticationManagerInterface $authenticationManager,
        WsseTokenFactoryInterface $wsseTokenFactory,
        AuthenticationEntryPointInterface $authenticationEntryPoint,
        string $providerKey
    ) {
        $this->tokenStorage = $tokenStorage;
        $this->authenticationManager = $authenticationManager;
        $this->wsseTokenFactory = $wsseTokenFactory;
        $this->providerKey = $providerKey;
        $this->authenticationEntryPoint = $authenticationEntryPoint;
    }

    public function __invoke(RequestEvent $event): void
    {
        $request = $event->getRequest();
        if (!$request->headers->has('X-WSSE')) {
            return;
        }

        $wsseHeaderData = $this->getHeaderData((string)$request->headers->get('X-WSSE'));
        if ($wsseHeaderData) {
            $token = $this->wsseTokenFactory->create(
                $wsseHeaderData['Username'],
                $wsseHeaderData['PasswordDigest'],
                $this->providerKey
            );

            $token->setAttribute('nonce', $wsseHeaderData['Nonce']);
            $token->setAttribute('created', $wsseHeaderData['Created']);

            try {
                $returnValue = $this->authenticationManager->authenticate($token);

                if ($returnValue instanceof TokenInterface) {
                    $this->tokenStorage->setToken($returnValue);
                    return;
                }

                if ($returnValue instanceof Response) {
                    $event->setResponse($returnValue);
                    return;
                }
            } catch (AuthenticationException $e) {
                $event->setResponse($this->authenticationEntryPoint->start($request, $e));
            }
        }
    }

    private function parseValue(string $wsseHeader, string $key): ?string
    {
        preg_match('/' . $key . '="([^"]+)"/', $wsseHeader, $matches);

        return $matches[1] ?? null;
    }

    /**
     * If Username, PasswordDigest, Nonce and Created are set then it returns their value,
     * otherwise the method returns empty array.
     */
    private function getHeaderData(string $wsseHeader): array
    {
        $result = [];
        foreach (['Username', 'PasswordDigest', 'Nonce', 'Created'] as $key) {
            if ($value = $this->parseValue($wsseHeader, $key)) {
                $result[$key] = $value;
            }
        }

        return count($result) === 4 ? $result : [];
    }
}
