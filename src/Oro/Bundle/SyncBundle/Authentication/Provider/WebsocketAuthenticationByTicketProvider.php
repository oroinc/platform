<?php

namespace Oro\Bundle\SyncBundle\Authentication\Provider;

use Gos\Bundle\WebSocketBundle\Client\Auth\WebsocketAuthenticationProviderInterface;
use Oro\Bundle\SyncBundle\Authentication\Ticket\InMemoryAnonymousTicket;
use Oro\Bundle\SyncBundle\Authentication\Ticket\TicketDigestGenerator\TicketDigestGeneratorInterface;
use Oro\Bundle\SyncBundle\Security\Token\AnonymousTicketToken;
use Oro\Bundle\SyncBundle\Security\Token\TicketToken;
use Psr\Http\Message\RequestInterface;
use Ratchet\ConnectionInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

/**
 * Authenticates user by ticket in websocket connection.
 */
class WebsocketAuthenticationByTicketProvider implements WebsocketAuthenticationProviderInterface
{
    public function __construct(
        private TicketDigestGeneratorInterface $ticketDigestGenerator,
        private UserProviderInterface $userProvider,
        private string $providerKey,
        private string $secret,
        private int $ticketTtl
    ) {
    }

    /**
     * {@inheritDoc}
     *
     * @throws BadCredentialsException
     */
    public function authenticate(ConnectionInterface $connection): TokenInterface
    {
        $ticket = $this->getTicketFromConnection($connection);
        list($ticketId, $username, $nonce, $created) = explode(';', $ticket);
        $this->validateTokenCreatedDate($created, $ticketId, $username);

        $user = $this->loadUserByIdentifier($username, $ticketId);
        $password = $this->secret;
        if (null !== $user) {
            $password = $user->getPassword();
        }
        $expectedDigest = $this->ticketDigestGenerator->generateDigest($nonce, $created, $password);
        if ($ticketId !== $expectedDigest) {
            throw new BadCredentialsException(sprintf(
                'Ticket "%s" for "%s" is not valid - invalid digest.',
                $ticketId,
                $username
            ));
        }
        $ticketToken = null !== $user
            ? new TicketToken($user, $this->providerKey, $this->getRoles($user))
            : new AnonymousTicketToken(
                $ticketId,
                new InMemoryAnonymousTicket(sprintf('anonymous-%s', $connection->WAMP->sessionId))
            );
        $ticketToken->setAttribute('ticketId', $ticketId);

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

    private function getRoles(UserInterface $user): array
    {
        return $user instanceof \Oro\Bundle\UserBundle\Entity\UserInterface
            ? $user->getUserRoles()
            : $user->getRoles();
    }

    private function loadUserByIdentifier($username, $ticketId): ?UserInterface
    {
        $user = null;
        if (!$username) {
            return $user;
        }
        try {
            $user = $this->userProvider->loadUserByIdentifier($username);
            if (null === $user) {
                return $user;
            }
            $user = $this->userProvider->refreshUser($user);
        } catch (UserNotFoundException $exception) {
            throw new BadCredentialsException(sprintf(
                'Ticket "%s" for "%s" is not valid - user was not found.',
                $ticketId,
                $username
            ));
        }

        return $user;
    }

    private function validateTokenCreatedDate($created, $ticketId, $username): void
    {
        $createdTime = strtotime($created);
        $now = strtotime(date('c'));
        if ($createdTime > $now && $createdTime - $now > 30) {
            throw new BadCredentialsException(sprintf(
                'Ticket "%s" for "%s" is not valid, because token creation date "%s" is in future',
                $ticketId,
                $username,
                $created
            ));
        }

        if ($now - $createdTime > $this->ticketTtl) {
            throw new BadCredentialsException(sprintf(
                'Ticket "%s" for "%s" is expired',
                $ticketId,
                $username
            ));
        }
    }
}
