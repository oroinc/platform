<?php

namespace Oro\Bundle\SecurityBundle\Tests\Unit\Model;

use Oro\Bundle\SecurityBundle\Acl\AccessLevel;
use Oro\Bundle\SecurityBundle\Model\AclPrivilege;
use Oro\Bundle\SecurityBundle\Model\AclPrivilegeIdentity;
use Oro\Bundle\SecurityBundle\Model\AclPermission;

class AclPrivilegeTest extends \PHPUnit_Framework_TestCase
{
    public function testGettersAndSetters()
    {
        $obj = new AclPrivilege();

        $id = new AclPrivilegeIdentity('TestId', 'TestName');
        $obj->setIdentity($id);
        $obj->setExtensionKey('TestKey');
        $obj->setGroup('TestGroup');
        $obj->setDescription('TestDescription');
        $obj->setCategory('TestCategory');
        $this->assertTrue($id === $obj->getIdentity());
        $this->assertEquals('TestKey', $obj->getExtensionKey());
        $this->assertEquals('TestGroup', $obj->getGroup());
        $this->assertEquals('TestDescription', $obj->getDescription());
        $this->assertEquals('TestCategory', $obj->getCategory());
    }

    public function testPermissions()
    {
        $obj = new AclPrivilege();

        $this->assertFalse($obj->hasPermissions());
        $this->assertFalse($obj->hasPermission('VIEW'));
        $this->assertEquals(0, $obj->getPermissionCount());

        $permission = new AclPermission('VIEW', AccessLevel::BASIC_LEVEL);

        $obj->addPermission($permission);
        $this->assertTrue($obj->hasPermissions());
        $this->assertTrue($obj->hasPermission('VIEW'));
        $this->assertFalse($obj->hasPermission('Another'));
        $this->assertEquals(1, $obj->getPermissionCount());

        $obj->removePermission($permission);
        $this->assertFalse($obj->hasPermissions());
        $this->assertFalse($obj->hasPermission('VIEW'));
        $this->assertEquals(0, $obj->getPermissionCount());
    }
}
