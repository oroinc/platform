<?php

namespace Oro\Bundle\SyncBundle\Tests\Unit\Authentication\Provider;

use Oro\Bundle\SyncBundle\Authentication\Provider\WebsocketAuthenticationByTicketProvider;
use Oro\Bundle\SyncBundle\Authentication\Ticket\InMemoryAnonymousTicket;
use Oro\Bundle\SyncBundle\Authentication\Ticket\TicketDigestGenerator\TicketDigestGeneratorInterface;
use Oro\Bundle\SyncBundle\Security\Token\AnonymousTicketToken;
use Oro\Bundle\SyncBundle\Security\Token\TicketToken;
use Oro\Bundle\UserBundle\Entity\Role;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\UserBundle\Tests\Unit\Stub\UserStub;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\UriInterface;
use Ratchet\ConnectionInterface;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Security\Core\User\UserProviderInterface;

class WebsocketAuthenticationByTicketProviderTest extends \PHPUnit\Framework\TestCase
{
    private const WAMP_SESSION_ID = 'sampleSessionId';
    private const USERNAME = 'sampleUsername';
    private const NONCE = 'sampleNonce';
    private const SECRET = 'sampleSecret';
    private const TICKET_TTL = 300;

    private string $providerKey;
    private WebsocketAuthenticationByTicketProvider $websocketAuthenticationByTicketProvider;
    private TicketDigestGeneratorInterface|\PHPUnit\Framework\MockObject\MockObject $ticketDigestGenerator;
    private UserProviderInterface|\PHPUnit\Framework\MockObject\MockObject $userProvider;

    #[\Override]
    protected function setUp(): void
    {
        $this->ticketDigestGenerator = $this->createMock(TicketDigestGeneratorInterface::class);
        $this->userProvider = $this->createMock(UserProviderInterface::class);
        $this->providerKey = 'sample-provider-key';
        $this->websocketAuthenticationByTicketProvider = new WebsocketAuthenticationByTicketProvider(
            $this->ticketDigestGenerator,
            $this->userProvider,
            $this->providerKey,
            self::SECRET,
            self::TICKET_TTL
        );
    }

    public function testAuthenticateUser(): void
    {
        $created = $this->getDate();
        $userPassword = 'sampleUserPassword';
        $ticketId = $this->ticketDigestGenerator->generateDigest(self::NONCE, $created, $userPassword);
        $ticket = base64_encode(implode(';', [$ticketId, self::USERNAME, self::NONCE, $created]));
        $connection = $this->getConnection($ticket);
        $user = (new UserStub())->setPassword($userPassword);

        $usernamePasswordToken = new TicketToken($user, $this->providerKey);
        $usernamePasswordToken->setAttributes([
            'ticketId' => $ticketId
        ]);
        $this->userProvider->expects(self::once())
            ->method('loadUserByIdentifier')
            ->with(self::USERNAME)
            ->willReturn($user);
        $this->userProvider->expects(self::once())
            ->method('refreshUser')
            ->with($user)
            ->willReturn($user);

        $actualToken = $this->websocketAuthenticationByTicketProvider->authenticate($connection);

        self::assertEquals($usernamePasswordToken, $actualToken);
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
        $connection->WAMP = (object)['sessionId' => self::WAMP_SESSION_ID];

        return $connection;
    }

    public function testAuthenticateTokenCreatedDateInFuture(): void
    {
        $created = $this->getDateInFuture();
        $userPassword = 'sampleUserPassword';
        $ticketId = $this->ticketDigestGenerator->generateDigest(self::NONCE, $created, $userPassword);
        $ticket = base64_encode(implode(';', [$ticketId, self::USERNAME, self::NONCE, $created]));
        $connection = $this->getConnection($ticket);

        $user = new User();
        $user->setUsername(self::USERNAME);

        $this->expectException(BadCredentialsException::class);
        $this->expectExceptionMessage(
            sprintf(
                'Ticket "%s" for "%s" is not valid, because token creation date "%s" is in future',
                $ticketId,
                method_exists($user, 'getUserIdentifier') ? $user->getUserIdentifier() : $user->getUserIdentifier(),
                $created
            )
        );

        $this->websocketAuthenticationByTicketProvider->authenticate($connection);
    }

    public function testAuthenticateTokenCreatedDateInFutureLessThan30Sec(): void
    {
        $created = $this->getDateInFuture('now +29 sec');
        $userPassword = 'sampleUserPassword';
        $ticketId = $this->ticketDigestGenerator->generateDigest(self::NONCE, $created, $userPassword);
        $ticket = base64_encode(implode(';', [$ticketId, self::USERNAME, self::NONCE, $created]));
        $connection = $this->getConnection($ticket);
        $userRoles = [new Role('sampleRole')];
        $user = (new UserStub())
            ->setPassword($userPassword)
            ->setUserRoles($userRoles);
        $this->userProvider->expects(self::once())
            ->method('loadUserByIdentifier')
            ->with(self::USERNAME)
            ->willReturn($user);
        $this->userProvider->expects(self::once())
            ->method('refreshUser')
            ->with($user)
            ->willReturn($user);
        $expectedToken = new TicketToken($user, $this->providerKey, $userRoles);
        $expectedToken->setAttribute('ticketId', $ticketId);
        $actualToken = $this->websocketAuthenticationByTicketProvider->authenticate($connection);

        self::assertEquals($expectedToken, $actualToken);
    }

    public function testAuthenticateTokenExpired(): void
    {
        $created = $this->getDateInPast();
        $userPassword = 'sampleUserPassword';
        $ticketId = $this->ticketDigestGenerator->generateDigest(self::NONCE, $created, $userPassword);
        $ticket = base64_encode(implode(';', [$ticketId, self::USERNAME, self::NONCE, $created]));
        $connection = $this->getConnection($ticket);

        $this->expectException(BadCredentialsException::class);
        $this->expectExceptionMessage(
            sprintf(
                'Ticket "%s" for "%s" is expired',
                $ticketId,
                self::USERNAME
            )
        );

        $this->websocketAuthenticationByTicketProvider->authenticate($connection);
    }

    public function testAuthenticateUserNotFound(): void
    {
        $created = $this->getDate();
        $userPassword = 'sampleUserPassword';
        $ticketId = $this->ticketDigestGenerator->generateDigest(self::NONCE, $created, $userPassword);
        $ticket = base64_encode(implode(';', [$ticketId, self::USERNAME, self::NONCE, $created]));
        $connection = $this->getConnection($ticket);


        $this->userProvider->expects(self::once())
            ->method('loadUserByIdentifier')
            ->with(self::USERNAME)
            ->willThrowException(new UserNotFoundException());

        $this->expectException(BadCredentialsException::class);
        $this->expectExceptionMessage(
            sprintf(
                'Ticket "%s" for "%s" is not valid - user was not found.',
                $ticketId,
                self::USERNAME
            )
        );

        $this->websocketAuthenticationByTicketProvider->authenticate($connection);
    }

    public function testAuthenticateDigestInvalid(): void
    {
        $created = $this->getDate();
        $userPassword = 'sampleUserPassword';
        $invalidTicketDigest = 'ticketDigestInvalidddd';
        $ticket = base64_encode(implode(';', [$invalidTicketDigest, self::USERNAME, self::NONCE, $created]));
        $connection = $this->getConnection($ticket);

        $user = (new UserStub())->setPassword($userPassword);
        $this->userProvider->expects(self::once())
            ->method('loadUserByIdentifier')
            ->with(self::USERNAME)
            ->willReturn($user);
        $this->ticketDigestGenerator->expects(self::exactly(2))
            ->method('generateDigest')
            ->willReturn($this->ticketDigestGenerator->generateDigest(self::NONCE, $created, $userPassword));
        $this->expectException(BadCredentialsException::class);
        $this->expectExceptionMessage(
            sprintf(
                'Ticket "%s" for "%s" is not valid - invalid digest.',
                $invalidTicketDigest,
                self::USERNAME
            )
        );

        $this->websocketAuthenticationByTicketProvider->authenticate($connection);
    }

    public function testAuthenticateAnonymous(): void
    {
        $created = $this->getDate();
        $userPassword = 'sampleUserPassword';
        $ticketId = $this->ticketDigestGenerator->generateDigest(self::NONCE, $created, $userPassword);
        $ticket = base64_encode(implode(';', [$ticketId, self::USERNAME, self::NONCE, $created]));
        $connection = $this->getConnection($ticket);

        $user = (new UserStub())->setPassword($userPassword);
        $this->userProvider->expects(self::once())
            ->method('loadUserByIdentifier')
            ->with(self::USERNAME)
            ->willReturn($user);

        $this->ticketDigestGenerator->expects(self::once())
            ->method('generateDigest')
            ->with(self::NONCE, $created, self::SECRET)
            ->willReturn($ticketId);

        $expectedToken = new AnonymousTicketToken(
            $ticketId,
            new InMemoryAnonymousTicket(sprintf('anonymous-%s', $connection->WAMP->sessionId))
        );
        $expectedToken->setAttribute('ticketId', $ticketId);
        $actualToken = $this->websocketAuthenticationByTicketProvider->authenticate($connection);

        self::assertEquals($expectedToken, $actualToken);
    }

    private function getDate(): string
    {
        return date('c');
    }

    private function getDateInFuture(string $future = 'now +1 day'): string
    {
        return (new \DateTime($future))->format('c');
    }

    private function getDateInPast(): string
    {
        return (new \DateTime('now -301 seconds'))->format('c');
    }
}
