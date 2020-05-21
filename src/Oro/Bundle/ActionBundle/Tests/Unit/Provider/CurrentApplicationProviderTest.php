<?php

namespace Oro\Bundle\ActionBundle\Tests\Unit\Provider;

use Oro\Bundle\ActionBundle\Provider\CurrentApplicationProvider;
use Oro\Bundle\UserBundle\Entity\User;
use PHPUnit\Framework\MockObject\Matcher\Invocation;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class CurrentApplicationProviderTest extends \PHPUnit\Framework\TestCase
{
    /** @var \PHPUnit\Framework\MockObject\MockObject|TokenStorageInterface */
    private $tokenStorage;

    /** @var CurrentApplicationProvider */
    private $provider;

    protected function setUp(): void
    {
        $this->tokenStorage = $this->createMock(TokenStorageInterface::class);

        $this->provider = new CurrentApplicationProvider($this->tokenStorage);
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

    /**
     * @return array
     */
    public function isApplicationsValidDataProvider()
    {
        $user = new User();
        $otherUser = 'anon.';

        return [
            [
                'applications' => ['default'],
                'token' => $this->createToken($user),
                'expectedResult' => true
            ],
            [
                'applications' => ['test'],
                'token' => $this->createToken($user),
                'expectedResult' => false
            ],
            [
                'applications' => ['default'],
                'token' => $this->createToken($otherUser),
                'expectedResult' => false
            ],
            [
                'applications' => ['test'],
                'token' => $this->createToken($otherUser),
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

    /**
     * @return array
     */
    public function getCurrentApplicationProvider()
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

    /**
     * @param UserInterface|string $user
     * @param \PHPUnit\Framework\MockObject\Matcher\Invocation $expects
     * @return TokenInterface
     */
    protected function createToken($user, Invocation $expects = null)
    {
        $token = $this->createMock(TokenInterface::class);
        $token->expects($expects ?: $this->once())
            ->method('getUser')
            ->willReturn($user);

        return $token;
    }
}
