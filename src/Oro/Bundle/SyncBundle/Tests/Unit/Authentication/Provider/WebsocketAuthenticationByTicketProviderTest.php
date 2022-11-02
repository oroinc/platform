<?php

namespace Oro\Bundle\SyncBundle\Tests\Unit\Authentication\Provider;

use Oro\Bundle\SyncBundle\Authentication\Provider\WebsocketAuthenticationByTicketProvider;
use Oro\Bundle\SyncBundle\Security\Token\AnonymousTicketToken;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\UriInterface;
use Ratchet\ConnectionInterface;
use Symfony\Component\Security\Core\Authentication\Provider\AuthenticationProviderInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;

class WebsocketAuthenticationByTicketProviderTest extends \PHPUnit\Framework\TestCase
{
    private const WAMP_SESSION_ID = 'sampleSessionId';
    private const TICKET = 'dGlja2V0RGlnZXN0O3NhbXBsZVVzZXJuYW1lO3NhbXBsZU5vbmNlOzIwMTgtMDEtMDFUMTAwOjAwOjAwKzAwOjAw';
    private const TICKET_DIGEST = 'ticketDigest';
    private const USERNAME = 'sampleUsername';
    private const NONCE = 'sampleNonce';
    private const CREATED = '2018-01-01T100:00:00+00:00';

    /** @var AuthenticationProviderInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $ticketAuthenticationProvider;

    /** @var string */
    private $providerKey;

    /** @var WebsocketAuthenticationByTicketProvider */
    private $websocketAuthenticationByTicketProvider;

    protected function setUp(): void
    {
        $this->ticketAuthenticationProvider = $this->createMock(AuthenticationProviderInterface::class);
        $this->providerKey = 'sample-provider-key';
        $this->websocketAuthenticationByTicketProvider = new WebsocketAuthenticationByTicketProvider(
            $this->ticketAuthenticationProvider,
            $this->providerKey
        );
    }

    public function testAuthenticate(): void
    {
        $connection = $this->getConnection(self::TICKET);

        $usernamePasswordToken = new UsernamePasswordToken(self::USERNAME, self::TICKET_DIGEST, $this->providerKey);
        $usernamePasswordToken->setAttributes(['nonce' => self::NONCE, 'created' => self::CREATED]);

        $expectedToken = $this->createMock(TokenInterface::class);
        $this->ticketAuthenticationProvider->expects(self::once())
            ->method('authenticate')
            ->with($usernamePasswordToken)
            ->willReturn($expectedToken);

        $expectedToken->expects(self::never())
            ->method('setUser');

        $actualToken = $this->websocketAuthenticationByTicketProvider->authenticate($connection);

        self::assertEquals($expectedToken, $actualToken);
    }

    public function testAuthenticateAnonymous(): void
    {
        $connection = $this->getConnection(self::TICKET);

        $usernamePasswordToken = new UsernamePasswordToken(self::USERNAME, self::TICKET_DIGEST, $this->providerKey);
        $usernamePasswordToken->setAttributes(['nonce' => self::NONCE, 'created' => self::CREATED]);

        $expectedToken = $this->createMock(AnonymousTicketToken::class);
        $this->ticketAuthenticationProvider->expects(self::once())
            ->method('authenticate')
            ->with($usernamePasswordToken)
            ->willReturn($expectedToken);

        $expectedToken->expects(self::once())
            ->method('setUser')
            ->with('anonymous-'.self::WAMP_SESSION_ID);

        $actualToken = $this->websocketAuthenticationByTicketProvider->authenticate($connection);

        self::assertEquals($expectedToken, $actualToken);
    }

    /**
     * @dataProvider authenticateWithInvalidTicketDataProvider
     */
    public function testAuthenticateWithInvalidTicket(?string $ticket): void
    {
        $connection = $this->getConnection($ticket);

        $this->expectException(BadCredentialsException::class);
        $this->expectExceptionMessage('Authentication ticket has invalid format');

        $this->websocketAuthenticationByTicketProvider->authenticate($connection);
    }

    public function authenticateWithInvalidTicketDataProvider(): array
    {
        return [
            'empty ticket' => [
                'ticket' => null,
            ],
            'malformed not encoded ticket' => [
                'ticket' => 'ticketDigest;nothing',
            ],
            'malformed encoded ticket' => [
                'ticket' => 'dGlja2V0LWRpZ2VzdDtub3RoaW5n',
            ],
        ];
    }

    public function testAuthenticateWithoutWebSocketRequest(): void
    {
        $connection = $this->createMock(ConnectionInterface::class);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('WebSocket request was not found in the connection object');

        $this->websocketAuthenticationByTicketProvider->authenticate($connection);
    }

    private function getConnection(?string $ticket): ConnectionInterface
    {
        $uri = $this->createMock(UriInterface::class);
        $uri->expects(self::once())
            ->method('getQuery')
            ->willReturn('ticket=' . $ticket);

        $request = $this->createMock(RequestInterface::class);
        $request->expects(self::once())
            ->method('getUri')
            ->willReturn($uri);

        $connection = $this->createMock(ConnectionInterface::class);
        $connection->httpRequest = $request;
        $connection->WAMP = (object) ['sessionId' => self::WAMP_SESSION_ID];

        return $connection;
    }
}
