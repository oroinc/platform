<?php

namespace Oro\Bundle\ConfigBundle\Tests\Unit\Manager;

use Oro\Bundle\ConfigBundle\Manager\GlobalConfigManager;
use Oro\Bundle\UserBundle\Entity\User;

class GlobalConfigManagerTest extends \PHPUnit_Framework_TestCase
{
    public function testSaveUserConfigSignatureNoSignature()
    {
        $manager = new GlobalConfigManager();
        $this->assertFalse($manager->saveUserConfigSignature(new User(), ''));
    }

    /**
     * @expectedException \RuntimeException
     */
    public function testSaveUserConfigSignatureWithException()
    {
        $manager = new GlobalConfigManager();
        $manager->saveUserConfigSignature(new User(), 'testSignature');
    }

    public function testSaveUserConfigSignature()
    {
        $configManager = $this->getMockBuilder('Oro\Bundle\ConfigBundle\Config\ConfigManager')
            ->disableOriginalConstructor()
            ->getMock();
        $userScopeManager = $this->getMockBuilder('Oro\Bundle\ConfigBundle\Config\UserScopeManager')
            ->disableOriginalConstructor()
            ->getMock();
        $userScopeManager->expects($this->at(0))
            ->method('getScopedEntityName');
        $configManager->expects($this->once())
            ->method('addManager');
        $userScopeManager->expects($this->at(1))
            ->method('getScopedEntityName');
        $configManager->expects($this->once())
            ->method('setScopeName');
        $configManager->expects($this->exactly(2))
            ->method('setScopeId');
        $configManager->expects($this->once())
            ->method('save');
        $manager = new GlobalConfigManager($configManager, $userScopeManager);
        $manager->saveUserConfigSignature(new User(), 'testSignature');
    }

    public function testGetUserConfigSignature()
    {
        $configManager = $this->getMockBuilder('Oro\Bundle\ConfigBundle\Config\ConfigManager')
            ->disableOriginalConstructor()
            ->getMock();
        $userScopeManager = $this->getMockBuilder('Oro\Bundle\ConfigBundle\Config\UserScopeManager')
            ->disableOriginalConstructor()
            ->getMock();
        $userScopeManager->expects($this->at(0))
            ->method('getScopedEntityName');
        $configManager->expects($this->once())
            ->method('addManager');
        $userScopeManager->expects($this->at(1))
            ->method('getScopedEntityName');
        $configManager->expects($this->once())
            ->method('setScopeName');
        $configManager->expects($this->exactly(2))
            ->method('setScopeId');
        $testSignature = 'testSignature';
        $configManager->expects($this->once())
            ->method('get')
            ->with('oro_email.signature')
            ->will($this->returnValue($testSignature));

        $manager = new GlobalConfigManager($configManager, $userScopeManager);
        $this->assertEquals($testSignature, $manager->getUserConfigSignature(new User()));
    }
}
