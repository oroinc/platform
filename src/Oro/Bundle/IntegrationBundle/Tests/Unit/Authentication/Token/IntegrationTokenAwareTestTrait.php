<?php

namespace Oro\Bundle\IntegrationBundle\Tests\Unit\Authentication\Token;

use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

trait IntegrationTokenAwareTestTrait
{
    private function getTokenStorageMock(
        int $getTokenCallsCount = 1,
        int $setTokenCallsCount = 1,
    ): MockObject|TokenStorageInterface {
        $token = $this->createMock(TokenInterface::class);
        $token->expects(self::once())
            ->method('setAttribute')
            ->with('owner_description');

        $tokenStorage = $this->createMock(TokenStorageInterface::class);
        $tokenStorage->expects(self::exactly($getTokenCallsCount))
            ->method('getToken')
            ->willReturn($token);
        $tokenStorage->expects(self::exactly($setTokenCallsCount))
            ->method('setToken');

        return $tokenStorage;
    }
}
