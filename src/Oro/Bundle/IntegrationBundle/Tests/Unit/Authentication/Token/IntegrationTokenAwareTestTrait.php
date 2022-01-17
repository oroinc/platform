<?php

namespace Oro\Bundle\IntegrationBundle\Tests\Unit\Authentication\Token;

use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

trait IntegrationTokenAwareTestTrait
{
    private function getTokenStorageMock(): \PHPUnit\Framework\MockObject\MockObject|TokenStorageInterface
    {
        $token = $this->createMock(TokenInterface::class);
        $token->expects(self::once())
            ->method('setAttribute')
            ->with('owner_description');

        $tokenStorage = $this->createMock(TokenStorageInterface::class);
        $tokenStorage->expects(self::once())
            ->method('getToken')
            ->willReturn($token);
        $tokenStorage->expects(self::once())
            ->method('setToken')
            ->willReturn($token);

        return $tokenStorage;
    }
}
