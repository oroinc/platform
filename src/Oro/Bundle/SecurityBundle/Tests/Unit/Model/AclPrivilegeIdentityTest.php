<?php

namespace Oro\Bundle\SecurityBundle\Tests\Unit\Model;

use Oro\Bundle\SecurityBundle\Model\AclPrivilegeIdentity;

class AclPrivilegeIdentityTest extends \PHPUnit\Framework\TestCase
{
    public function testAclPrivilegeIdentity()
    {
        $obj = new AclPrivilegeIdentity('TestId', 'TestName');
        $this->assertEquals('TestId', $obj->getId());
        $this->assertEquals('TestName', $obj->getName());

        $obj->setId('AnotherId');
        $obj->setName('AnotherName');
        $this->assertEquals('AnotherId', $obj->getId());
        $this->assertEquals('AnotherName', $obj->getName());
    }
}
