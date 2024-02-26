<?php

namespace Oro\Bundle\SecurityBundle\Tests\Unit\Attribute;

use Oro\Bundle\SecurityBundle\Attribute\AclAncestor;

class AclAncestorTest extends \PHPUnit\Framework\TestCase
{
    public function testAncestor()
    {
        $aclAncestor = AclAncestor::fromArray(['value' => 'test_acl']);
        $this->assertEquals('test_acl', $aclAncestor->getId());
    }

    public function testAncestorWithEmptyId()
    {
        $this->expectException(\InvalidArgumentException::class);
        AclAncestor::fromArray(['value' => '']);
    }

    public function testAncestorWithInvalidId()
    {
        $this->expectException(\InvalidArgumentException::class);
        AclAncestor::fromArray(['value' => 'test acl']);
    }

    public function testAncestorWithMissingId()
    {
        $this->expectException(\InvalidArgumentException::class);
        AclAncestor::fromArray([]);
    }
}
