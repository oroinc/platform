<?php

namespace Oro\Bundle\SyncBundle\Tests\Unit\Security;

use Oro\Bundle\SyncBundle\Authentication\Ticket\TicketDigestGenerator\TicketDigestGeneratorInterface;
use Oro\Bundle\SyncBundle\Security\TicketAuthenticationProvider;
use Oro\Bundle\SyncBundle\Security\Token\AnonymousTicketToken;
use Oro\Bundle\SyncBundle\Security\Token\TicketToken;
use Oro\Bundle\UserBundle\Security\UserProvider;
use Symfony\Component\Security\Core\Authentication\Provider\AuthenticationProviderInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;
use Symfony\Component\Security\Core\User\UserInterface;

class TicketAuthenticationProviderTest extends \PHPUnit\Framework\TestCase
{
    private const USERNAME = 'sampleUsername';
    private const NONCE = 'sampleNonce';
    private const TICKET_DIGEST = 'sampleTicketDigest';
    /**
     * @var UserProvider|\PHPUnit\Framework\MockObject\MockObject
     */
    private $userProvider;

    /**
     * @var TicketDigestGeneratorInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $ticketDigestGenerator;

    /**
     * @var string
     */
    private $providerKey;

    /**
     * @var string
     */
    private $secret;

    /**
     * @var int
     */
    private $ticketTtl;

    /**
     * @var TicketAuthenticationProvider
     */
    private $ticketAuthenticationProvider;

    protected function setUp()
    {
        $this->userProvider = $this->createMock(UserProvider::class);
        $this->ticketDigestGenerator = $this->createMock(TicketDigestGeneratorInterface::class);
        $this->providerKey = 'sampleProdiverKey';
        $this->secret = 'sampleSecret';
        $this->ticketTtl = 300;

        $this->ticketAuthenticationProvider = new TicketAuthenticationProvider(
            $this->ticketDigestGenerator,
            $this->userProvider,
            $this->providerKey,
            $this->secret,
            $this->ticketTtl
        );
    }

    /**
     * @dataProvider authenticateTokenIsNotSupportedDataProvider
     *
     * @param TokenInterface $token
     */
    public function testAuthenticateTokenIsNotSupported(TokenInterface $token): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Token is not supported');

        $this->ticketAuthenticationProvider->authenticate($token);
    }

    /**
     * @return array
     */
    public function authenticateTokenIsNotSupportedDataProvider(): array
    {
        $tokenWithoutNonce = $this->createMock(UsernamePasswordToken::class);
        $tokenWithoutNonce
            ->method('hasAttribute')
            ->with('nonce')
            ->willReturn(false);

        $tokenWithoutCreated = $this->createMock(UsernamePasswordToken::class);
        $tokenWithoutCreated
            ->method('hasAttribute')
            ->willReturnMap([
                ['nonce', true],
                ['created', false],
            ]);

        $tokenWithAnotherProviderKey = $this->createMock(UsernamePasswordToken::class);
        $tokenWithAnotherProviderKey
            ->method('getProviderKey')
            ->willReturn('anotherProviderKey');

        $tokenWithAnotherProviderKey
            ->method('hasAttribute')
            ->willReturnMap([
                ['nonce', true],
                ['created', true],
            ]);

        return [
            'not token' => ['token' => $this->createMock(TokenInterface::class)],
            'token without nonce' => ['token' => $tokenWithoutNonce],
            'token without created' => ['token' => $tokenWithoutCreated],
            'token with another provider key' => ['token' => $tokenWithAnotherProviderKey],
        ];
    }

    public function testAuthenticateTokenCreatedDateInFuture(): void
    {
        $created = $this->getDateInFuture();
        $token = new UsernamePasswordToken(self::USERNAME, self::TICKET_DIGEST, $this->providerKey);
        $token->setAttributes(['nonce' => self::NONCE, 'created' => $created]);

        $this->expectException(BadCredentialsException::class);
        $this->expectExceptionMessage(sprintf(
            'Ticket "%s" for "%s" is not valid, because token creation date "%s" is in future',
            $token->getCredentials(),
            $token->getUsername(),
            $created
        ));

        $this->ticketAuthenticationProvider->authenticate($token);
    }

    public function testAuthenticateTokenExpired(): void
    {
        $created = $this->getDateInPast();
        $token = new UsernamePasswordToken(self::USERNAME, self::TICKET_DIGEST, $this->providerKey);
        $token->setAttributes(['nonce' => self::NONCE, 'created' => $created]);

        $this->expectException(BadCredentialsException::class);
        $this->expectExceptionMessage(sprintf(
            'Ticket "%s" for "%s" is expired',
            $token->getCredentials(),
            $token->getUsername()
        ));

        $this->ticketAuthenticationProvider->authenticate($token);
    }

    public function testAuthenticateUserNotFound(): void
    {
        $token = new UsernamePasswordToken(self::USERNAME, self::TICKET_DIGEST, $this->providerKey);
        $token->setAttributes(['nonce' => self::NONCE, 'created' => $this->getDate()]);

        $this->userProvider
            ->expects(self::once())
            ->method('loadUserByUsername')
            ->with(self::USERNAME)
            ->willThrowException(new UsernameNotFoundException());

        $this->expectException(BadCredentialsException::class);
        $this->expectExceptionMessage(sprintf(
            'Ticket "%s" for "%s" is not valid - invalid credentials',
            self::TICKET_DIGEST,
            self::USERNAME
        ));

        $this->ticketAuthenticationProvider->authenticate($token);
    }

    public function testAuthenticateDigestInvalid(): void
    {
        $created = $this->getDate();
        $invalidTicketDigest = 'ticketDigestInvalid';
        $token = new UsernamePasswordToken('', $invalidTicketDigest, $this->providerKey);
        $token->setAttributes(['nonce' => self::NONCE, 'created' => $created]);

        $this->userProvider
            ->expects(self::never())
            ->method('loadUserByUsername');

        $this->ticketDigestGenerator
            ->expects(self::once())
            ->method('generateDigest')
            ->with(self::NONCE, $created, $this->secret)
            ->willReturn(self::TICKET_DIGEST);

        $this->expectException(BadCredentialsException::class);
        $this->expectExceptionMessage(sprintf(
            'Ticket "%s" for "%s" is not valid - invalid credentials',
            $invalidTicketDigest,
            ''
        ));

        $this->ticketAuthenticationProvider->authenticate($token);
    }

    public function testAuthenticateAnonymous(): void
    {
        $created = $this->getDate();
        $token = new UsernamePasswordToken('', self::TICKET_DIGEST, $this->providerKey);
        $token->setAttributes(['nonce' => self::NONCE, 'created' => $created]);

        $this->userProvider
            ->expects(self::never())
            ->method('loadUserByUsername');

        $this->ticketDigestGenerator
            ->expects(self::once())
            ->method('generateDigest')
            ->with(self::NONCE, $created, $this->secret)
            ->willReturn(self::TICKET_DIGEST);

        $expectedToken = new AnonymousTicketToken(
            self::TICKET_DIGEST,
            AuthenticationProviderInterface::USERNAME_NONE_PROVIDED
        );
        $actualToken = $this->ticketAuthenticationProvider->authenticate($token);

        self::assertEquals($expectedToken, $actualToken);
    }

    public function testAuthenticate(): void
    {
        $created = $this->getDate();
        $token = new UsernamePasswordToken(self::USERNAME, self::TICKET_DIGEST, $this->providerKey);
        $token->setAttributes(['nonce' => self::NONCE, 'created' => $created]);

        $user = $this->createMock(UserInterface::class);

        $userPassword = 'sampleUserPassword';
        $user
            ->expects(self::once())
            ->method('getPassword')
            ->willReturn($userPassword);

        $userRoles = ['sampleRole'];
        $user
            ->expects(self::once())
            ->method('getRoles')
            ->willReturn($userRoles);

        $this->userProvider
            ->expects(self::once())
            ->method('loadUserByUsername')
            ->with(self::USERNAME)
            ->willReturn($user);

        $this->ticketDigestGenerator
            ->expects(self::once())
            ->method('generateDigest')
            ->with(self::NONCE, $created, $userPassword)
            ->willReturn(self::TICKET_DIGEST);

        $expectedToken = new TicketToken($user, self::TICKET_DIGEST, $this->providerKey, $userRoles);
        $actualToken = $this->ticketAuthenticationProvider->authenticate($token);

        self::assertEquals($expectedToken, $actualToken);
    }

    /**
     * @return string
     */
    private function getDate(): string
    {
        return date('c');
    }

    /**
     * @return string
     */
    private function getDateInFuture(): string
    {
        return (new \DateTime('now +1 day'))->format('c');
    }

    /**
     * @return string
     */
    private function getDateInPast(): string
    {
        return (new \DateTime('now -301 seconds'))->format('c');
    }
}
