<?php

namespace Oro\Bundle\LDAPBundle\Tests\Unit\Manager;

use Oro\Bundle\LDAPBundle\Manager\ExportManager;
use Oro\Bundle\LDAPBundle\Tests\Unit\Stub\TestingUser;

class ExportManagerTest extends \PHPUnit_Framework_TestCase
{
    private $ldapManager;
    private $userManager;

    private $exportManager;

    public function setUp()
    {
        $this->ldapManager = $this->getMockBuilder('Oro\Bundle\LDAPBundle\LDAP\LdapManager')
            ->disableOriginalConstructor()
            ->getMock();
        $this->userManager = $this->getMockBuilder('Oro\Bundle\UserBundle\Entity\UserManager')
            ->disableOriginalConstructor()
            ->getMock();

        $this->exportManager = new ExportManager($this->ldapManager, $this->userManager);
    }

    public function testExportNewUser()
    {
        $user1 = new TestingUser();
        $user1->setUsername('user1');
        $user1->setEmail('user1@example.com');

        $this->userManager->expects($this->once())
            ->method('findUsers')
            ->will($this->returnValue([$user1]));
        $this->ldapManager->expects($this->once())
            ->method('exists')
            ->with($user1)
            ->will($this->returnValue(false));
        $this->ldapManager->expects($this->once())
            ->method('save')
            ->with($user1);

        $expectedResult = [
            'add'      => 1,
            'replace'  => 0,
            'total'    => 1,
            'done'     => true,
            'errors'   => [],
        ];

        $result = $this->exportManager->export(false);
        $this->assertEquals($expectedResult, $result);
    }

    public function testExportExistingUser()
    {
        $user1 = new TestingUser();
        $user1->setUsername('user1');
        $user1->setEmail('user1@example.com');

        $this->userManager->expects($this->once())
            ->method('findUsers')
            ->will($this->returnValue([$user1]));
        $this->ldapManager->expects($this->once())
            ->method('exists')
            ->with($user1)
            ->will($this->returnValue(true));
        $this->ldapManager->expects($this->once())
            ->method('save')
            ->with($user1);

        $expectedResult = [
            'add'      => 0,
            'replace'  => 1,
            'total'    => 1,
            'done'     => true,
            'errors'   => [],
        ];

        $result = $this->exportManager->export(false);
        $this->assertEquals($expectedResult, $result);
    }

    public function testExportExistingUserDryRun()
    {
        $user1 = new TestingUser();
        $user1->setUsername('user1');
        $user1->setEmail('user1@example.com');

        $this->userManager->expects($this->once())
            ->method('findUsers')
            ->will($this->returnValue([$user1]));
        $this->ldapManager->expects($this->once())
            ->method('exists')
            ->with($user1)
            ->will($this->returnValue(true));

        $expectedResult = [
            'add'      => 0,
            'replace'  => 1,
            'total'    => 1,
            'done'     => false,
            'errors'   => [],
        ];

        $result = $this->exportManager->export(true);
        $this->assertEquals($expectedResult, $result);
    }
}
