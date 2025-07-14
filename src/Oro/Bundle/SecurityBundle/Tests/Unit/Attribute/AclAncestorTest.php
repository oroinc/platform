<?php

namespace Oro\Bundle\SecurityBundle\Tests\Unit\Attribute;

use Oro\Bundle\SecurityBundle\Attribute\AclAncestor;
use PHPUnit\Framework\TestCase;

class AclAncestorTest extends TestCase
{
    public function testAncestor(): void
    {
        $aclAncestor = AclAncestor::fromArray(['value' => 'test_acl']);
        $this->assertEquals('test_acl', $aclAncestor->getId());
    }

    public function testAncestorWithEmptyId(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        AclAncestor::fromArray(['value' => '']);
    }

    public function testAncestorWithInvalidId(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        AclAncestor::fromArray(['value' => 'test acl']);
    }

    public function testAncestorWithMissingId(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        AclAncestor::fromArray([]);
    }
}
