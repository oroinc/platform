<?php

namespace Oro\Bundle\SyncBundle\Authentication\Provider;

use Gos\Bundle\WebSocketBundle\Client\Auth\WebsocketAuthenticationProviderInterface;
use Oro\Bundle\SyncBundle\Security\Token\AnonymousTicketToken;
use Psr\Http\Message\RequestInterface;
use Ratchet\ConnectionInterface;
use Symfony\Component\Security\Core\Authentication\Provider\AuthenticationProviderInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken as Token;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;

/**
 * Authenticates user by ticket in websocket connection.
 */
class WebsocketAuthenticationByTicketProvider implements WebsocketAuthenticationProviderInterface
{
    /**
     * @var AuthenticationProviderInterface
     */
    private $ticketAuthenticationProvider;

    /**
     * @var string
     */
    private $providerKey;

    public function __construct(
        AuthenticationProviderInterface $ticketAuthenticationProvider,
        string $providerKey
    ) {
        $this->ticketAuthenticationProvider = $ticketAuthenticationProvider;
        $this->providerKey = $providerKey;
    }

    /**
     * {@inheritDoc}
     *
     * @throws BadCredentialsException
     */
    public function authenticate(ConnectionInterface $connection): TokenInterface
    {
        $ticket = $this->getTicketFromConnection($connection);
        $token = $this->createTokenFromTicket($ticket);

        $ticketToken = $this->ticketAuthenticationProvider->authenticate($token);
        if ($ticketToken instanceof AnonymousTicketToken) {
            $ticketToken->setUser(sprintf('anonymous-%s', $connection->WAMP->sessionId));
        }

        return $ticketToken;
    }

    /**
     * @throws BadCredentialsException
     */
    private function getTicketFromConnection(ConnectionInterface $connection): string
    {
        if (!isset($connection->httpRequest)) {
            throw new \InvalidArgumentException('WebSocket request was not found in the connection object');
        }

        /** @var RequestInterface $request */
        $request = $connection->httpRequest;

        // Try to find the ticket in requested URL.
        $requestUri = $request->getUri();
        parse_str($requestUri->getQuery(), $query);

        if (isset($query['ticket'])) {
            $ticket = base64_decode((string)$query['ticket']);
        }

        if (empty($ticket) || \substr_count($ticket, ';') < 3) {
            throw new BadCredentialsException('Authentication ticket has invalid format');
        }

        return $ticket;
    }

    private function createTokenFromTicket(string $ticket): Token
    {
        [$ticketId, $username, $nonce, $created] = explode(';', $ticket);

        $token = new Token($username, $ticketId, $this->providerKey);
        $token->setAttributes(['nonce' => $nonce, 'created' => $created]);

        return $token;
    }
}
