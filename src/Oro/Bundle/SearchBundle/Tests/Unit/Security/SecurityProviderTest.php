<?php

namespace Oro\Bundle\SearchBundle\Tests\Unit\Security;

use Oro\Bundle\SearchBundle\Security\SecurityProvider;
use Oro\Bundle\SecurityBundle\Metadata\EntitySecurityMetadataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class SecurityProviderTest extends TestCase
{
    private AuthorizationCheckerInterface&MockObject $authorizationChecker;
    private EntitySecurityMetadataProvider&MockObject $entitySecurityMetadataProvider;
    private SecurityProvider $provider;

    #[\Override]
    protected function setUp(): void
    {
        $this->authorizationChecker = $this->createMock(AuthorizationCheckerInterface::class);
        $this->entitySecurityMetadataProvider = $this->createMock(EntitySecurityMetadataProvider::class);

        $this->provider = new SecurityProvider(
            $this->authorizationChecker,
            $this->entitySecurityMetadataProvider
        );
    }

    public function testIisProtectedEntity(): void
    {
        $this->entitySecurityMetadataProvider->expects($this->once())
            ->method('isProtectedEntity')
            ->with('someClass')
            ->willReturn(true);
        $this->provider->isProtectedEntity('someClass');
    }

    public function testIsGranted(): void
    {
        $this->authorizationChecker->expects($this->once())
            ->method('isGranted')
            ->with('VIEW', 'someClass')
            ->willReturn(true);
        $this->provider->isGranted('VIEW', 'someClass');
    }
}
