<?php

namespace Oro\Bundle\SyncBundle\Tests\Unit\Authentication\Provider;

use Guzzle\Http\Message\RequestInterface;
use Guzzle\Http\QueryString;
use Guzzle\Http\Url;
use Oro\Bundle\SyncBundle\Authentication\Provider\WebsocketAuthenticationByTicketProvider;
use Oro\Bundle\SyncBundle\Security\Token\AnonymousTicketToken;
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

    /**
     * @var AuthenticationProviderInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $ticketAuthenticationProvider;

    /**
     * @var string
     */
    private $providerKey;

    /**
     * @var WebsocketAuthenticationByTicketProvider
     */
    private $websocketAuthenticationByTicketProvider;

    protected function setUp()
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
        $connection = $this->mockWebSocketRequest(self::TICKET);

        $usernamePasswordToken = new UsernamePasswordToken(self::USERNAME, self::TICKET_DIGEST, $this->providerKey);
        $usernamePasswordToken->setAttributes(['nonce' => self::NONCE, 'created' => self::CREATED]);

        $expectedToken = $this->createMock(TokenInterface::class);
        $this
            ->ticketAuthenticationProvider
            ->expects(self::once())
            ->method('authenticate')
            ->with($usernamePasswordToken)
            ->willReturn($expectedToken);

        $expectedToken
            ->expects(self::never())
            ->method('setUser');

        $actualToken = $this->websocketAuthenticationByTicketProvider->authenticate($connection);

        self::assertEquals($expectedToken, $actualToken);
    }

    public function testAuthenticateAnonymous(): void
    {
        $connection = $this->mockWebSocketRequest(self::TICKET);

        $usernamePasswordToken = new UsernamePasswordToken(self::USERNAME, self::TICKET_DIGEST, $this->providerKey);
        $usernamePasswordToken->setAttributes(['nonce' => self::NONCE, 'created' => self::CREATED]);

        $expectedToken = $this->createMock(AnonymousTicketToken::class);
        $this
            ->ticketAuthenticationProvider
            ->expects(self::once())
            ->method('authenticate')
            ->with($usernamePasswordToken)
            ->willReturn($expectedToken);

        $expectedToken
            ->expects(self::once())
            ->method('setUser')
            ->with('anonymous-'.self::WAMP_SESSION_ID);

        $actualToken = $this->websocketAuthenticationByTicketProvider->authenticate($connection);

        self::assertEquals($expectedToken, $actualToken);
    }

    /**
     * @dataProvider authenticateWithInvalidTicketDataProvider
     *
     * @param string|null $ticket
     */
    public function testAuthenticateWithInvalidTicket($ticket): void
    {
        $connection = $this->mockWebSocketRequest($ticket);

        $this->expectException(BadCredentialsException::class);
        $this->expectExceptionMessage('Authentication ticket has invalid format');

        $this->websocketAuthenticationByTicketProvider->authenticate($connection);
    }

    /**
     * @return array
     */
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
        /** @var ConnectionInterface $connection */
        $connection = $this->createMock(ConnectionInterface::class);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('WebSocket request was not found in the connection object');

        $this->websocketAuthenticationByTicketProvider->authenticate($connection);
    }

    /**
     * @param string|null $ticket
     *
     * @return \PHPUnit\Framework\MockObject\MockObject|ConnectionInterface
     */
    private function mockWebSocketRequest(?string $ticket)
    {
        $query = $this->createMock(QueryString::class);
        $query
            ->expects(self::once())
            ->method('get')
            ->with('ticket')
            ->willReturn($ticket);

        $url = $this->createMock(Url::class);
        $url
            ->expects(self::once())
            ->method('getQuery')
            ->willReturn($query);

        $request = $this->createMock(RequestInterface::class);
        $request
            ->expects(self::once())
            ->method('getUrl')
            ->with(true)
            ->willReturn($url);

        $connection = $this->createMock(ConnectionInterface::class);
        $connection->WebSocket = (object) ['request' => $request];
        $connection->WAMP = (object) ['sessionId' => self::WAMP_SESSION_ID];

        return $connection;
    }
}
