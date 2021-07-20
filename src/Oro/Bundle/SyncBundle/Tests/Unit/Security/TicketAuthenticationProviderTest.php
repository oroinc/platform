<?php

namespace Oro\Bundle\SyncBundle\Tests\Unit\Security;

use Oro\Bundle\SyncBundle\Authentication\Ticket\TicketDigestGenerator\TicketDigestGeneratorInterface;
use Oro\Bundle\SyncBundle\Security\TicketAuthenticationProvider;
use Oro\Bundle\SyncBundle\Security\Token\AnonymousTicketToken;
use Oro\Bundle\SyncBundle\Security\Token\TicketToken;
use Symfony\Component\Security\Core\Authentication\Provider\AuthenticationProviderInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

class TicketAuthenticationProviderTest extends \PHPUnit\Framework\TestCase
{
    private const USERNAME = 'sampleUsername';
    private const NONCE = 'sampleNonce';
    private const TICKET_DIGEST = 'sampleTicketDigest';
    private const PROVIDER_KEY = 'sampleProviderKey';
    private const SECRET = 'sampleSecret';
    private const TICKET_TTL = 300;

    /** @var UserProviderInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $userProvider;

    /** @var TicketDigestGeneratorInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $ticketDigestGenerator;

    /** @var TicketAuthenticationProvider */
    private $ticketAuthenticationProvider;

    protected function setUp(): void
    {
        $this->userProvider = $this->createMock(UserProviderInterface::class);
        $this->ticketDigestGenerator = $this->createMock(TicketDigestGeneratorInterface::class);

        $this->ticketAuthenticationProvider = new TicketAuthenticationProvider(
            $this->ticketDigestGenerator,
            $this->userProvider,
            self::PROVIDER_KEY,
            self::SECRET,
            self::TICKET_TTL
        );
    }

    /**
     * @dataProvider supportsDataProvider
     */
    public function testSupports(TokenInterface $token, bool $expectedResult): void
    {
        self::assertSame(
            $expectedResult,
            $this->ticketAuthenticationProvider->supports($token)
        );
    }

    public function supportsDataProvider(): array
    {
        $tokenWithoutNonce = new UsernamePasswordToken(self::USERNAME, self::TICKET_DIGEST, self::PROVIDER_KEY);
        $tokenWithoutNonce->setAttributes([]);

        $tokenWithoutCreated = new UsernamePasswordToken(self::USERNAME, self::TICKET_DIGEST, self::PROVIDER_KEY);
        $tokenWithoutCreated->setAttributes(['nonce' => self::NONCE]);

        $tokenWithAnotherProviderKey = new UsernamePasswordToken(self::USERNAME, self::TICKET_DIGEST, 'anotherKey');
        $tokenWithAnotherProviderKey->setAttributes(['nonce' => self::NONCE, 'created' => $this->getDate()]);

        $token = new UsernamePasswordToken(self::USERNAME, self::TICKET_DIGEST, self::PROVIDER_KEY);
        $token->setAttributes(['nonce' => self::NONCE, 'created' => $this->getDate()]);

        return [
            'not supported token type'        => [
                'token'          => $this->createMock(TokenInterface::class),
                'expectedResult' => false
            ],
            'token without nonce'             => [
                'token'          => $tokenWithoutNonce,
                'expectedResult' => false
            ],
            'token without created'           => [
                'token'          => $tokenWithoutCreated,
                'expectedResult' => false
            ],
            'token with another provider key' => [
                'token'          => $tokenWithAnotherProviderKey,
                'expectedResult' => false
            ],
            'valid token'                     => [
                'token'          => $token,
                'expectedResult' => true
            ]
        ];
    }

    public function testAuthenticateTokenCreatedDateInFuture(): void
    {
        $created = $this->getDateInFuture();
        $token = new UsernamePasswordToken(self::USERNAME, self::TICKET_DIGEST, self::PROVIDER_KEY);
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
        $token = new UsernamePasswordToken(self::USERNAME, self::TICKET_DIGEST, self::PROVIDER_KEY);
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
        $token = new UsernamePasswordToken(self::USERNAME, self::TICKET_DIGEST, self::PROVIDER_KEY);
        $token->setAttributes(['nonce' => self::NONCE, 'created' => $this->getDate()]);

        $this->userProvider
            ->expects(self::once())
            ->method('loadUserByUsername')
            ->with(self::USERNAME)
            ->willThrowException(new UsernameNotFoundException());

        $this->expectException(BadCredentialsException::class);
        $this->expectExceptionMessage(sprintf(
            'Ticket "%s" for "%s" is not valid - user was not found.',
            self::TICKET_DIGEST,
            self::USERNAME
        ));

        $this->ticketAuthenticationProvider->authenticate($token);
    }

    public function testAuthenticateDigestInvalid(): void
    {
        $created = $this->getDate();
        $invalidTicketDigest = 'ticketDigestInvalid';
        $token = new UsernamePasswordToken('', $invalidTicketDigest, self::PROVIDER_KEY);
        $token->setAttributes(['nonce' => self::NONCE, 'created' => $created]);

        $this->userProvider
            ->expects(self::never())
            ->method('loadUserByUsername');

        $this->ticketDigestGenerator
            ->expects(self::once())
            ->method('generateDigest')
            ->with(self::NONCE, $created, self::SECRET)
            ->willReturn(self::TICKET_DIGEST);

        $this->expectException(BadCredentialsException::class);
        $this->expectExceptionMessage(sprintf(
            'Ticket "%s" for "%s" is not valid - invalid digest.',
            $invalidTicketDigest,
            ''
        ));

        $this->ticketAuthenticationProvider->authenticate($token);
    }

    public function testAuthenticateAnonymous(): void
    {
        $created = $this->getDate();
        $token = new UsernamePasswordToken('', self::TICKET_DIGEST, self::PROVIDER_KEY);
        $token->setAttributes(['nonce' => self::NONCE, 'created' => $created]);

        $this->userProvider
            ->expects(self::never())
            ->method('loadUserByUsername');

        $this->ticketDigestGenerator
            ->expects(self::once())
            ->method('generateDigest')
            ->with(self::NONCE, $created, self::SECRET)
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
        $token = new UsernamePasswordToken(self::USERNAME, self::TICKET_DIGEST, self::PROVIDER_KEY);
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

        $expectedToken = new TicketToken($user, self::TICKET_DIGEST, self::PROVIDER_KEY, $userRoles);
        $actualToken = $this->ticketAuthenticationProvider->authenticate($token);

        self::assertEquals($expectedToken, $actualToken);
    }

    private function getDate(): string
    {
        return date('c');
    }

    private function getDateInFuture(): string
    {
        return (new \DateTime('now +1 day'))->format('c');
    }

    private function getDateInPast(): string
    {
        return (new \DateTime('now -301 seconds'))->format('c');
    }
}
