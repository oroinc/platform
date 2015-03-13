<?php

namespace Oro\Bundle\ConfigBundle\Tests\Unit\Manager;

use Oro\Bundle\ConfigBundle\Manager\UserConfigManager;

class UserConfigManagerTest extends \PHPUnit_Framework_TestCase
{
    public function testSaveUserConfigSignatureNoSignature()
    {
        $userScopeManager = $this->getMockBuilder('Oro\Bundle\ConfigBundle\Config\UserScopeManager')
            ->disableOriginalConstructor()
            ->getMock();
        $userScopeManager->expects($this->once())
            ->method('save')
            ->with(['oro_email___signature' => null]);
        $manager = new UserConfigManager($userScopeManager);
        $manager->saveUserConfigSignature('');
    }

    public function testSaveUserConfigSignature()
    {
        $signature = 'testSignature';
        $userScopeManager = $this->getMockBuilder('Oro\Bundle\ConfigBundle\Config\UserScopeManager')
            ->disableOriginalConstructor()
            ->getMock();
        $userScopeManager->expects($this->once())
            ->method('save')
            ->with(['oro_email___signature' => $signature]);
        $manager = new UserConfigManager($userScopeManager);
        $manager->saveUserConfigSignature($signature);
    }

    public function testGetUserConfigSignature()
    {
        $signature = 'testSignature';
        $userScopeManager = $this->getMockBuilder('Oro\Bundle\ConfigBundle\Config\UserScopeManager')
            ->disableOriginalConstructor()
            ->getMock();
        $userScopeManager->expects($this->once())
            ->method('getSettingValue')
            ->with('oro_email.signature')
            ->will($this->returnValue($signature));
        $manager = new UserConfigManager($userScopeManager);
        $this->assertEquals($signature, $manager->getUserConfigSignature());
    }
}
