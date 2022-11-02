<?php

namespace Oro\Bundle\SearchBundle\Tests\Unit\Security;

use Oro\Bundle\SearchBundle\Security\SecurityProvider;
use Oro\Bundle\SecurityBundle\Metadata\EntitySecurityMetadataProvider;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class SecurityProviderTest extends \PHPUnit\Framework\TestCase
{
    /** @var AuthorizationCheckerInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $authorizationChecker;

    /** @var EntitySecurityMetadataProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $entitySecurityMetadataProvider;

    /** @var SecurityProvider */
    private $provider;

    protected function setUp(): void
    {
        $this->authorizationChecker = $this->createMock(AuthorizationCheckerInterface::class);
        $this->entitySecurityMetadataProvider = $this->createMock(EntitySecurityMetadataProvider::class);

        $this->provider = new SecurityProvider(
            $this->authorizationChecker,
            $this->entitySecurityMetadataProvider
        );
    }

    public function testIisProtectedEntity()
    {
        $this->entitySecurityMetadataProvider->expects($this->once())
            ->method('isProtectedEntity')
            ->with('someClass')
            ->willReturn(true);
        $this->provider->isProtectedEntity('someClass');
    }

    public function testIsGranted()
    {
        $this->authorizationChecker->expects($this->once())
            ->method('isGranted')
            ->with('VIEW', 'someClass')
            ->willReturn(true);
        $this->provider->isGranted('VIEW', 'someClass');
    }
}
