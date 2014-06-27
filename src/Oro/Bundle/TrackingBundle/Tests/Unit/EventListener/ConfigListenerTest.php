<?php

namespace Oro\Bundle\TrackingBundle\Tests\Unit\EventListener;

use Oro\Bundle\TrackingBundle\EventListener\ConfigListener;

class ConfigListenerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var string
     */
    protected $logsDir;

    /**
     * @var string
     */
    protected $trackingDir;

    /**
     * @var string
     */
    protected $settingsFile;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $configManager;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $router;

    /**
     * @var ConfigListener
     */
    protected $listener;

    protected function setUp()
    {
        $this->logsDir = realpath(__DIR__) . DIRECTORY_SEPARATOR . 'fixtures';
        $this->trackingDir = $this->logsDir . DIRECTORY_SEPARATOR . 'tracking';
        $this->settingsFile = $this->trackingDir . DIRECTORY_SEPARATOR . 'settings.ser';

        $this->removeSettings();

        $this->configManager = $this->getMockBuilder('Oro\Bundle\ConfigBundle\Config\ConfigManager')
            ->disableOriginalConstructor()
            ->getMock();
        $this->router = $this->getMockBuilder('Symfony\Component\Routing\Router')
            ->disableOriginalConstructor()
            ->getMock();

        $this->listener = new ConfigListener(
            $this->configManager,
            $this->router,
            $this->logsDir
        );
    }

    protected function tearDown()
    {
        $this->removeSettings();
    }

    protected function removeSettings()
    {
        if (is_file($this->settingsFile)) {
            unlink($this->settingsFile);
        }
        if (is_dir($this->trackingDir)) {
            rmdir($this->trackingDir);
        }
    }

    public function testOnUpdateAfterNoChanges()
    {
        $event = $this->getMockBuilder('Oro\Bundle\ConfigBundle\Event\ConfigUpdateEvent')
            ->disableOriginalConstructor()
            ->getMock();

        $this->listener->onUpdateAfter($event);
        $this->assertFileNotExists($this->settingsFile);
    }

    public function testOnUpdateAfterNoDynamic()
    {
        $event = $this->getMockBuilder('Oro\Bundle\ConfigBundle\Event\ConfigUpdateEvent')
            ->disableOriginalConstructor()
            ->getMock();

        $event->expects($this->exactly(4))
            ->method('isChanged')
            ->will(
                $this->returnValueMap(
                    array(
                        array('oro_tracking.dynamic_tracking_enabled', false),
                        array('oro_tracking.log_rotate_interval', true),
                        array('oro_tracking.piwik_host', true),
                        array('oro_tracking.piwik_token_auth', false),
                    )
                )
            );

        $event->expects($this->exactly(2))
            ->method('getNewValue')
            ->will(
                $this->returnValueMap(
                    array(
                        array('oro_tracking.log_rotate_interval', 5),
                        array('oro_tracking.piwik_host', 'http://test.com')
                    )
                )
            );

        $this->configManager->expects($this->exactly(2))
            ->method('get')
            ->will(
                $this->returnValueMap(
                    array(
                        array(
                            'oro_tracking.dynamic_tracking_enabled',
                            true,
                            false,
                            array('value' => false, 'scope' => 'app')
                        ),
                        array('oro_tracking.piwik_token_auth', true, false, 'TEST_DEFAULT')
                    )
                )
            );

        $this->router->expects($this->never())
            ->method('generate');

        $this->listener->onUpdateAfter($event);
        $this->assertFileExists($this->settingsFile);

        $expectedSettings = array(
            'dynamic_tracking_enabled' => false,
            'log_rotate_interval' => 5,
            'piwik_host' => 'http://test.com',
            'piwik_token_auth' => 'TEST_DEFAULT',
            'dynamic_tracking_endpoint' => null
        );
        $actualSettings = unserialize(file_get_contents($this->settingsFile));
        $this->assertEquals($expectedSettings, $actualSettings);
    }

    public function testOnUpdateAfterDynamic()
    {
        $event = $this->getMockBuilder('Oro\Bundle\ConfigBundle\Event\ConfigUpdateEvent')
            ->disableOriginalConstructor()
            ->getMock();

        $event->expects($this->exactly(4))
            ->method('isChanged')
            ->will(
                $this->returnValueMap(
                    array(
                        array('oro_tracking.dynamic_tracking_enabled', true),
                        array('oro_tracking.log_rotate_interval', false),
                        array('oro_tracking.piwik_host', false),
                        array('oro_tracking.piwik_token_auth', false),
                    )
                )
            );

        $event->expects($this->exactly(1))
            ->method('getNewValue')
            ->will(
                $this->returnValueMap(
                    array(
                        array('oro_tracking.dynamic_tracking_enabled', true)
                    )
                )
            );

        $this->configManager->expects($this->exactly(3))
            ->method('get')
            ->will($this->returnValue('default'));

        $this->router->expects($this->once())
            ->method('generate')
            ->with('oro_tracking_website_view')
            ->will($this->returnValue('/test/url'));

        $this->listener->onUpdateAfter($event);
        $this->assertFileExists($this->settingsFile);

        $expectedSettings = array(
            'dynamic_tracking_enabled' => true,
            'log_rotate_interval' => 'default',
            'piwik_host' => 'default',
            'piwik_token_auth' => 'default',
            'dynamic_tracking_endpoint' => '/test/url'
        );
        $actualSettings = unserialize(file_get_contents($this->settingsFile));
        $this->assertEquals($expectedSettings, $actualSettings);
    }
}
