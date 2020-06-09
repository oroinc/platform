<?php

namespace Oro\Bundle\SecurityBundle\Tests\Unit\Annotation;

use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;

class AclAncestorTest extends \PHPUnit\Framework\TestCase
{
    public function testAncestor()
    {
        $aclAncestor = new AclAncestor(array('value' => 'test_acl'));
        $this->assertEquals('test_acl', $aclAncestor->getId());
    }

    public function testAncestorWithEmptyId()
    {
        $this->expectException(\InvalidArgumentException::class);
        $aclAncestor = new AclAncestor(array('value' => ''));
    }

    public function testAncestorWithInvalidId()
    {
        $this->expectException(\InvalidArgumentException::class);
        $aclAncestor = new AclAncestor(array('value' => 'test acl'));
    }

    public function testAncestorWithMissingId()
    {
        $this->expectException(\InvalidArgumentException::class);
        $aclAncestor = new AclAncestor(array());
    }
}
