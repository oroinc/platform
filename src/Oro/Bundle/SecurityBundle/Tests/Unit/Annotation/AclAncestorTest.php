<?php

namespace Oro\Bundle\SecurityBundle\Tests\Unit\Annotation;

use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;

class AclAncestorTest extends \PHPUnit\Framework\TestCase
{
    public function testAncestor()
    {
        $aclAncestor = new AclAncestor(['value' => 'test_acl']);
        $this->assertEquals('test_acl', $aclAncestor->getId());
    }

    public function testAncestorWithEmptyId()
    {
        $this->expectException(\InvalidArgumentException::class);
        new AclAncestor(['value' => '']);
    }

    public function testAncestorWithInvalidId()
    {
        $this->expectException(\InvalidArgumentException::class);
        new AclAncestor(['value' => 'test acl']);
    }

    public function testAncestorWithMissingId()
    {
        $this->expectException(\InvalidArgumentException::class);
        new AclAncestor([]);
    }
}
