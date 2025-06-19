<?php

namespace Oro\Bundle\FormBundle\Tests\Unit\Autocomplete;

use Oro\Bundle\FormBundle\Autocomplete\Security;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class SecurityTest extends TestCase
{
    private AuthorizationCheckerInterface&MockObject $authorizationChecker;
    private Security $security;

    #[\Override]
    protected function setUp(): void
    {
        $this->authorizationChecker = $this->createMock(AuthorizationCheckerInterface::class);

        $this->security = new Security(
            ['test_search' => 'test_acl_resource'],
            $this->authorizationChecker
        );
    }

    public function testGetAutocompleteAclResource(): void
    {
        $this->assertNull($this->security->getAutocompleteAclResource('test'));
        $this->assertEquals('test_acl_resource', $this->security->getAutocompleteAclResource('test_search'));
    }

    public function testIsAutocompleteGranted(): void
    {
        $this->authorizationChecker->expects($this->once())
            ->method('isGranted')
            ->with('test_acl_resource')
            ->willReturn(true);

        $this->assertTrue($this->security->isAutocompleteGranted('test_acl_resource'));
        $this->assertTrue($this->security->isAutocompleteGranted('test_search'));
    }
}
