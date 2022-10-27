<?php

namespace Oro\Bundle\ActionBundle\Tests\Unit\Provider;

use Oro\Bundle\ActionBundle\Provider\CurrentApplicationProvider;
use Oro\Bundle\UserBundle\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class CurrentApplicationProviderTest extends \PHPUnit\Framework\TestCase
{
    /** @var TokenStorageInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $tokenStorage;

    /** @var CurrentApplicationProvider */
    private $provider;

    protected function setUp(): void
    {
        $this->tokenStorage = $this->createMock(TokenStorageInterface::class);

        $this->provider = new CurrentApplicationProvider($this->tokenStorage);
    }

    private function createToken(UserInterface|string $user): TokenInterface
    {
        $token = $this->createMock(TokenInterface::class);
        $token->expects($this->once())
            ->method('getUser')
            ->willReturn($user);

        return $token;
    }

    /**
     * @dataProvider isApplicationsValidDataProvider
     */
    public function testIsApplicationsValid(array $applications, ?TokenInterface $token, bool $expectedResult)
    {
        $this->tokenStorage->expects($this->any())
            ->method('getToken')
            ->willReturn($token);

        $this->assertEquals($expectedResult, $this->provider->isApplicationsValid($applications));
    }

    /**
     * @dataProvider getCurrentApplicationProvider
     */
    public function testGetCurrentApplication(?TokenInterface $token, ?string $expectedResult)
    {
        $this->tokenStorage->expects($this->any())
            ->method('getToken')
            ->willReturn($token);

        $this->assertSame($expectedResult, $this->provider->getCurrentApplication());
    }

    public function isApplicationsValidDataProvider(): array
    {
        return [
            [
                'applications' => ['default'],
                'token' => $this->createToken(new User()),
                'expectedResult' => true
            ],
            [
                'applications' => ['test'],
                'token' => $this->createToken(new User()),
                'expectedResult' => false
            ],
            [
                'applications' => ['default'],
                'token' => $this->createToken('anon.'),
                'expectedResult' => false
            ],
            [
                'applications' => ['test'],
                'token' => $this->createToken('anon.'),
                'expectedResult' => false
            ],
            [
                'applications' => ['default'],
                'token' => null,
                'expectedResult' => false
            ],
            [
                'applications' => [],
                'token' => null,
                'expectedResult' => true
            ],
        ];
    }

    public function getCurrentApplicationProvider(): array
    {
        return [
            'supported user' => [
                'token' => $this->createToken(new User()),
                'expectedResult' => 'default',
            ],
            'not supported user' => [
                'token' => $this->createToken('anon.'),
                'expectedResult' => null,
            ],
            'empty token' => [
                'token' => null,
                'expectedResult' => null,
            ],
        ];
    }
}
