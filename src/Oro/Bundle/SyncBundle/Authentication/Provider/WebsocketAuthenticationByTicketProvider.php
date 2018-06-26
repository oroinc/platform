<?php

namespace Oro\Bundle\SyncBundle\Authentication\Provider;

use Gos\Bundle\WebSocketBundle\Client\Auth\WebsocketAuthenticationProviderInterface;
use Guzzle\Http\Message\RequestInterface;
use Oro\Bundle\SyncBundle\Security\Token\AnonymousTicketToken;
use Ratchet\ConnectionInterface;
use Symfony\Component\Security\Core\Authentication\Provider\AuthenticationProviderInterface;
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

    /**
     * @param AuthenticationProviderInterface $ticketAuthenticationProvider
     * @param string $providerKey
     */
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
    public function authenticate(ConnectionInterface $connection)
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
     * @param ConnectionInterface $connection
     *
     * @return string
     *
     * @throws BadCredentialsException
     */
    private function getTicketFromConnection(ConnectionInterface $connection): string
    {
        if (!isset($connection->WebSocket->request)) {
            throw new \InvalidArgumentException('WebSocket request was not found in the connection object');
        }

        /** @var RequestInterface $request */
        $request = $connection->WebSocket->request;

        // Try to find the ticket in requested URL.
        $requestUrl = $request->getUrl(true);
        $ticket = base64_decode((string)$requestUrl->getQuery()->get('ticket'));

        if ($ticket === false || \substr_count($ticket, ';') < 3) {
            throw new BadCredentialsException('Authentication ticket has invalid format');
        }

        return $ticket;
    }

    /**
     * @param string $ticket
     *
     * @return Token
     */
    private function createTokenFromTicket(string $ticket): Token
    {
        [$ticketId, $username, $nonce, $created] = explode(';', $ticket);

        $token = new Token($username, $ticketId, $this->providerKey);
        $token->setAttributes(['nonce' => $nonce, 'created' => $created]);

        return $token;
    }
}
