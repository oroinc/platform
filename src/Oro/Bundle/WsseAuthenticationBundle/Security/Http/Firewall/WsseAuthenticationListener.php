<?php

namespace Oro\Bundle\WsseAuthenticationBundle\Security\Http\Firewall;

use Oro\Bundle\WsseAuthenticationBundle\Security\WsseTokenFactoryInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\Security\Core\Authentication\AuthenticationManagerInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\EntryPoint\AuthenticationEntryPointInterface;
use Symfony\Component\Security\Http\Firewall\ListenerInterface;

/**
 * Firewall listener for WSSE authentication.
 */
class WsseAuthenticationListener implements ListenerInterface
{
    /** @var TokenStorageInterface */
    private $tokenStorage;

    /** @var AuthenticationManagerInterface */
    private $authenticationManager;

    /** @var WsseTokenFactoryInterface */
    private $wsseTokenFactory;

    /** @var AuthenticationEntryPointInterface */
    private $authenticationEntryPoint;

    /** @var string */
    private $providerKey;

    /**
     * @param TokenStorageInterface $tokenStorage
     * @param AuthenticationManagerInterface $authenticationManager
     * @param WsseTokenFactoryInterface $wsseTokenFactory
     * @param AuthenticationEntryPointInterface $authenticationEntryPoint
     * @param string $providerKey The unique id of the firewall
     */
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

    /**
     * {@inheritdoc}
     */
    public function handle(GetResponseEvent $event): void
    {
        $request = $event->getRequest();
        if (!$request->headers->has('X-WSSE')) {
            return;
        }

        $wsseHeaderData = $this->getHeaderData((string) $request->headers->get('X-WSSE'));
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

    /**
     * @param string $wsseHeader
     * @param string $key
     *
     * @return string
     */
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
