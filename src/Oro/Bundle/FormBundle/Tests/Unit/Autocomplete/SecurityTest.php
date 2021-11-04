<?php

namespace Oro\Bundle\FormBundle\Tests\Unit\Autocomplete;

use Oro\Bundle\FormBundle\Autocomplete\Security;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class SecurityTest extends \PHPUnit\Framework\TestCase
{
    /** @var AuthorizationCheckerInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $authorizationChecker;

    /** @var Security */
    private $security;

    protected function setUp(): void
    {
        $this->authorizationChecker = $this->createMock(AuthorizationCheckerInterface::class);

        $this->security = new Security(
            ['test_search' => 'test_acl_resource'],
            $this->authorizationChecker
        );
    }

    public function testGetAutocompleteAclResource()
    {
        $this->assertNull($this->security->getAutocompleteAclResource('test'));
        $this->assertEquals('test_acl_resource', $this->security->getAutocompleteAclResource('test_search'));
    }

    public function testIsAutocompleteGranted()
    {
        $this->authorizationChecker->expects($this->once())
            ->method('isGranted')
            ->with('test_acl_resource')
            ->willReturn(true);

        $this->assertTrue($this->security->isAutocompleteGranted('test_acl_resource'));
        $this->assertTrue($this->security->isAutocompleteGranted('test_search'));
    }
}
